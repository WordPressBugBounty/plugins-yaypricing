<?php
/**
 * Shared query pieces for the report endpoints.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Query {

	/**
	 * Grouping formats, keyed by the value the client may send.
	 *
	 * Used as a whitelist: the requested grouping never reaches SQL directly.
	 */
	const BUCKET_FORMATS = array(
		'day'   => '%Y-%m-%d',
		'month' => '%Y-%m',
		'year'  => '%Y',
	);

	/**
	 * Rule types the report groups by.
	 *
	 * Reports always show every one of these, so a type that was simply not used
	 * in the period reads as zero rather than disappearing from the chart.
	 */
	const RULE_TYPES = array( 'product_pricing', 'cart_discount', 'checkout_fee' );

	/**
	 * Resolve the requested grouping to a safe date format.
	 *
	 * @param string $order_by Requested grouping.
	 */
	public static function get_bucket_format( $order_by ) {
		return isset( self::BUCKET_FORMATS[ $order_by ] ) ? self::BUCKET_FORMATS[ $order_by ] : self::BUCKET_FORMATS['day'];
	}

	/**
	 * Resolve the requested range to a start and end datetime.
	 *
	 * @param string $range_type Type of time range.
	 * @param array  $from       Start parts.
	 * @param array  $to         End parts.
	 *
	 * @return array [ start, end ] as datetime strings.
	 */
	public static function get_range( $range_type, $from, $to ) {
		if ( 'last_7_days' === $range_type ) {
			return array(
				gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) ),
				gmdate( 'Y-m-d 23:59:59' ),
			);
		}

		$from_parts = self::normalize_parts( $from );
		$to_parts   = self::normalize_parts( $to );

		if ( 'year' === $range_type ) {
			return array(
				sprintf( '%04d-01-01 00:00:00', $from_parts['year'] ),
				sprintf( '%04d-12-31 23:59:59', $to_parts['year'] ),
			);
		}

		if ( 'month' === $range_type ) {
			$last_day = (int) gmdate( 't', strtotime( sprintf( '%04d-%02d-01', $to_parts['year'], $to_parts['month'] ) ) );
			return array(
				sprintf( '%04d-%02d-01 00:00:00', $from_parts['year'], $from_parts['month'] ),
				sprintf( '%04d-%02d-%02d 23:59:59', $to_parts['year'], $to_parts['month'], $last_day ),
			);
		}

		return array(
			sprintf( '%04d-%02d-%02d 00:00:00', $from_parts['year'], $from_parts['month'], $from_parts['date'] ),
			sprintf( '%04d-%02d-%02d 23:59:59', $to_parts['year'], $to_parts['month'], $to_parts['date'] ),
		);
	}

	/**
	 * Coerce the date parts sent by the client into integers.
	 *
	 * @param mixed $parts Given parts.
	 */
	private static function normalize_parts( $parts ) {
		$parts = is_array( $parts ) ? $parts : array();
		return array(
			'year'  => isset( $parts['year'] ) ? absint( $parts['year'] ) : (int) gmdate( 'Y' ),
			'month' => isset( $parts['month'] ) ? max( 1, min( 12, absint( $parts['month'] ) ) ) : 1,
			'date'  => isset( $parts['date'] ) ? max( 1, min( 31, absint( $parts['date'] ) ) ) : 1,
		);
	}

	/**
	 * Conditions shared by every report query, already prepared.
	 *
	 * Amounts are never summed across currencies, so the query is limited to the
	 * store base currency. Rows recorded before the currency was tracked carry an
	 * empty value and are included.
	 *
	 * @param string $start Range start.
	 * @param string $end   Range end.
	 */
	public static function get_where( $start, $end ) {
		global $wpdb;

		$statuses = YAYDP_Report_Stats_Writer::get_counted_statuses();
		if ( empty( $statuses ) ) {
			// Nothing can match, rather than dropping the status condition and
			// reporting on every row in the table.
			return '1 = 0';
		}
		$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		return $wpdb->prepare(
			"date_created BETWEEN %s AND %s AND order_status IN ({$status_placeholders}) AND currency IN (%s, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array_merge(
				array( $start, $end ),
				$statuses,
				array( \get_woocommerce_currency(), '' )
			)
		);
	}

	/**
	 * Fill in buckets that have no rows, so charts do not show gaps.
	 *
	 * @param array  $rows     Rows keyed by bucket.
	 * @param string $start    Range start.
	 * @param string $end      Range end.
	 * @param string $order_by Requested grouping.
	 * @param array  $defaults Values for an empty bucket.
	 */
	public static function fill_gaps( array $rows, $start, $end, $order_by, array $defaults ) {
		$steps = array(
			'day'   => array( '+1 day', 'Y-m-d' ),
			'month' => array( '+1 month', 'Y-m' ),
			'year'  => array( '+1 year', 'Y' ),
		);
		$step = isset( $steps[ $order_by ] ) ? $steps[ $order_by ] : $steps['day'];

		$filled  = array();
		$cursor  = strtotime( $start );
		$last    = strtotime( $end );
		$guard   = 0;
		while ( $cursor <= $last && $guard < 5000 ) {
			$bucket            = gmdate( $step[1], $cursor );
			$filled[ $bucket ] = isset( $rows[ $bucket ] ) ? $rows[ $bucket ] : array_merge( array( 'date' => $bucket ), $defaults );
			$cursor            = strtotime( $step[0], $cursor );
			$guard++;
		}
		return array_values( $filled );
	}
}
