<?php
/**
 * Read and write access to the report statistics table.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Stats_Table {

	/**
	 * Write rows, replacing any existing row for the same order and rule.
	 *
	 * @param array $rows Rows built by the stats writer.
	 */
	public static function insert_rows( array $rows ) {
		if ( empty( $rows ) ) {
			return;
		}
		global $wpdb;
		$table = YAYDP_Report_Install::get_table_name();

		$placeholders = array();
		$values       = array();
		foreach ( $rows as $row ) {
			/**
			 * A null discount means the amount is unknowable, which is not the
			 * same as zero. Placeholders cannot carry null, so an unknown amount
			 * is written as a literal NULL and simply not bound.
			 */
			$is_unknown     = is_null( $row['discount_amount'] );
			$discount_token = $is_unknown ? 'NULL' : '%f';
			$placeholders[] = "(%d, %s, %s, %s, {$discount_token}, %f, %s, %s, %s, %s, %d)";

			array_push( $values, $row['order_id'], $row['rule_id'], $row['rule_type'], $row['rule_name'] );
			if ( ! $is_unknown ) {
				$values[] = $row['discount_amount'];
			}
			array_push(
				$values,
				$row['order_revenue'],
				$row['order_status'],
				$row['currency'],
				$row['date_created'],
				$row['date_created_gmt'],
				$row['is_backfilled']
			);
		}

		$sql = "INSERT INTO {$table}
			(order_id, rule_id, rule_type, rule_name, discount_amount, order_revenue, order_status, currency, date_created, date_created_gmt, is_backfilled)
			VALUES " . implode( ', ', $placeholders ) . '
			ON DUPLICATE KEY UPDATE
				rule_type = VALUES(rule_type),
				rule_name = VALUES(rule_name),
				discount_amount = VALUES(discount_amount),
				order_revenue = VALUES(order_revenue),
				order_status = VALUES(order_status),
				currency = VALUES(currency),
				date_created = VALUES(date_created),
				date_created_gmt = VALUES(date_created_gmt),
				is_backfilled = VALUES(is_backfilled)';

		$wpdb->query( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
	}

	/**
	 * Remove every row belonging to the given orders in a single query.
	 *
	 * @param array $order_ids Given order ids.
	 */
	public static function delete_orders( array $order_ids ) {
		$order_ids = array_filter( array_map( 'intval', $order_ids ) );
		if ( empty( $order_ids ) ) {
			return;
		}
		global $wpdb;
		$table        = YAYDP_Report_Install::get_table_name();
		$placeholders = implode( ', ', array_fill( 0, count( $order_ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE order_id IN ({$placeholders})", $order_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
	}

	/**
	 * Count rows currently stored.
	 */
	public static function count_rows() {
		global $wpdb;
		$table = YAYDP_Report_Install::get_table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
	}
}
