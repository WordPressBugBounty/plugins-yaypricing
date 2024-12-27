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
class YAYDP_YITH_Product_Bundles_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'yith_wcpb_pr_init' ) && ! function_exists( 'yith_wcpb_install' ) ) {
			return;
		}

		add_filter( 'yaydp_init_cart_items', array( $this, 'remove_initial_bundled_items' ) );
	}

	public function remove_initial_bundled_items( $items ) {
		foreach ( $items as $key => $item ) {
			if ( ! empty( $item['bundled_by'] ) ) {
				unset( $items[ $key ] );
			}
		}
		return $items;
	}
}
