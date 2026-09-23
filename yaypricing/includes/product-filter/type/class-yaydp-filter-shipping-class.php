<?php
/**
 * Product filter type: shipping_class
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Shipping_Class extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'shipping_class';
	}

	public function label() {
		return __( 'Shipping class', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'shipping_classes',
		);
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$in_list = in_array( $product->get_shipping_class_id(), \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter ), true );
		return YAYDP_Comparators::matches_list( $in_list, $filter );
	}
}
