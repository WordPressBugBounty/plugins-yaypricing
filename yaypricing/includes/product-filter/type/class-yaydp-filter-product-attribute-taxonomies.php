<?php
/**
 * Product filter type: product_attribute_taxonomies
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Attributes;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Product_Attribute_Taxonomies extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'product_attribute_taxonomies';
	}

	public function label() {
		return __( 'Attribute (any term)', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'product_attribute_taxonomies',
		);
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$keys = array_keys( YAYDP_Product_Attributes::of( $product ) );
		return YAYDP_Comparators::matches_list( ! empty( array_intersect( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter ), $keys ) ), $filter );
	}
}
