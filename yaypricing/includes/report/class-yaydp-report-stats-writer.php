<?php
/**
 * Project order data into the report statistics table.
 *
 * The table is a projection, not the source of truth. Everything here can be
 * rebuilt from the order itself, which is what makes re-running safe.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Stats_Writer {

	/**
	 * Order statuses that always count towards reported revenue.
	 */
	const COUNTED_STATUSES = array( 'completed', 'processing' );

	/**
	 * Order status registered by the Ready to Collect for WooCommerce plugin.
	 *
	 * An order waiting to be picked up is already paid for, so it belongs with
	 * processing and completed rather than with the pending statuses.
	 */
	const READY_TO_COLLECT_STATUS = 'ready-to-collect';

	/**
	 * Order meta keys holding the ids of the rules applied to an order.
	 */
	const RULE_ID_META_KEYS = array(
		'yaydp_product_pricing_rules',
		'yaydp_cart_discount_rules',
		'yaydp_checkout_fee_rules',
	);

	/**
	 * Order statuses that count towards reported revenue on this store.
	 *
	 * Statuses added by other plugins are only counted while their plugin is
	 * actually registering them, so a deactivated plugin cannot leave the report
	 * summing a status the store no longer has.
	 */
	public static function get_counted_statuses() {
		$statuses = self::COUNTED_STATUSES;

		if ( self::is_status_registered( self::READY_TO_COLLECT_STATUS ) ) {
			$statuses[] = self::READY_TO_COLLECT_STATUS;
		}

		/**
		 * Filters the order statuses whose orders are counted by the report.
		 *
		 * @param array $statuses Status keys, without the wc- prefix.
		 */
		$statuses = \apply_filters( 'yaydp_report_counted_order_statuses', $statuses );

		return array_values( array_unique( array_filter( array_map( 'strval', (array) $statuses ) ) ) );
	}

	/**
	 * Whether a status is registered with WooCommerce right now.
	 *
	 * @param string $status Status key, without the wc- prefix.
	 */
	private static function is_status_registered( $status ) {
		if ( ! function_exists( 'wc_get_order_statuses' ) ) {
			return false;
		}
		return array_key_exists( 'wc-' . $status, \wc_get_order_statuses() );
	}

	/**
	 * Rebuild the rows for a single order.
	 *
	 * @param int $order_id Given order id.
	 */
	public static function rebuild_order( $order_id ) {
		self::rebuild_orders( array( $order_id ) );
	}

	/**
	 * Rebuild the rows for several orders using a single write.
	 *
	 * @param array $order_ids Given order ids.
	 */
	public static function rebuild_orders( array $order_ids ) {
		$rows        = array();
		$handled_ids = array();
		$counted     = self::get_counted_statuses();
		foreach ( $order_ids as $order_id ) {
			$order = \wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			$handled_ids[] = $order->get_id();
			if ( ! in_array( $order->get_status(), $counted, true ) ) {
				continue;
			}
			$rows = array_merge( $rows, self::collect_rows( $order ) );
		}

		// Orders that no longer qualify must lose their rows, otherwise a
		// cancelled order would keep counting towards revenue.
		$keep_order_ids = array_unique( wp_list_pluck( $rows, 'order_id' ) );
		$stale_ids      = array_diff( $handled_ids, $keep_order_ids );
		if ( ! empty( $stale_ids ) ) {
			YAYDP_Report_Stats_Table::delete_orders( $stale_ids );
		}

		YAYDP_Report_Stats_Table::insert_rows( $rows );
	}

	/**
	 * Build the rows describing one order.
	 *
	 * @param \WC_Order $order Given order.
	 */
	public static function collect_rows( $order ) {
		/**
		 * Orders placed before the amounts were captured at checkout carry no
		 * stored amounts, so whatever the order still records is reconstructed
		 * instead. Doing it here means the backfill is only a batching loop and
		 * there is no separate write path that could drift from this one.
		 */
		$amounts       = \YAYDP\Helper\YAYDP_Rule_Discount_Helper::get( $order );
		$is_backfilled = empty( $amounts );
		if ( $is_backfilled ) {
			$amounts = YAYDP_Report_Backfill_Resolver::resolve( $order );
		}

		$rule_ids = self::collect_rule_ids( $order, $amounts );
		if ( empty( $rule_ids ) ) {
			return array();
		}

		$date_created = $order->get_date_created();
		if ( ! $date_created ) {
			return array();
		}
		$revenue = self::get_net_revenue( $order );

		$rows = array();
		foreach ( $rule_ids as $rule_id ) {
			$rule   = YAYDP_Report_Rule_Map::find( $rule_id );
			$rows[] = array(
				'order_id'         => $order->get_id(),
				'rule_id'          => $rule_id,
				'rule_type'        => is_null( $rule ) ? '' : $rule['type'],
				'rule_name'        => is_null( $rule ) ? '' : $rule['name'],
				'discount_amount'  => array_key_exists( $rule_id, $amounts ) ? $amounts[ $rule_id ] : null,
				'order_revenue'    => $revenue,
				'order_status'     => $order->get_status(),
				'currency'         => $order->get_currency(),
				'date_created'     => $date_created->date( 'Y-m-d H:i:s' ),
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s', $date_created->getTimestamp() ),
				'is_backfilled'    => $is_backfilled ? 1 : 0,
			);
		}
		return $rows;
	}

	/**
	 * Every rule id associated with an order.
	 *
	 * A rule can appear in the captured amounts without appearing in the id
	 * meta, because rules that only added free items are not collected into the
	 * id meta. Both sources are therefore combined.
	 *
	 * @param \WC_Order $order   Given order.
	 * @param array     $amounts Captured per-rule amounts.
	 */
	private static function collect_rule_ids( $order, array $amounts ) {
		$rule_ids = array_keys( $amounts );
		foreach ( self::RULE_ID_META_KEYS as $meta_key ) {
			$stored = $order->get_meta( $meta_key );
			if ( is_array( $stored ) ) {
				$rule_ids = array_merge( $rule_ids, $stored );
			}
		}
		return array_unique( array_filter( $rule_ids ) );
	}

	/**
	 * Net revenue for an order, excluding tax and shipping, after refunds.
	 *
	 * Shipping and tax are excluded so the figure reflects what the store earned
	 * from the products a rule actually influenced. This corresponds to the net
	 * sales figure in WooCommerce Analytics rather than total sales.
	 *
	 * @param \WC_Order $order Given order.
	 */
	private static function get_net_revenue( $order ) {
		$gross    = $order->get_total() - $order->get_total_tax() - $order->get_shipping_total();
		$refunded = $order->get_total_refunded() - $order->get_total_tax_refunded() - $order->get_total_shipping_refunded();
		return (float) \wc_format_decimal( $gross - $refunded, 4 );
	}

	/**
	 * Remove every row belonging to an order.
	 *
	 * @param int $order_id Given order id.
	 */
	public static function delete_order( $order_id ) {
		YAYDP_Report_Stats_Table::delete_orders( array( $order_id ) );
	}
}
