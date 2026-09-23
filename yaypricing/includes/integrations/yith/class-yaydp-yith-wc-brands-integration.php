<?php
/**
 * Offers the YITH WooCommerce Brands taxonomy as a product filter.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\YITH;

use YAYDP\Product_Filter\YAYDP_Taxonomy_Product_Filter;

defined( 'ABSPATH' ) || exit;

class YAYDP_YITH_WC_Brands_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'yith_brands_install' ) ) {
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
		if ( class_exists( 'YITH_WCBR' ) ) {
			$registry->register( new YAYDP_Taxonomy_Product_Filter( \YITH_WCBR::$brands_taxonomy, __( 'YITH Brands', 'yaypricing' ) ) );
		}
	}
}
