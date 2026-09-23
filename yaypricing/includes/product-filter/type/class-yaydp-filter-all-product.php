<?php
/**
 * Product filter type: all_product
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_All_Product extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'all_product';
	}

	public function label() {
		return __( 'All products', 'yaypricing' );
	}

	public function comparators() {
		return array();
	}

	public function editor() {
		return array( 'kind' => 'none' );
	}

	public function default_value() {
		return array();
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		return true;
	}
}
