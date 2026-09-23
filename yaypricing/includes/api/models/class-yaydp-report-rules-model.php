<?php
/**
 * Per-rule report figures.
 *
 * @package YayPricing\Models
 */

namespace YAYDP\API\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Rules_Model {

	/**
	 * Orders, revenue, discount and return per rule.
	 *
	 * Revenue is credited in full to every rule that took part in an order, so
	 * the figures describe influenced revenue and their total legitimately
	 * exceeds the store revenue. The report has to say so.
	 *
	 * @param string $range_type Type of time range.
	 * @param array  $from       Start parts.
	 * @param array  $to         End parts.
	 */
	public static function get( $range_type, $from, $to ) {
		global $wpdb;

		list( $start, $end ) = \YAYDP\Report\YAYDP_Report_Query::get_range( $range_type, $from, $to );

		$table = \YAYDP\Report\YAYDP_Report_Install::get_table_name();
		$where = \YAYDP\Report\YAYDP_Report_Query::get_where( $start, $end );

		/**
		 * A rule appears at most once per order, so revenue can be summed
		 * directly here. That is not true of the time series, where several
		 * rules of one order fall into the same bucket.
		 */
		$sql = "SELECT rule_id,
				MAX(rule_type) AS rule_type,
				MAX(rule_name) AS rule_name,
				COUNT(DISTINCT order_id) AS orders,
				SUM(order_revenue) AS revenue,
				SUM(COALESCE(discount_amount, 0)) AS discount,
				SUM(CASE WHEN discount_amount IS NULL THEN 1 ELSE 0 END) AS unknown_rows
			FROM {$table}
			WHERE {$where}
			GROUP BY rule_id";

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

		$rows = array();
		foreach ( (array) $results as $result ) {
			$discount = (float) $result->discount;
			$revenue  = (float) $result->revenue;
			$rows[]   = array(
				'rule_id'     => $result->rule_id,
				'type'        => $result->rule_type,
				'name'        => '' !== $result->rule_name ? $result->rule_name : __( 'Deleted rule', 'yaypricing' ),
				'orders'      => (int) $result->orders,
				'revenue'     => $revenue,
				'discount'    => $discount,
				'roi'         => self::get_roi( $revenue, $discount ),
				'has_unknown' => $result->unknown_rows > 0,
			);
		}
		return $rows;
	}

	/**
	 * Orders, revenue, discount, fee income and return per rule type.
	 *
	 * Rules that charge the customer are summed as fee income instead of being
	 * netted against the discount, so a type that both charges and discounts
	 * reports each side of that at its true size, and its return is measured
	 * against what it actually gave away.
	 *
	 * Not derivable from the per-rule figures: an order using two rules of the
	 * same type would be counted, and its revenue credited, twice. The rows are
	 * reduced to one per order and type first.
	 *
	 * Types that saw no activity are still returned, at zero, so the ranking
	 * shows the full picture rather than silently dropping a type.
	 *
	 * @param string $range_type Type of time range.
	 * @param array  $from       Start parts.
	 * @param array  $to         End parts.
	 */
	public static function get_by_type( $range_type, $from, $to ) {
		global $wpdb;

		list( $start, $end ) = \YAYDP\Report\YAYDP_Report_Query::get_range( $range_type, $from, $to );

		$table = \YAYDP\Report\YAYDP_Report_Install::get_table_name();
		$where = \YAYDP\Report\YAYDP_Report_Query::get_where( $start, $end );

		$income = \YAYDP\Report\YAYDP_Report_Fee_Subtype_Filter::get_income_condition();

		$sql = "SELECT rule_type,
				COUNT(DISTINCT order_id) AS orders,
				SUM(CASE WHEN {$income} THEN 0 ELSE COALESCE(discount_amount, 0) END) AS discount,
				-SUM(CASE WHEN {$income} THEN COALESCE(discount_amount, 0) ELSE 0 END) AS fee_income,
				SUM(CASE WHEN discount_amount IS NULL THEN 1 ELSE 0 END) AS unknown_rows
			FROM {$table}
			WHERE {$where}
			GROUP BY rule_type";

		// Rules that charge the customer contribute fee income rather than
		// revenue, so the orders that only used those are not counted here.
		$revenue_sql = "SELECT o.rule_type, SUM(o.order_revenue) AS revenue
			FROM (
				SELECT DISTINCT order_id, rule_type, order_revenue
				FROM {$table}
				WHERE {$where} AND NOT ({$income})
			) AS o
			GROUP BY o.rule_type";

		$results  = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$revenues = $wpdb->get_results( $revenue_sql, OBJECT_K ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

		$totals = array();
		foreach ( (array) $results as $result ) {
			$totals[ $result->rule_type ] = $result;
		}

		$types = array_unique( array_merge( \YAYDP\Report\YAYDP_Report_Query::RULE_TYPES, array_keys( $totals ) ) );

		$rows = array();
		foreach ( $types as $type ) {
			$total    = isset( $totals[ $type ] ) ? $totals[ $type ] : null;
			$discount = is_null( $total ) ? 0.0 : (float) $total->discount;
			$revenue  = isset( $revenues[ $type ] ) ? (float) $revenues[ $type ]->revenue : 0.0;
			$rows[]   = array(
				'rule_type'   => $type,
				'orders'      => is_null( $total ) ? 0 : (int) $total->orders,
				'revenue'     => $revenue,
				'discount'    => $discount,
				'fee_income'  => is_null( $total ) ? 0.0 : (float) $total->fee_income,
				'roi'         => self::get_roi( $revenue, $discount ),
				'has_unknown' => ! is_null( $total ) && $total->unknown_rows > 0,
			);
		}
		return $rows;
	}

	/**
	 * Return on the discount given, expressed as a percentage.
	 *
	 * Rules that cost nothing have no meaningful return, so they are left
	 * without a value instead of being shown as an unbounded one. Rules that
	 * only charge the customer fall in that group: their income is reported on
	 * its own and never reaches the discount this is measured against.
	 *
	 * @param float $revenue  Revenue influenced by the rule.
	 * @param float $discount Discount the rule gave up.
	 */
	private static function get_roi( $revenue, $discount ) {
		if ( $discount <= 0 ) {
			return null;
		}
		return round( ( $revenue - $discount ) / $discount * 100, 2 );
	}
}
