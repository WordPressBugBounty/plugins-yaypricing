<?php
/**
 * Product filter type: cart_item_price_criterion
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Price_Criterion;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Cart_Item_Price_Criterion extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'cart_item_price_criterion';
	}

	public function label() {
		return __( 'Cart item by price rank', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::pick(
			array(
				'highest_price'        => __( 'Highest-price cart item', 'yaypricing' ),
				'second_highest_price' => __( 'Second-highest-price cart item', 'yaypricing' ),
				'third_highest_price'  => __( 'Third-highest-price cart item', 'yaypricing' ),
				'lowest_price'         => __( 'Lowest-price cart item', 'yaypricing' ),
				'second_lowest_price'  => __( 'Second-lowest-price cart item', 'yaypricing' ),
				'third_lowest_price'   => __(
					'Third-lowest-price cart item',
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

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		return YAYDP_Price_Criterion::cart_item_matches( $product, $filter['comparation'], $ctx );
	}
}
