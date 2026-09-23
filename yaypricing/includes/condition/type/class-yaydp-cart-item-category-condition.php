<?php
/**
 * Condition type: cart_item_category
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Cart_Item_Matcher;
use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Cart_Item_Category_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'cart_item_category';
	}

	public function label() {
		return __( 'Cart item - category', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CART_ITEMS;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::pick( array( YAYDP_Comparators::CONTAIN_ALL, YAYDP_Comparators::CONTAIN, YAYDP_Comparators::CONTAIN_ONLY, YAYDP_Comparators::NOT_CONTAIN ) );
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'product_categories',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		return ! empty( YAYDP_Cart_Item_Matcher::by_category( $ctx->cart_items(), $condition ) );
	}
}
