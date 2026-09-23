<?php
/**
 * Product filter type: product_variation
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Price_Criterion;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Product_Variation extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'product_variation';
	}

	public function label() {
		return __( 'Product variation', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'product_variations',
		);
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$ids = array( $product->get_id() );
		if ( \yaydp_is_variable_product( $product ) || \yaydp_is_grouped_product( $product ) ) {
			$ids = array_merge( $ids, $product->get_children() );
		}
		$listed = apply_filters( 'yaydp_translated_list_object_id', \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter ), 'product' );
		$listed = YAYDP_Price_Criterion::narrow_product_ids( $listed, $ctx->sub_filter() );
		return YAYDP_Comparators::matches_list( ! empty( array_intersect( $ids, $listed ) ), $filter );
	}
}
