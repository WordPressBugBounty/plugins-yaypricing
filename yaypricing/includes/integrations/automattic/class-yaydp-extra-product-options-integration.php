<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Automattic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Extra_Product_Options_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'Themecomplete_Extra_Product_Options_Setup' ) ) {
			return;
		}
		add_action( 'yaydp_after_initial_cart_item', array( $this, 'initialize_extra_options' ), 100, 2 );
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 100, 2 );
	}

	public function initialize_extra_options( $cart_item, $yaydp_cart_item_instance ) {
		if ( ! empty( $cart_item['tmcartepo'] ) ) {
			$yaydp_cart_item_instance->regardless_extra_options = true;
		}
	}

	public function initialize_cart_item_price( $price, $cart_item ) {
		if ( empty( $cart_item['tmcartepo'] ) ) {
			return $price;
		}
		return floatval( $cart_item['tm_epo_product_price_with_options'] ?? $price );
	}
}
