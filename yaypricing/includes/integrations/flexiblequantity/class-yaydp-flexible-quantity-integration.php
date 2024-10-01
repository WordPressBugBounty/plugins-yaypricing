<?php
/**
 * Handles the integration of Flexible Quantity plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\FlexibleQuantity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Flexible_Quantity_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initial_cart_item_price' ), 100, 2 );
	}
	public function initial_cart_item_price( $initial_price, $cart_item_data ) {
		if ( is_array( $cart_item_data ) && isset( $cart_item_data['pricing_item_meta_data'] ) && isset( $cart_item_data['pricing_item_meta_data']['_price'] ) ) {
			$initial_price = $cart_item_data['pricing_item_meta_data']['_price'];
		}
		return $initial_price;
	}
}
