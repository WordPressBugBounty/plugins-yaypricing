<?php
/**
 * Product filter type: sub_filter_product_price_criterion
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Sub_Filter_Product_Price_Criterion extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'sub_filter_product_price_criterion';
	}

	public function label() {
		return __( 'Matched item by price rank', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::pick(
			array(
				'highest_price'        => __( 'Highest-price item', 'yaypricing' ),
				'second_highest_price' => __( 'Second-highest-price item', 'yaypricing' ),
				'third_highest_price'  => __( 'Third-highest-price item', 'yaypricing' ),
				'lowest_price'         => __( 'Lowest-price item', 'yaypricing' ),
				'second_lowest_price'  => __( 'Second-lowest-price item', 'yaypricing' ),
				'third_lowest_price'   => __(
					'Third-lowest-price item',
					'yaypricing'
				),
			)
		);
	}

	public function editor() {
		return array( 'kind' => 'none' );
	}

	public function default_value() {
		return array();
	}

	/**
	 * Never matches on its own: the registry reads it off the list and hands
	 * it to the product / variation / category filters as the narrowing rule.
	 */
	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		return false;
	}
}
