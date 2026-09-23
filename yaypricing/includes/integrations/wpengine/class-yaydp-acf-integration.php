<?php
/**
 * Offers product taxonomies registered through ACF as product filters.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WPEngine;

use YAYDP\Product_Filter\YAYDP_Taxonomy_Product_Filter;

defined( 'ABSPATH' ) || exit;

class YAYDP_ACF_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'ACF' ) ) {
			return;
		}
		add_action( 'yaydp_register_product_filters', array( $this, 'register_product_filters' ) );
	}

	/**
	 * Register one filter per ACF product taxonomy.
	 *
	 * @param \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry $registry Registry.
	 */
	public function register_product_filters( $registry ) {
		if ( ! class_exists( 'ACF' ) ) {
			return;
		}
		foreach ( $this->get_taxonomies() as $taxonomy ) {
			$registry->register( new YAYDP_Taxonomy_Product_Filter( $taxonomy->name, $taxonomy->label ) );
		}
	}

	/**
	 * Taxonomy objects ACF registered for products.
	 *
	 * @return \WP_Taxonomy[]
	 */
	public function get_taxonomies() {
		$cache_key = '';
		if ( function_exists( 'acf_cache_key' ) ) {
			$cache_key = \acf_cache_key( 'acf_get_taxonomy_posts' );
		}
		$post_ids = wp_cache_get( $cache_key, 'acf' ); // TODO: Do we need to change the group at all?

		if ( $post_ids === false ) {

			$post_ids = array();

		}

		$return = array();

		foreach ( $post_ids as $post_id ) {
			$post          = get_post( $post_id );
			$taxonomy_info = $post->post_content;
			if ( function_exists( 'acf_maybe_unserialize' ) ) {
				$taxonomy_info = acf_maybe_unserialize( $post->post_content );
			}
			$return[] = get_taxonomy( $taxonomy_info['taxonomy'] );
		}

		return $return;

	}
}
