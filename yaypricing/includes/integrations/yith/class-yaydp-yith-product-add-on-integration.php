<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\YITH;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_YITH_Product_Add_On_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'yith_wapo_init' ) ) {
			return;
		}
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 100, 2 );
	}

	public function initialize_cart_item_price( $price, $cart_item ) {
		if ( isset( $cart_item['yith_wapo_total_options_price'] ) ) {
			return $price + $cart_item['yith_wapo_total_options_price'];
		}
		return $price;
	}
}
