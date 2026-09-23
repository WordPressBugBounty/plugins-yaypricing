<?php
/**
 * Handles the integration of YayExtra plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\YayCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_YayExtra_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'YAYE_VERSION' ) ) {
			return;
		}
		// The filter and its picker collection exist on any YayExtra site; only the price hooks below need YayCurrency.
		add_action( 'yaydp_register_product_filters', array( $this, 'register_product_filters' ) );
		add_filter( 'yaydp_collections', array( $this, 'register_collection' ) );
		if ( ! class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			return;
		}
		add_filter( 'yaydp_initial_product_price', array( $this, 'get_cart_item_price' ), 100, 2 );
		add_filter( 'yay_currency_product_price_3rd_with_condition', array( $this, 'keep_priced_cart_item_price_by_currency' ), 20, 2 );
		add_filter( 'YayCurrency/StoreCurrency/GetPrice', array( $this, 'keep_priced_cart_item_price' ), 20, 2 );
		add_filter( 'YayCurrency/StoreCurrency/ByCartItem/GetPriceOptions', array( $this, 'drop_counted_option_cost' ), 20, 2 );
		add_filter( 'YayCurrency/ApplyCurrency/ByCartItem/GetPriceOptions', array( $this, 'drop_counted_option_cost' ), 20, 2 );
	}

	/**
	 * Align the store currency price of a cart item with the price the cart actually charges.
	 *
	 * While YayExtra is active we read cart item prices straight from the product object, where
	 * YayExtra keeps them in the store currency as `reverse( converted product price ) + option
	 * cost`. That does not convert back to what the cart charges: YayCurrency converts the option
	 * cost on its own and rounds it ( rounding type / rounding value ), and reversing the product
	 * price and converting it again leaves a remainder of a few billionths that the same rounding
	 * turns into a whole rounding step - 550 becomes 560 at a rounding value of 10.
	 *
	 * Every pricing we build on top of that price inherits the gap, so a cart discount of 10% ends
	 * up being 10% of an amount the customer is never shown. Rebuilding the converted price the way
	 * YayCurrency builds it, and reversing that, gives the store currency price the cart is really
	 * made of.
	 *
	 * @param float       $price Given price in store currency.
	 * @param \WC_Product $product Given product.
	 *
	 * @return float
	 */
	public function get_cart_item_price( $price, $product ) {
		if ( empty( $price ) ) {
			return $price;
		}

		if ( ! $this->is_cart_item_product( $product ) ) {
			return $price;
		}

		$apply_currency = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
		if ( empty( $apply_currency ) ) {
			return $price;
		}

		// Cart runs in the store currency, nothing is converted.
		if ( \Yay_Currency\Helpers\YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency ) ) {
			return $price;
		}

		$rate = floatval( \Yay_Currency\Helpers\YayCurrencyHelper::get_rate_fee( $apply_currency ) );
		if ( $rate <= 0 ) {
			return $price;
		}

		/**
		 * The price of the whole item as YayCurrency composes it for the cart: the converted product
		 * price - or the price fixed for this currency - plus the option cost converted and rounded on
		 * its own. Reading its own figure keeps us right for option costs given as a percentage too,
		 * which YayCurrency takes off the converted product price rather than converting.
		 */
		$converted_price = \Yay_Currency\Helpers\SupportHelper::get_cart_item_objects_property( $product, 'yay_currency_extra_set_price_with_options' );

		/**
		 * Nothing on the item costs extra, so the given price is the converted product price reversed.
		 * Multiplying by the rate lands back on it up to the remainder of the division - snapping to the
		 * precision of the currency drops the remainder and recovers the amount itself, where converting
		 * again would round the remainder up into the next step ( 550 would become 560 at a rounding
		 * value of 10 ).
		 */
		if ( empty( $converted_price ) ) {
			$converted_price = round( floatval( $price ) * $rate, \wc_get_price_decimals() );
		}

		if ( empty( $converted_price ) ) {
			return $price;
		}

		return \Yay_Currency\Helpers\YayCurrencyHelper::reverse_calculate_price_by_currency( floatval( $converted_price ), $apply_currency );
	}

	/**
	 * Drop the option cost YayCurrency adds on top of our price while it sums up the cart.
	 *
	 * Summing a cart item, YayCurrency takes the price we wrote on it ( see its own YayPricing
	 * compatibility ) and adds the option cost of the item to it. Our price is the price of the whole
	 * item, option cost included, so the option cost would be counted twice - a cart subtotal above
	 * what the same cart charges.
	 *
	 * @param float $price_options Option cost YayCurrency is about to add.
	 * @param array $cart_item Given cart item.
	 *
	 * @return float
	 */
	public function drop_counted_option_cost( $price_options, $cart_item ) {
		if ( empty( $cart_item['modifiers'] ) || empty( $cart_item['yaydp_custom_data']['price'] ) ) {
			return $price_options;
		}
		return 0;
	}

	/**
	 * Register the YayExtra option filter.
	 *
	 * @param \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry $registry Registry.
	 */
	public function register_product_filters( $registry ) {
		$registry->register( new YAYDP_YayExtra_Options_Product_Filter() );
	}

	/**
	 * The YayExtra options picker: route page-data/yayextra-options, seeded.
	 *
	 * @param array $collections Picker collections.
	 * @return array
	 */
	public function register_collection( $collections ) {
		$collections['yayextra_options'] = array(
			'route'  => 'yayextra-options',
			'getter' => array( \YAYDP\API\Models\YAYDP_Data_Model::class, 'get_yayextra_options' ),
			'seed'   => true,
		);
		return $collections;
	}

	public static function init() {
		self::get_instance();
	}

	/**
	 * Check whether the given product is the product of an item in the cart.
	 *
	 * Cart items carry their own product instance, the one YayExtra writes the price of the item on.
	 * Products coming from anywhere else ( shop pages, single product pages ) hold a catalog price
	 * that is converted on its own and must be left alone.
	 *
	 * @param \WC_Product $product Given product.
	 *
	 * @return bool
	 */
	private function is_cart_item_product( $product ) {
		return false !== $this->get_cart_item_of( $product );
	}

	/**
	 * Returns the cart item the given product object belongs to, false when it belongs to none.
	 *
	 * @param \WC_Product $product Given product.
	 *
	 * @return array|false
	 */
	private function get_cart_item_of( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}
		if ( ! function_exists( 'WC' ) || empty( \WC()->cart ) ) {
			return false;
		}
		foreach ( \WC()->cart->cart_contents as $cart_item ) {
			if ( isset( $cart_item['data'] ) && $cart_item['data'] === $product ) {
				return $cart_item;
			}
		}
		return false;
	}

	/**
	 * Returns the price we wrote on the given cart item product, null when we priced no such item.
	 *
	 * Read straight off the product object rather than off `yaydp_custom_data`, so a pass that has
	 * rewound a price it no longer discounts is followed too. Read in the `edit` context, the raw
	 * price the cart is made of, so reading it does not run the conversion filters we answer from.
	 *
	 * @param \WC_Product $product Given product.
	 *
	 * @return float|null
	 */
	private function get_priced_cart_item_price( $product ) {
		$cart_item = $this->get_cart_item_of( $product );
		if ( false === $cart_item || ! isset( $cart_item['yaydp_custom_data']['price'] ) ) {
			return null;
		}
		return floatval( $product->get_price( 'edit' ) );
	}

	/**
	 * Keep our price on a cart item when YayCurrency asks YayExtra what the item costs in the
	 * current currency.
	 *
	 * YayExtra answers with the catalog price of the product ( plus its option cost ) read afresh
	 * from the database, which throws away the discount we wrote on the item and leaves the cart
	 * charging the undiscounted price. The price on the item is the one the cart is made of, so
	 * hand that back instead, converted the way YayCurrency converts.
	 *
	 * @param float       $price Price YayExtra composed, false when it composed none.
	 * @param \WC_Product $product Given product.
	 *
	 * @return float|false
	 */
	public function keep_priced_cart_item_price_by_currency( $price, $product ) {
		$item_price = $this->get_priced_cart_item_price( $product );
		if ( is_null( $item_price ) ) {
			return $price;
		}
		$apply_currency = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
		return \Yay_Currency\Helpers\YayCurrencyHelper::calculate_price_by_currency( $item_price, false, $apply_currency );
	}

	/**
	 * Keep our price on a cart item while the cart runs in the store currency.
	 *
	 * Same loss as above on the branch YayCurrency takes for the store currency - and for a currency
	 * checking out in the store currency - where our price needs no converting.
	 *
	 * @param float       $price Price YayExtra composed.
	 * @param \WC_Product $product Given product.
	 *
	 * @return float
	 */
	public function keep_priced_cart_item_price( $price, $product ) {
		$item_price = $this->get_priced_cart_item_price( $product );
		return is_null( $item_price ) ? $price : $item_price;
	}

}
