<?php
/**
 * Condition type: orders_purchased_date
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

class YAYDP_Orders_Purchased_Date_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'orders_purchased_date';
	}

	public function label() {
		return __( 'Previous purchase date', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Purchase date of orders placed in the past.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_PURCHASE_HISTORY;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::date();
	}

	public function editor() {
		return array( 'kind' => 'date' );
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( ! $ctx->is_logged_in() ) {
			return false;
		}
		return YAYDP_Order_History_Query::user_has_order( $ctx->user_id(), YAYDP_Comparators::order_date_args( $condition ) );
	}
}
