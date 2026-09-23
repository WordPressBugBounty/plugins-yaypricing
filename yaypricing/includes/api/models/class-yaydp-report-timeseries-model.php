<?php
/**
 * Time bucketed report figures, split by rule type.
 *
 * @package YayPricing\Models
 */

namespace YAYDP\API\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Timeseries_Model {

	/**
	 * Orders, revenue, discount and fee income per time bucket and rule type.
	 *
	 * Every known rule type is returned for every bucket, empty ones included,
	 * so a chart keeps the same series throughout the period instead of gaining
	 * and losing lines as rules happen to be used.
	 *
	 * Revenue is credited in full to each rule type that took part in an order,
	 * so the types legitimately add up to more than the store revenue whenever
	 * an order used rules of more than one type.
	 *
	 * Checkout fees are reported as two separate figures rather than one net
	 * one: discount holds what Reduce Shipping Fee rules gave away, fee_income
	 * holds what the Add Custom Fee rules charged. See
	 * \YAYDP\Report\YAYDP_Report_Fee_Subtype_Filter.
	 *
	 * @param string $range_type Type of time range.
	 * @param array  $from       Start parts.
	 * @param array  $to         End parts.
	 * @param string $order_by   Grouping: day, month or year.
	 */
	public static function get( $range_type, $from, $to, $order_by = 'day' ) {
		list( $start, $end ) = \YAYDP\Report\YAYDP_Report_Query::get_range( $range_type, $from, $to );

		$counts   = self::query_counts( $start, $end, $order_by );
		$revenues = self::query_revenue( $start, $end, $order_by );

		// Types present in the data but not declared, such as rows written for a
		// rule that could no longer be resolved, must still be reported.
		$types = array_values( array_unique( array_merge( \YAYDP\Report\YAYDP_Report_Query::RULE_TYPES, array_keys( $counts ) ) ) );

		$rows = array();
		foreach ( $types as $type ) {
			$buckets = isset( $counts[ $type ] ) ? $counts[ $type ] : array();
			foreach ( $buckets as $bucket => $row ) {
				$buckets[ $bucket ]['revenue'] = isset( $revenues[ $type ][ $bucket ] ) ? $revenues[ $type ][ $bucket ] : 0.0;
			}

			$rows = array_merge(
				$rows,
				\YAYDP\Report\YAYDP_Report_Query::fill_gaps(
					$buckets,
					$start,
					$end,
					$order_by,
					array(
						'rule_type'        => $type,
						'orders'           => 0,
						'revenue'          => 0.0,
						'discount'         => 0.0,
						'fee_income'       => 0.0,
						'discount_unknown' => 0,
					)
				)
			);
		}

		return $rows;
	}

	/**
	 * Order counts, discount and fee income totals per bucket and rule type.
	 *
	 * The table holds one row per order and rule, so orders must be counted
	 * distinctly: an order using two rules of the same type is still one order.
	 * Discounts are summed across every row, because each row is a separate rule
	 * contribution.
	 *
	 * Rules that charge the customer are kept out of the discount total and
	 * summed on their own instead. Their amounts are stored negated, because
	 * they are money gained, so the sum is negated back into a positive income.
	 *
	 * @param string $start    Range start.
	 * @param string $end      Range end.
	 * @param string $order_by Grouping.
	 *
	 * @return array Rows keyed by rule type, then by bucket.
	 */
	private static function query_counts( $start, $end, $order_by ) {
		global $wpdb;
		$table  = \YAYDP\Report\YAYDP_Report_Install::get_table_name();
		$format = \YAYDP\Report\YAYDP_Report_Query::get_bucket_format( $order_by );
		$where  = \YAYDP\Report\YAYDP_Report_Query::get_where( $start, $end );

		$income = \YAYDP\Report\YAYDP_Report_Fee_Subtype_Filter::get_income_condition();

		$sql = $wpdb->prepare(
			"SELECT DATE_FORMAT(date_created, %s) AS bucket,
				rule_type,
				COUNT(DISTINCT order_id) AS orders,
				SUM(CASE WHEN {$income} THEN 0 ELSE COALESCE(discount_amount, 0) END) AS discount,
				-SUM(CASE WHEN {$income} THEN COALESCE(discount_amount, 0) ELSE 0 END) AS fee_income,
				SUM(CASE WHEN discount_amount IS NULL THEN 1 ELSE 0 END) AS discount_unknown
			FROM {$table}
			WHERE {$where}
			GROUP BY bucket, rule_type",
			$format
		); // phpcs:ignore WordPress.DB.PreparedSQL

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows    = array();
		foreach ( (array) $results as $result ) {
			$rows[ $result->rule_type ][ $result->bucket ] = array(
				'date'             => $result->bucket,
				'rule_type'        => $result->rule_type,
				'orders'           => (int) $result->orders,
				'discount'         => (float) $result->discount,
				'fee_income'       => (float) $result->fee_income,
				'discount_unknown' => (int) $result->discount_unknown,
			);
		}
		return $rows;
	}

	/**
	 * Revenue per bucket and rule type.
	 *
	 * Every row of an order repeats that order's revenue, so summing the column
	 * directly would multiply revenue by the number of rules of that type on
	 * each order. The rows are reduced to one per order and type first.
	 *
	 * Rules that charge the customer are left out: their contribution is fee
	 * income, reported on its own, so an order that only ever paid a fee is not
	 * also credited as revenue the checkout fee rules brought in.
	 *
	 * @param string $start    Range start.
	 * @param string $end      Range end.
	 * @param string $order_by Grouping.
	 *
	 * @return array Revenue keyed by rule type, then by bucket.
	 */
	private static function query_revenue( $start, $end, $order_by ) {
		global $wpdb;
		$table  = \YAYDP\Report\YAYDP_Report_Install::get_table_name();
		$format = \YAYDP\Report\YAYDP_Report_Query::get_bucket_format( $order_by );
		$where  = \YAYDP\Report\YAYDP_Report_Query::get_where( $start, $end );
		$income = \YAYDP\Report\YAYDP_Report_Fee_Subtype_Filter::get_income_condition();

		$sql = $wpdb->prepare(
			"SELECT DATE_FORMAT(o.date_created, %s) AS bucket, o.rule_type, SUM(o.order_revenue) AS revenue
			FROM (
				SELECT DISTINCT order_id, rule_type, order_revenue, date_created
				FROM {$table}
				WHERE {$where} AND NOT ({$income})
			) AS o
			GROUP BY bucket, o.rule_type",
			$format
		); // phpcs:ignore WordPress.DB.PreparedSQL

		$results  = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$revenues = array();
		foreach ( (array) $results as $result ) {
			$revenues[ $result->rule_type ][ $result->bucket ] = (float) $result->revenue;
		}
		return $revenues;
	}
}
