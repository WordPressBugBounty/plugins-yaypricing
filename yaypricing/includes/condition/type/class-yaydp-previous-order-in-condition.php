<?php
/**
 * Condition type: previous_order_in
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

class YAYDP_Previous_Order_In_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'previous_order_in';
	}

	public function label() {
		return __( 'Last order within', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Has previous order in such of time from the time they checkout.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_PURCHASE_HISTORY;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::pick( array( YAYDP_Comparators::GREATER_THAN, YAYDP_Comparators::LESS_THAN ) );
	}

	public function editor() {
		return array( 'kind' => 'days' );
	}

	public function default_value() {
		return 1;
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( ! $ctx->is_logged_in() ) {
			return false;
		}
		$days = max( 0, floatval( $condition['value'] ) );
		$args = array( 'status' => YAYDP_Order_History_Query::placed_statuses() ) + YAYDP_Order_History_Query::window_args( $days, $condition['comparation'] );
		return YAYDP_Order_History_Query::user_has_order( $ctx->user_id(), $args );
	}
}
