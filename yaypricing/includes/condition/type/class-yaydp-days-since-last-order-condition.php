<?php
/**
 * Condition type: days_since_last_order
 *
 * Unlike `previous_order_in` (any order on that side of the window), this
 * measures only the customer's most recent order — "greater than 365" means
 * they have not ordered for over a year.
 *
 * @package YayPricing\Condition\Type
 * @since 3.6.0
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;
use YAYDP\Condition\YAYDP_Order_History_Query;

defined( 'ABSPATH' ) || exit;

class YAYDP_Days_Since_Last_Order_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'days_since_last_order';
	}

	public function label() {
		return __( 'Days since last order', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Days since the customer\'s most recent order. Works for guests once they enter their billing email at checkout. Customers with no previous order never match.', 'yaypricing' );
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
		return array( 'kind' => 'days' );
	}

	public function default_value() {
		return 30;
	}

	public function frontend_requirements() {
		return array( 'billing_email' );
	}

	/**
	 * Logged-in customers are looked up by account; guests by the billing
	 * email entered at checkout (no email yet → no match).
	 */
	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( $ctx->is_logged_in() ) {
			$lookup = array( 'customer_id' => $ctx->user_id() );
		} else {
			$email = $ctx->billing_email();
			if ( empty( $email ) ) {
				return false;
			}
			$lookup = array( 'billing_email' => $email );
		}
		$last_order_date = YAYDP_Order_History_Query::last_order_date( $lookup );
		if ( is_null( $last_order_date ) ) {
			return false;
		}
		$days = ( time() - $last_order_date->getTimestamp() ) / DAY_IN_SECONDS;
		return YAYDP_Comparators::compare_numeric( $days, $condition );
	}
}
