<?php
/**
 * Product filter type: product_in_stock
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Product_In_Stock extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'product_in_stock';
	}

	public function label() {
		return __( 'Stock quantity', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::numeric();
	}

	public function editor() {
		return array( 'kind' => 'number' );
	}

	public function default_value() {
		return 100;
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		return YAYDP_Comparators::compare_numeric( \yaydp_get_stock_quantity( $product ), $filter );
	}
}
