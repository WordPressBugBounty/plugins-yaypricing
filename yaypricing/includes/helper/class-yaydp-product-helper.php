<?php
/**
 * YayPricing product helper
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Product_Helper {

	/**
	 * Retrieves all categories associated with a given product, including parent categories
	 *
	 * @since 2.2
	 * @param \WC_Product $product given product.
	 *
	 * @return array
	 */
	public static function get_product_cats( $product ) {
		// Resolving a product's categories (plus ancestors, plus the parent
		// product's categories for variations) is pure taxonomy work repeated for
		// every rule check on every variation. Memoize per product for the request.
		$cache     = \YAYDP\Core\Caches\YAYDP_Request_Cache::get_instance();
		$cache_key = 'cat:' . $product->get_id();
		if ( $cache->has( 'taxonomy', $cache_key ) ) {
			return $cache->get( 'taxonomy', $cache_key );
		}
		$result       = array();
		// Variations are not registered for product_cat/product_tag, so asking for
		// their terms is a guaranteed-empty query per variation. The parent product
		// below supplies the terms. Still ask when a third party did register the
		// taxonomy for variations.
		$post_type    = \get_post_type( $product->get_id() );
		$product_cats = \is_object_in_taxonomy( $post_type, 'product_cat' ) ? \get_the_terms( $product->get_id(), 'product_cat' ) : false;
		$product_cat_ids = array_map(
			function( $item ) {
				return $item->term_id;
			},
			$product_cats ? $product_cats : array()
		);
		foreach ( $product_cat_ids as $cat_id ) {
			$result[]    = $cat_id;
			$cat_parents = get_ancestors( $cat_id, 'product_cat' );
			$result      = array_merge( $result, $cat_parents );
		}
		$product_parent_id = $product->get_parent_id();
		if ( ! empty( $product_parent_id ) ) {
			$parent_product = \wc_get_product( $product_parent_id );
			$result         = array_merge( $result, self::get_product_cats( $parent_product ) );
		}
		return $cache->set( 'taxonomy', $cache_key, array_unique( $result ) );
	}

	/**
	 * Retrieves all tags associated with a given product, including parent tags
	 *
	 * @since 2.2
	 * @param \WC_Product $product given product.
	 *
	 * @return array
	 */
	public static function get_product_tags( $product ) {
		// Same rationale as get_product_cats: memoize the per-product tag walk
		// (including ancestors and parent product) for the request.
		$cache     = \YAYDP\Core\Caches\YAYDP_Request_Cache::get_instance();
		$cache_key = 'tag:' . $product->get_id();
		if ( $cache->has( 'taxonomy', $cache_key ) ) {
			return $cache->get( 'taxonomy', $cache_key );
		}
		$result       = array();
		// Variations are not registered for product_cat/product_tag, so asking for
		// their terms is a guaranteed-empty query per variation. The parent product
		// below supplies the terms. Still ask when a third party did register the
		// taxonomy for variations.
		$post_type    = \get_post_type( $product->get_id() );
		$product_cats = \is_object_in_taxonomy( $post_type, 'product_tag' ) ? \get_the_terms( $product->get_id(), 'product_tag' ) : false;
		$product_cat_ids = array_map(
			function( $item ) {
				return $item->term_id;
			},
			$product_cats ? $product_cats : array()
		);
		foreach ( $product_cat_ids as $cat_id ) {
			$result[]    = $cat_id;
			$cat_parents = get_ancestors( $cat_id, 'product_tag' );
			$result      = array_merge( $result, $cat_parents );
		}
		$product_parent_id = $product->get_parent_id();
		if ( ! empty( $product_parent_id ) ) {
			$parent_product = \wc_get_product( $product_parent_id );
			$result         = array_merge( $result, self::get_product_tags( $parent_product ) );
		}
		return $cache->set( 'taxonomy', $cache_key, array_unique( $result ) );
	}
}
