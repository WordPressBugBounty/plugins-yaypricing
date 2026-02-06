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
			<del><?php echo \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) ); ?></del>
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

	public function initialize_cart_item_price( $price, $cart_item ) {

		if ( ! isset( $cart_item['product_extras'] ) ) {
			return $price;
		}

		$product_extras    = $cart_item['product_extras'];
		$original_price    = $product_extras['original_price'];
		$price_with_extras = $product_extras['price_with_extras'];
		$extras_total      = $price_with_extras - $original_price;

		return $price + $extras_total;
	}

	public static function init() {
		self::get_instance();
	}
}

YAYDP_WC_Product_Addons_Ultimate_Integration::init();