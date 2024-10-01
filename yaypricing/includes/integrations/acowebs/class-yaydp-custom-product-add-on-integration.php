<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Acowebs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Custom_Product_Add_On_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'WCPA_VERSION' ) ) {
			return;
		}
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 100, 2 );
	}

	public function initialize_cart_item_price( $price, $cart_item ) {
		if ( isset( $cart_item['wcpa_price']['addon'] ) ) {
			$price = $price + $cart_item['wcpa_price']['addon'];
		}
		if ( isset( $cart_item['wcpa_price']['excludeDiscount'] ) ) {
			$price = $price - $cart_item['wcpa_price']['excludeDiscount'];
		}
		return $price;
	}
}
