<?php
/**
 * Handles the integration of Bookings for WooCommerce (Plugin Republic) with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\PluginRepublic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Bookings_For_WooCommerce_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'BFWC_PLUGIN_VERSION' ) ) {
			return;
		}
		// After the Product Add-Ons Ultimate integration (priority 1): the booking cost already includes its extras.
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 20, 2 );
		add_action( 'yaydp_after_initial_cart_item', array( $this, 'initialize_cart_item_store_price' ), 10, 2 );
		add_filter( 'yaydp_other_source_product_base_price', array( $this, 'get_product_base_price' ), 10, 2 );
		// Bookings re-sets the booking cost at priority 10 on every totals pass, but YayPricing (110) skips a pass
		// when the cart is unchanged, so re-apply our price. Registered before the managers, so it runs before
		// the cart discount manager at the same priority.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'reapply_cart_item_price' ), 111 );
	}

	/**
	 * Re-apply YayPricing price to booking cart items after Bookings has overwritten it
	 *
	 * @param \WC_Cart $cart Cart object.
	 */
	public function reapply_cart_item_price( $cart ) {
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( ! self::is_booking_cart_item( $cart_item ) || ! isset( $cart_item['yaydp_custom_data']['price'] ) ) {
				continue;
			}
			$cart_item['data']->set_price( $cart_item['yaydp_custom_data']['price'] );
		}
	}

	/**
	 * Use the booking cost as the cart item base price.
	 *
	 * Booking products have no regular or sale price, and the booking cost is the
	 * full line price (days, units and add-ons), the same value the plugin sets itself.
	 * YayPricing prices in the store currency, so the cost is converted back from the
	 * currency it was booked in, and YayExtra options on the booking are added on top.
	 *
	 * @param float $initial_price Initial price.
	 * @param array $cart_item_data Cart item data.
	 */
	public function initialize_cart_item_price( $initial_price, $cart_item_data ) {
		if ( ! self::is_booking_cart_item( $cart_item_data ) ) {
			return $initial_price;
		}
		return self::get_booking_cost_in_store_currency( $cart_item_data['booking'] ) + self::get_option_cost( $cart_item_data );
	}

	/**
	 * Use the booking cost as the store price too, the price cart discounts, fees and conditions
	 * are built on when no product pricing rule applies.
	 *
	 * The store price is read off the product, where Bookings sets the cost in the currency it
	 * was booked in and currency plugins convert it again on read.
	 *
	 * @param array                        $cart_item_data Cart item data.
	 * @param \YAYDP\Core\YAYDP_Cart_Item $cart_item YayPricing cart item.
	 */
	public function initialize_cart_item_store_price( $cart_item_data, $cart_item ) {
		if ( ! self::is_booking_cart_item( $cart_item_data ) ) {
			return;
		}
		$cart_item->set_store_price( $cart_item->get_initial_price() );
	}

	/**
	 * Bookings keeps the cost in the currency the customer was shopping in ( `bfwc_currency` ),
	 * either converted by itself or entered by hand for that currency.
	 *
	 * @param array $booking Booking data of the cart item.
	 */
	public static function get_booking_cost_in_store_currency( $booking ) {
		$cost     = (float) $booking['cost'];
		$currency = isset( $booking['bfwc_currency'] ) ? $booking['bfwc_currency'] : '';
		if ( '' === $currency || get_option( 'woocommerce_currency' ) === $currency ) {
			return $cost;
		}
		return (float) apply_filters( 'yaydp_reversed_price', $cost, $currency );
	}

	/**
	 * Whether the given cart item is a booking with its cost set.
	 *
	 * @param array $cart_item Cart item data.
	 */
	public static function is_booking_cart_item( $cart_item ) {
		return is_array( $cart_item ) && isset( $cart_item['booking']['cost'] ) && '' !== $cart_item['booking']['cost'];
	}

	/**
	 * YayExtra keeps the option cost of a booking in the store currency ( percentages already taken off the booking ).
	 * Only its Bookings integration writes `yaye_product_price_original`; older versions take percentages off the standard cost.
	 *
	 * @param array $cart_item Cart item data.
	 */
	public static function get_option_cost( $cart_item ) {
		if ( empty( $cart_item['yaye_custom_option'] ) || ! isset( $cart_item['yaye_total_option_cost'], $cart_item['yaye_product_price_original'] ) ) {
			return 0;
		}
		return (float) $cart_item['yaye_total_option_cost'];
	}

	/**
	 * Fall back to the standard booking cost for booking products without a regular price.
	 *
	 * @param float       $price Product base price.
	 * @param \WC_Product $product Product.
	 */
	public function get_product_base_price( $price, $product ) {
		if ( ! empty( $price ) || ! is_object( $product ) || 'simple_booking' !== $product->get_type() || ! function_exists( 'bfwc_get_standard_cost' ) ) {
			return $price;
		}
		$standard_cost = \bfwc_get_standard_cost( $product );
		return is_numeric( $standard_cost ) ? floatval( $standard_cost ) : $price;
	}
}