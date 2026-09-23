<?php
/**
 * Attribute lookups shared by the attribute product filters.
 *
 * @package YayPricing\Product_Filter
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Product_Attributes {

	/**
	 * A product's attributes keyed by decoded taxonomy/name. A variation also
	 * carries its parent's visible, non-variation attributes.
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	public static function of( $product ) {
		$attributes = array();
		foreach ( $product->get_attributes() as $taxonomy => $attribute ) {
			$key                = preg_match( '/%[0-9a-fA-F]{2}/', $taxonomy ) ? urldecode( $taxonomy ) : $taxonomy;
			$attributes[ $key ] = $attribute;
		}
		if ( \yaydp_is_variation_product( $product ) ) {
			$parent = \wc_get_product( $product->get_parent_id() );
			if ( $parent ) {
				foreach ( $parent->get_attributes() as $attribute ) {
					if ( $attribute instanceof \WC_Product_Attribute && $attribute['visible'] && ! $attribute['variation'] ) {
						$attributes[ $attribute['name'] ] = $attribute;
					}
				}
			}
		}
		return $attributes;
	}

	/**
	 * Whether a taxonomy attribute lists a term with the given slug.
	 *
	 * @param mixed  $attribute Product attribute (object for taxonomy attributes, string for custom ones).
	 * @param string $slug      Term slug.
	 * @return bool
	 */
	public static function has_term_slug( $attribute, $slug ) {
		if ( ! $attribute instanceof \WC_Product_Attribute ) {
			return false;
		}
		foreach ( $attribute->get_options() as $term_id ) {
			$term = get_term( $term_id );
			if ( $term && ! is_wp_error( $term ) && $term->slug === $slug ) {
				return true;
			}
		}
		return false;
	}
}
