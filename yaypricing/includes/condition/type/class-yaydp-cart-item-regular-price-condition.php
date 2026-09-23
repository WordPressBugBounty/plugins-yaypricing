<?php
/**
 * Condition type: cart_item_regular_price
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Cart_Item_Regular_Price_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'cart_item_regular_price';
	}

	public function label() {
		return __( 'Cart item - regular price', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CART_ITEMS;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
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

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$count = 0;
		foreach ( $ctx->cart_items() as $item ) {
			if ( ! $item->is_sale_product() ) {
				$count += $item->get_quantity();
			}
		}
		return YAYDP_Comparators::compare_numeric( $count, $condition );
	}
}
