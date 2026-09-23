<?php
/**
 * Product filter type: product_specific_attributes
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Attributes;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Product_Specific_Attributes extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'product_specific_attributes';
	}

	public function label() {
		return __( 'Custom product attribute', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'product_specific_attributes',
		);
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}
		$selected   = \YAYDP\Helper\YAYDP_Helper::separate_attribute_option( array( 'title' => $filter['value'] ) );
		$attributes = YAYDP_Product_Attributes::of( $product );
		$variation  = $ctx->cart_item_variation();
		$in_list    = false;
		foreach ( $selected as $pair ) {
			$name = strtolower( $pair['attribute'] );
			if ( isset( $attributes[ $name ] ) && $pair['option'] === $attributes[ $name ] ) {
				$in_list = true;
				break;
			}
			if ( isset( $variation[ 'attribute_' . $name ] ) && $pair['option'] === $variation[ 'attribute_' . $name ] ) {
				$in_list = true;
				break;
			}
		}
		return YAYDP_Comparators::matches_list( $in_list, $filter );
	}
}
