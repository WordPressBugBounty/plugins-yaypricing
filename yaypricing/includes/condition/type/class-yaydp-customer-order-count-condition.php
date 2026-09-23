<?php
/**
 * Condition type: customer_order_count
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

class YAYDP_Customer_Order_Count_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	// Named in notice renderers and the priority table, but no notice is emitted (historical).
	const INCOMPLETE_KEY = 'customer_order_count';

	public function slug() {
		return 'customer_order_count';
	}

	public function label() {
		return __( 'Customer order count', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Number of all the orders they have placed in the past.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_PURCHASE_HISTORY;
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
		if ( ! $ctx->is_logged_in() ) {
			return false;
		}
		$count = YAYDP_Order_History_Query::count_paid_orders( array( 'customer_id' => $ctx->user_id() ) );
		return YAYDP_Comparators::compare_numeric( $count, $condition );
	}

	public function incomplete_priority() {
		return 2;
	}
}
