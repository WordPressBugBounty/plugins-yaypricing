<?php
/**
 * Product filter type: product_attribute
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Attributes;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Product_Attribute extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'product_attribute';
	}

	public function label() {
		return __( 'Attribute term', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'product_attributes',
		);
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		return YAYDP_Comparators::matches_list( $this->has_term( $product, $filter, $ctx ), $filter );
	}

	/**
	 * A listed attribute term is set on the product (taxonomy attribute option
	 * or custom attribute text), or chosen on the evaluated cart line.
	 *
	 * @param \WC_Product                  $product Product.
	 * @param array                        $filter  Stored filter.
	 * @param YAYDP_Product_Filter_Context $ctx     Context.
	 * @return bool
	 */
	private function has_term( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$wanted = array();
		foreach ( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter ) as $term_id ) {
			$term = get_term( $term_id );
			if ( $term && ! is_wp_error( $term ) ) {
				$wanted[] = array( $term->taxonomy, $term->slug );
			}
		}
		$attributes = YAYDP_Product_Attributes::of( $product );
		$variation  = $ctx->cart_item_variation();
		foreach ( $wanted as list( $taxonomy, $slug ) ) {
			$attribute = isset( $attributes[ $taxonomy ] ) ? $attributes[ $taxonomy ] : null;
			if ( YAYDP_Product_Attributes::has_term_slug( $attribute, $slug ) ) {
				return true;
			}
			if ( ! is_null( $attribute ) && $slug === $attribute ) {
				return true;
			}
			if ( isset( $variation[ 'attribute_' . $taxonomy ] ) && $slug === $variation[ 'attribute_' . $taxonomy ] ) {
				return true;
			}
		}
		return false;
	}
}
