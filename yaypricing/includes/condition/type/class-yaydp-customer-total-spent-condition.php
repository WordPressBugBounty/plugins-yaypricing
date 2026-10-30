<?php
/**
 * Condition type: customer_total_spent
 *
 * @package YayPricing\Condition\Type
 * @since 3.6.2
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Customer_Total_Spent_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'customer_total_spent';
	}

	public function label() {
		return __( 'Customer total spent', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Total amount of all the paid orders they have placed in the past.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_PURCHASE_HISTORY;
	}

	public function comparators() {
		return YAYDP_Comparators::numeric();
	}

	public function editor() {
		return array(
			'kind'   => 'number',
			'suffix' => 'currency',
		);
	}

	public function default_value() {
		return 1000;
	}

	/**
	 * WooCommerce's own "Total spend" (paid order totals, cached in user meta),
	 * so the lookup stays cheap on archive pages.
	 */
	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( ! $ctx->is_logged_in() || ! function_exists( 'wc_get_customer_total_spent' ) ) {
			return false;
		}
		$spent = (float) \wc_get_customer_total_spent( $ctx->user_id() );
		return YAYDP_Comparators::compare_numeric( $spent, $condition );
	}
}
