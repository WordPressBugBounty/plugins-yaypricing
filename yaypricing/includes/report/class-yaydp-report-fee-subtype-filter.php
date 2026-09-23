<?php
/**
 * Split checkout fee rows into the money they earn and the money they cost.
 *
 * Checkout fee rules come in three kinds. Add Custom Fee and Add Custom Fee
 * based on Shipping Fee charge the customer, so they are income. Reduce
 * Shipping Fee gives shipping away, so it is a cost like any other discount.
 * Netting the two against each other hides both, which is why every report
 * figure is built from one side or the other rather than from the total.
 *
 * The split is resolved from the rule ids rather than from a stored column, so
 * it applies to rows that were written before the distinction existed.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Fee_Subtype_Filter {

	/**
	 * Checkout fee rule types that charge the customer.
	 */
	const INCOME_TYPES = array( 'custom_fee', 'custom_shipping_fee' );

	/**
	 * Cached ids of the rules that charge the customer.
	 *
	 * @var null|array
	 */
	private static $income_rule_ids = null;

	/**
	 * Ids of every checkout fee rule that charges the customer, deleted ones
	 * included, so past orders keep being classified the same way.
	 */
	public static function get_income_rule_ids() {
		if ( ! is_null( self::$income_rule_ids ) ) {
			return self::$income_rule_ids;
		}

		$rules                 = \YAYDP\API\Models\YAYDP_Report_Model::get_all_checkout_fee_rules();
		self::$income_rule_ids = array();
		foreach ( (array) $rules as $rule ) {
			if ( empty( $rule['id'] ) || empty( $rule['type'] ) ) {
				continue;
			}
			if ( in_array( $rule['type'], self::INCOME_TYPES, true ) ) {
				self::$income_rule_ids[] = (string) $rule['id'];
			}
		}
		self::$income_rule_ids = array_values( array_unique( self::$income_rule_ids ) );
		return self::$income_rule_ids;
	}

	/**
	 * Prepared condition matching the rows that charge the customer.
	 *
	 * Returns a condition that never matches when the store has no such rule,
	 * so callers can always interpolate it without a special case.
	 */
	public static function get_income_condition() {
		global $wpdb;

		$rule_ids = self::get_income_rule_ids();
		if ( empty( $rule_ids ) ) {
			return '1 = 0';
		}
		$placeholders = implode( ', ', array_fill( 0, count( $rule_ids ), '%s' ) );
		return $wpdb->prepare( "rule_id IN ({$placeholders})", $rule_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Drop the cache. Used by long running batches that may outlive a rule edit.
	 */
	public static function flush() {
		self::$income_rule_ids = null;
	}
}
