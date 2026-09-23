<?php
/**
 * Product filter type: product_category
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Price_Criterion;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Product_Category extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'product_category';
	}

	public function label() {
		return __( 'Product category', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'product_categories',
		);
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$listed = apply_filters( 'yaydp_translated_list_object_id', \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter ), 'product_cat' );
		$listed = YAYDP_Price_Criterion::narrow_category_ids( $listed, $product, $ctx->sub_filter() );
		$cats   = \YAYDP\Helper\YAYDP_Product_Helper::get_product_cats( $product );
		return YAYDP_Comparators::matches_list( ! empty( array_intersect( $cats, $listed ) ), $filter );
	}
}
