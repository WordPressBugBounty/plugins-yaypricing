<?php
/**
 * Condition type: order_history_category
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;
use YAYDP\Condition\YAYDP_Order_History_Query;

defined( 'ABSPATH' ) || exit;

class YAYDP_Order_History_Category_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'order_history_category';
	}

	public function label() {
		return __( 'Past order - category', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Purchased product categories in a specific order.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_PURCHASE_HISTORY;
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
			'source' => 'product_categories',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		return $ctx->is_logged_in() && YAYDP_Order_History_Query::any_order_contains_categories( $ctx->user_id(), $condition );
	}
}
