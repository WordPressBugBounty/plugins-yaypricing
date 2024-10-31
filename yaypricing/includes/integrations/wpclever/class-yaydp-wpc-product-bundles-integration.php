<?php
/**
 * Handles the integration of WPClever Product Bundles plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WPClever;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_WPC_Product_Bundles_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'woosb_init' ) ) {
			return;
		}

		add_filter( 'yaydp_init_cart_items', array( $this, 'remove_initial_bundled_items' ) );
	}

	public function remove_initial_bundled_items( $items ) {
		foreach ( $items as $key => $item ) {
			if ( ! empty( $item['woosb_parent_id'] ) ) {
				unset( $items[$key] );
			}
		}
		return $items;
	}
}
