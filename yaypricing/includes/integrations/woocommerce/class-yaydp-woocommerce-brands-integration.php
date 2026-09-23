<?php
/**
 * Offers the WooCommerce Brands taxonomy as a product filter.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WooCommerce;

use YAYDP\Product_Filter\YAYDP_Taxonomy_Product_Filter;

defined( 'ABSPATH' ) || exit;

class YAYDP_WooCommerce_Brands_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'WC_Brands' ) ) {
			return;
		}
		add_action( 'yaydp_register_product_filters', array( $this, 'register_product_filters' ) );
	}

	/**
	 * Register the brand filter.
	 *
	 * @param \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry $registry Registry.
	 */
	public function register_product_filters( $registry ) {
		if ( class_exists( 'WC_Brands' ) ) {
			$registry->register( new YAYDP_Taxonomy_Product_Filter( 'product_brand', __( 'WooCommerce Brands', 'yaypricing' ) ) );
		}
	}
}
