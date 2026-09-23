<?php
/**
 * Condition type: cart_item_tag
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

class YAYDP_Cart_Item_Tag_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'cart_item_tag';
	}

	public function label() {
		return __( 'Cart item - tag', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CART_ITEMS;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::contain();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'product_tags',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$cart_tags = array();
		foreach ( $ctx->cart_items() as $item ) {
			$cart_tags = array_merge( $cart_tags, YAYDP_Cart_Item_Matcher::tag_ids( $item ) );
		}
		$tags      = YAYDP_Cart_Item_Matcher::translated_ids( $condition, 'product_tag' );
		$intersect = array_intersect( array_unique( $cart_tags ), $tags );
		return YAYDP_Comparators::matches_contain( $intersect, count( $tags ), $condition );
	}
}
