<?php
/**
 * Condition type: billing_email_order_count
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

class YAYDP_Billing_Email_Order_Count_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'billing_email_order_count';
	}

	public function label() {
		return __( 'Billing email order count', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Number of completed and processing orders placed with the billing email of the current order. Works for guests once they enter their email at checkout.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_PURCHASE_HISTORY;
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
		return 1;
	}

	public function frontend_requirements() {
		return array( 'billing_email' );
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		try {
			$email = $ctx->billing_email();
			if ( empty( $email ) ) {
				return false;
			}
			$count = YAYDP_Order_History_Query::count_paid_orders( array( 'billing_email' => $email ) );
			return YAYDP_Comparators::compare_numeric( $count, $condition );
		} catch ( \Exception $e ) {
			return false;
		}
	}
}
