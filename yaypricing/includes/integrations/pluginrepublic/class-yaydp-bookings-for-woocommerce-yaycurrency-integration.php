<?php
/**
 * Handles Bookings for WooCommerce (Plugin Republic) cart items under YayCurrency
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\PluginRepublic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 *
 * YayCurrency prices a cart item off the catalog price of its product, which for a booking is the
 * standard cost of a single unit ( one day ), not the booking cost Bookings set on the item. These
 * hooks hand it the booking cost instead, so the line, the subtotals and the discounts we build on
 * the booking cost all agree. Items priced by a product pricing rule are left to the YayCurrency
 * and YayExtra integrations.
 */
class YAYDP_Bookings_For_WooCommerce_YayCurrency_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'BFWC_PLUGIN_VERSION' ) || ! class_exists( '\Yay_Currency\Helpers\SupportHelper' ) ) {
			return;
		}
		// Subtotals YayCurrency sums up itself ( totals in the store currency, coupons, shipping ). After its YayPricing support (30).
		add_filter( 'YayCurrency/StoreCurrency/ByCartItem/GetProductPrice', array( $this, 'get_store_cart_item_price' ), 40, 2 );
		add_filter( 'YayCurrency/ApplyCurrency/ByCartItem/GetProductPrice', array( $this, 'get_cart_item_price' ), 40, 3 );
		if ( ! defined( 'YAYE_VERSION' ) ) {
			return;
		}
		// After YayCurrency's own YayExtra support (10), which caches the price of items with YayExtra options only.
		add_action( 'yay_currency_set_cart_contents', array( $this, 'set_cart_item_price' ), 20, 4 );
		// Before YayCurrency's YayExtra support (10) looks the catalog price up.
		add_filter( 'YayCurrency/StoreCurrency/GetPrice', array( $this, 'cache_catalog_price' ), 9, 2 );
		// From PHP 8.2 these prices are kept as product meta: never let them reach the database.
		add_action( 'woocommerce_before_product_object_save', array( $this, 'drop_cached_prices' ) );
	}

	/**
	 * Hand YayCurrency the line price of a booking cart item.
	 *
	 * While YayExtra is active, YayCurrency prices a cart item from the price it caches on the item, and
	 * only caches it for items with YayExtra options; otherwise it falls back to the catalog price.
	 *
	 * @param array  $cart_contents Cart contents.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $cart_item Cart item.
	 * @param array  $apply_currency Currency the customer is shopping in.
	 */
	public function set_cart_item_price( $cart_contents, $cart_item_key, $cart_item, $apply_currency ) {
		if ( ! YAYDP_Bookings_For_WooCommerce_Integration::is_booking_cart_item( $cart_item ) || empty( $cart_item['data'] ) ) {
			return;
		}
		$product     = $cart_item['data'];
		$option_cost = YAYDP_Bookings_For_WooCommerce_Integration::get_option_cost( $cart_item );
		// YayCurrency's own figure for the options, converted and rounded the way it shows them.
		$converted_option_cost = $option_cost ? (float) \Yay_Currency\Helpers\SupportHelper::get_cart_item_objects_property( $product, 'yay_currency_extra_price_options' ) : 0;
		$store_cost            = YAYDP_Bookings_For_WooCommerce_Integration::get_booking_cost_in_store_currency( $cart_item['booking'] );
		$this->cache_price( $product, 'yay_currency_extra_set_price_with_options_default', $store_cost + $option_cost );
		$this->cache_price( $product, 'yay_currency_extra_set_price_with_options', $this->get_booking_cost( $cart_item['booking'], $apply_currency ) + $converted_option_cost );
	}

	/**
	 * Booking cost of a booking cart item in the store currency.
	 *
	 * @param float $price Price YayCurrency composed.
	 * @param array $cart_item Cart item.
	 *
	 * @return float
	 */
	public function get_store_cart_item_price( $price, $cart_item ) {
		if ( ! $this->is_unpriced_booking_cart_item( $cart_item ) ) {
			return $price;
		}
		return YAYDP_Bookings_For_WooCommerce_Integration::get_booking_cost_in_store_currency( $cart_item['booking'] );
	}

	/**
	 * Booking cost of a booking cart item in the currency the customer is shopping in.
	 *
	 * @param float $price Price YayCurrency composed.
	 * @param array $cart_item Cart item.
	 * @param array $apply_currency Currency the customer is shopping in.
	 *
	 * @return float
	 */
	public function get_cart_item_price( $price, $cart_item, $apply_currency ) {
		if ( ! $this->is_unpriced_booking_cart_item( $cart_item ) ) {
			return $price;
		}
		return $this->get_booking_cost( $cart_item['booking'], $apply_currency );
	}

	/**
	 * The booking cost as Bookings set it, unless the customer has switched currency since.
	 *
	 * @param array $booking Booking data of the cart item.
	 * @param array $apply_currency Currency the customer is shopping in.
	 *
	 * @return float
	 */
	private function get_booking_cost( $booking, $apply_currency ) {
		$currency = isset( $booking['bfwc_currency'] ) ? $booking['bfwc_currency'] : '';
		if ( '' !== $currency && isset( $apply_currency['currency'] ) && $apply_currency['currency'] === $currency ) {
			return (float) $booking['cost'];
		}
		$store_cost = YAYDP_Bookings_For_WooCommerce_Integration::get_booking_cost_in_store_currency( $booking );
		return (float) \Yay_Currency\Helpers\YayCurrencyHelper::calculate_price_by_currency( $store_cost, false, $apply_currency );
	}

	/**
	 * Hand YayCurrency's YayExtra support the catalog price of a booking product it is about to look up.
	 *
	 * It looks the price up on a fresh product, and reading the price of a booking product runs the
	 * price filters again in every context, which brings it back here - on a checkout in the store
	 * currency, until PHP runs out of memory. The raw price is the one it was after.
	 *
	 * @param float       $price Price in the store currency.
	 * @param \WC_Product $product Product.
	 *
	 * @return float
	 */
	public function cache_catalog_price( $price, $product ) {
		if ( ! is_object( $product ) || 'simple_booking' !== $product->get_type() || \Yay_Currency\Helpers\SupportHelper::get_cart_item_objects_property( $product, 'yay_currency_extra_set_price_with_options_default' ) ) {
			return $price;
		}
		$props     = array_merge( $product->get_data(), $product->get_changes() );
		$raw_price = isset( $props['price'] ) ? $props['price'] : '';
		if ( '' === $raw_price && function_exists( 'bfwc_get_standard_cost' ) ) {
			$raw_price = \bfwc_get_standard_cost( $product );
		}
		$this->cache_price( $product, 'yay_currency_extra_set_price_with_options_default', is_numeric( $raw_price ) ? (float) $raw_price : 0 );
		return $price;
	}

	/**
	 * Cache a price on a product the way YayCurrency does.
	 *
	 * YayCurrency takes an empty value - 0 included - for no price cached and looks the catalog price up,
	 * which is the lookup that never ends for a booking product, so a free booking is cached as '0.0'.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $key Property YayCurrency reads.
	 * @param float       $price Price.
	 */
	private function cache_price( $product, $key, $price ) {
		\Yay_Currency\Helpers\SupportHelper::set_cart_item_objects_property( $product, $key, $price > 0 ? (float) $price : '0.0' );
	}

	/**
	 * Drop the prices cached on a booking product before it is saved.
	 *
	 * @param \WC_Product $product Product about to be saved.
	 */
	public function drop_cached_prices( $product ) {
		if ( ! is_object( $product ) || 'simple_booking' !== $product->get_type() ) {
			return;
		}
		foreach ( array( 'yay_currency_extra_set_price_with_options_default', 'yay_currency_extra_set_price_with_options' ) as $key ) {
			$product->delete_meta_data( \Yay_Currency\Helpers\SupportHelper::cart_item_maybe_prefix_key( $key ) );
		}
	}

	/**
	 * Whether the cart item is a booking no product pricing rule has priced.
	 *
	 * @param array $cart_item Cart item.
	 */
	private function is_unpriced_booking_cart_item( $cart_item ) {
		return YAYDP_Bookings_For_WooCommerce_Integration::is_booking_cart_item( $cart_item ) && empty( $cart_item['modifiers'] );
	}
}
