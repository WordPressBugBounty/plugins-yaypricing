<?php
/**
 * Offers product taxonomies registered through Custom Post Type UI as product filters.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WebDevStudios;

use YAYDP\Product_Filter\YAYDP_Taxonomy_Product_Filter;

defined( 'ABSPATH' ) || exit;

class YAYDP_CPT_UI_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'cptui_init' ) ) {
			return;
		}
		add_action( 'yaydp_register_product_filters', array( $this, 'register_product_filters' ) );
	}

	/**
	 * Register one filter per CPT UI product taxonomy.
	 *
	 * @param \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry $registry Registry.
	 */
	public function register_product_filters( $registry ) {
		foreach ( $this->get_cptui_product_taxonomies() as $slug => $data ) {
			$registry->register( new YAYDP_Taxonomy_Product_Filter( $slug, $data['label'] ) );
		}
	}

	/**
	 * CPT UI taxonomy definitions attached to products, keyed by slug.
	 *
	 * @return array
	 */
	public function get_cptui_product_taxonomies() {
		if ( function_exists( 'cptui_get_taxonomy_data' ) ) {
			$taxonomies         = \cptui_get_taxonomy_data();
			$product_taxonomies = array();
			foreach ( $taxonomies as $key => $value ) {
				if ( ! isset( $value['object_types'] ) ) {
					continue;
				}
				if ( in_array( 'product', $value['object_types'], true ) ) {
					$product_taxonomies[ $key ] = $value;
				}
			}
			return $product_taxonomies;
		}
		return array();
	}
}
