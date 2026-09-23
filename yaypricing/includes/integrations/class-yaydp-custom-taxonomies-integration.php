<?php
/**
 * Offers every other product taxonomy (not category, tag, type or attributes) as a product filter, unless a dedicated integration already did.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations;

use YAYDP\Product_Filter\YAYDP_Taxonomy_Product_Filter;

defined( 'ABSPATH' ) || exit;

class YAYDP_Custom_Taxonomies_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		// Last, so dedicated integrations (brands, ACF, CPT UI) win their slugs.
		add_action( 'yaydp_register_product_filters', array( $this, 'register_product_filters' ), PHP_INT_MAX );
	}

	/**
	 * Register one filter per remaining product taxonomy.
	 *
	 * @param \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry $registry Registry.
	 */
	public function register_product_filters( $registry ) {
		foreach ( $this->get_custom_taxonomies() as $slug ) {
			if ( $registry->has( $slug ) ) {
				continue;
			}
			$taxonomy = get_taxonomy( $slug );
			$registry->register( new YAYDP_Taxonomy_Product_Filter( $slug, $taxonomy ? $taxonomy->label : $slug, true ) );
		}
	}

	/**
	 * Product taxonomy slugs that are not WooCommerce's own.
	 *
	 * @return string[]
	 */
	public function get_custom_taxonomies() {
		$product_taxonomies = get_taxonomies(
			array(
				'object_type' => array( 'product' ),
			)
		);

		$custom_taxonomies = array_filter(
			$product_taxonomies,
			function( $item ) {
				return ! in_array( $item, array( 'product_type', 'product_cat', 'product_tag' ) ) && ! str_starts_with( $item, 'pa_' );
			}
		);
		return $custom_taxonomies;
	}
}
