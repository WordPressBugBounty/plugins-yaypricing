<?php
/**
 * Product Addons Ultimate and YayPricing compatibility.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WC_Product_Addons_Ultimate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAYDP_WC_Product_Addons_Ultimate_Integration {

	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
		if ( ! function_exists( 'pewc_plugins_loaded' ) ) {
			return;
		}
		add_filter( 'pewc_filter_default_price', array( $this, 'modify_product_total' ), 10, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'modify_variation_price' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'modify_cart_item_subtotal' ), 10, 3 );
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 1, 2 );
		// Before other integrations ( e.g. Bookings for WooCommerce at 10 ) that set their own store price.
		add_action( 'yaydp_after_initial_cart_item', array( $this, 'initialize_cart_item_store_price' ), 5, 2 );
		// Product Add-Ons Ultimate re-sets the price with extras at priority 10 on every totals pass, but YayPricing (110)
		// skips a pass when the cart is unchanged, so re-apply our price. Registered before the managers, so it runs
		// before the cart discount manager at the same priority.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'reapply_cart_item_price' ), 111 );
	}

	/**
	 * Re-apply YayPricing price to cart items with extras after Product Add-Ons Ultimate has overwritten it
	 *
	 * YayPricing clears its price from the cart item whenever it recalculates, so a price still saved here
	 * is the one of the current cart.
	 *
	 * @param \WC_Cart $cart Cart object.
	 */
	public function reapply_cart_item_price( $cart ) {
		if ( ! $cart instanceof \WC_Cart ) {
			$cart = \WC()->cart;
		}
		if ( empty( $cart ) ) {
			return;
		}
		// Not get_cart(): it can reload the cart from the session before it has been loaded this request.
		foreach ( $cart->get_cart_contents() as $cart_item ) {
			if ( empty( $cart_item['product_extras'] ) || ! isset( $cart_item['yaydp_custom_data']['price'] ) ) {
				continue;
			}
			$cart_item['data']->set_price( $cart_item['yaydp_custom_data']['price'] );
		}
	}

	/**
	 * Modifies the product total price for Product Addons Ultimate compatibility.
	 *
	 * This function ensures that when YayPricing discounts are applied,
	 * the Product Addons Ultimate plugin displays the correct discounted price
	 * instead of the original price.
	 *
	 * @param float $product_total The current product total price
	 * @param WC_Product $product The WooCommerce product object
	 * @return float The modified product total price with YayPricing discounts applied
	 */
	public function modify_product_total( $product_total, $product ) {
		if ( \yaydp_is_variable_product( $product ) ) {
			return $product_total;
		}
		
		$product_sale      = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$discounted_prices = $product_sale->get_min_max_discounted_price();

		if ( is_null( $discounted_prices ) ) {
			return $product_total;
		}

		$original_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		
		if ( $original_price === $discounted_prices['min'] && $original_price === $discounted_prices['max'] ) {
			return $product_total;
		}

		return $discounted_prices['min'];
	}

	public function modify_cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
		if ( empty( $cart_item['product_extras'] ) ) {
			return $subtotal;
		}

		if ( ! isset( $cart_item['yaydp_custom_data']['price'] ) ) {
			return $subtotal;
		}
 		$yaydp_cart_item    = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );
		$item_price         = $cart_item['yaydp_custom_data']['price'];
		$item_initial_price = $yaydp_cart_item->get_store_price();
		$item_quantity      = $cart_item['quantity'];

		$product  = $yaydp_cart_item->get_product();
		$subtotal = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $item_price,
				'qty'             => $item_quantity,
				'display_context' => 'cart',
			)
		);
		$subtotal = \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) );
		$html     = $subtotal;

		if ( ! \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_original_subtotal_price() ) {
			return $html;
		}

		$subtotal = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $item_initial_price,
				'qty'             => $item_quantity,
				'display_context' => 'cart',
			)
		);

		if ( $yaydp_cart_item->can_modify() ) {
			ob_start();
			?>
			<del><?php echo \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></del>
			<?php
			ob_end_clean();
			$html = '<div class="price">' . $html . '</div>';
		}

		return apply_filters( 'yaydp_cart_item_price_html', $html, $cart_item );
	}	

	/**
	 * Modifies the variation price data for variable products.
	 *
	 * This function ensures that variable products display the correct
	 * discounted prices when YayPricing discounts are applied, specifically
	 * for the Product Addons Ultimate integration.
	 *
	 * @param array $data The variation data array
	 * @param WC_Product_Variable $product The variable product object
	 * @param WC_Product_Variation $variation The product variation object
	 * @return array The modified variation data with updated display price
	 */
	public function modify_variation_price( $data, $product, $variation ) {

		if ( ! \yaydp_is_variable_product( $product ) ) {
			return $data;
		}

		$product_sale      = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$discounted_prices = $product_sale->get_min_max_discounted_price();

		if ( is_null( $discounted_prices ) ) {
			return $data;
		}

		$data['display_price'] = $discounted_prices['min'];

		return $data;
	}

	/**
	 * Add the extras to the cart item base price.
	 *
	 * Product Add-Ons Ultimate keeps the extras in the currency the customer is shopping in,
	 * YayPricing prices in the store currency.
	 *
	 * @param float $price Initial price.
	 * @param array $cart_item Cart item data.
	 */
	public function initialize_cart_item_price( $price, $cart_item ) {

		if ( ! isset( $cart_item['product_extras']['original_price'], $cart_item['product_extras']['price_with_extras'] ) ) {
			return $price;
		}

		$product_extras = $cart_item['product_extras'];
		$extras_total   = floatval( $product_extras['price_with_extras'] ) - floatval( $product_extras['original_price'] );

		return $price + self::get_price_in_store_currency( $extras_total, $product_extras );
	}

	/**
	 * Use the price with extras as the store price, the price cart discounts, fees and conditions
	 * are built on when no product pricing rule applies.
	 *
	 * The store price is read off the product, where Product Add-Ons Ultimate sets the price with extras
	 * in the currency the customer is shopping in.
	 *
	 * @param array                       $cart_item_data Cart item data.
	 * @param \YAYDP\Core\YAYDP_Cart_Item $cart_item YayPricing cart item.
	 */
	public function initialize_cart_item_store_price( $cart_item_data, $cart_item ) {
		if ( ! isset( $cart_item_data['product_extras']['price_with_extras'] ) || isset( $cart_item_data['custom_price'] ) ) {
			return;
		}
		$product_extras = $cart_item_data['product_extras'];
		$cart_item->set_store_price( self::get_price_in_store_currency( $product_extras['price_with_extras'], $product_extras ) );
	}

	/**
	 * Convert a price kept by Product Add-Ons Ultimate back to the store currency.
	 *
	 * @param float $price Price in the currency the item was priced in.
	 * @param array $product_extras Product extras of the cart item.
	 */
	public static function get_price_in_store_currency( $price, $product_extras ) {
		$price    = floatval( $price );
		$currency = self::get_currency_code( $product_extras );
		if ( '' === $currency || get_option( 'woocommerce_currency' ) === $currency ) {
			return $price;
		}
		return (float) apply_filters( 'yaydp_reversed_price', $price, $currency );
	}

	/**
	 * Currency the extras are priced in, as saved by YayCurrency ( older versions save an array ).
	 *
	 * @param array $product_extras Product extras of the cart item.
	 */
	public static function get_currency_code( $product_extras ) {
		$currency = isset( $product_extras['yay_currency'] ) ? $product_extras['yay_currency'] : '';
		if ( is_array( $currency ) ) {
			$currency = isset( $currency['currency_code'] ) ? $currency['currency_code'] : '';
		}
		return is_string( $currency ) ? $currency : '';
	}

	public static function init() {
		self::get_instance();
	}
}

YAYDP_WC_Product_Addons_Ultimate_Integration::init();