<?php
/**
 * Order-history lookups shared by purchase-history condition types.
 *
 * Every query here is the slow path (wc_get_orders + item walks); the query
 * arguments are the historical ones and must not change while types move.
 *
 * @package YayPricing\Condition
 * @since 3.5.8
 */

namespace YAYDP\Condition;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Order_History_Query {

	/**
	 * Statuses counted as purchase history: everything but refunded.
	 *
	 * @return string[]
	 */
	public static function history_statuses() {
		return array_filter(
			array_keys( \wc_get_order_statuses() ),
			function ( $status ) {
				return 'wc-refunded' !== $status;
			}
		);
	}

	/**
	 * Statuses counted as "an order was placed": everything but drafts/failed.
	 *
	 * @return string[]
	 */
	public static function placed_statuses() {
		return array_filter(
			array_keys( \wc_get_order_statuses() ),
			function ( $status ) {
				return ! in_array( $status, array( 'wc-checkout-draft', 'wc-failed' ), true );
			}
		);
	}

	/**
	 * Number of completed/processing orders matching the given lookup.
	 *
	 * @param array $lookup wc_get_orders() args such as customer_id or billing_email.
	 * @return int
	 */
	public static function count_paid_orders( array $lookup ) {
		$orders = \wc_get_orders(
			$lookup + array(
				'status' => array( 'wc-completed', 'wc-processing' ),
				'limit'  => -1,
				'return' => 'ids',
			)
		);
		return count( $orders );
	}

	/**
	 * Whether the user has at least one order matching the extra args.
	 *
	 * @param int   $user_id User id.
	 * @param array $args    Extra wc_get_orders() args (dates, statuses).
	 * @return bool
	 */
	public static function user_has_order( $user_id, array $args ) {
		$orders = \wc_get_orders(
			$args + array(
				'customer_id' => $user_id,
				'limit'       => '1',
			)
		);
		return ! empty( $orders );
	}

	/**
	 * The user's most recent orders, newest first, any status.
	 *
	 * @param int $user_id User id.
	 * @param int $limit   Number of orders.
	 * @return \WC_Order[]
	 */
	public static function recent_orders( $user_id, $limit ) {
		return \wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => $limit,
			)
		);
	}

	/**
	 * The user's history orders (all statuses but refunded).
	 *
	 * @param int $user_id User id.
	 * @return \WC_Order[]
	 */
	private static function history_orders( $user_id ) {
		return \wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => '-1',
				'status'      => self::history_statuses(),
			)
		);
	}

	/**
	 * Product ids (plus parent ids) in one order, unique.
	 *
	 * @param \WC_Order $order Order.
	 * @return int[]
	 */
	private static function order_product_ids( $order ) {
		$ids = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( empty( $product ) ) {
				continue;
			}
			$ids[] = $product->get_id();
			if ( ! empty( $product->get_parent_id() ) ) {
				$ids[] = $product->get_parent_id();
			}
		}
		return array_unique( $ids );
	}

	/**
	 * Whether any single history order satisfies the contain comparator against
	 * the condition's product list.
	 *
	 * @param int   $user_id   User id.
	 * @param array $condition Stored condition.
	 * @return bool
	 */
	public static function any_order_contains_products( $user_id, array $condition ) {
		$ids = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		foreach ( self::history_orders( $user_id ) as $order ) {
			$intersect = array_intersect( $ids, self::order_product_ids( $order ) );
			if ( YAYDP_Comparators::matches_contain( $intersect, count( $ids ), $condition ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether any single history order satisfies the contain comparator against
	 * the condition's category list.
	 *
	 * @param int   $user_id   User id.
	 * @param array $condition Stored condition.
	 * @return bool
	 */
	public static function any_order_contains_categories( $user_id, array $condition ) {
		$ids = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		foreach ( self::history_orders( $user_id ) as $order ) {
			$order_cats = array();
			foreach ( $order->get_items() as $item ) {
				$order_cats = array_merge( \YAYDP\Helper\YAYDP_Product_Helper::get_product_cats( $item->get_product() ), $order_cats );
			}
			$intersect = array_intersect( $ids, array_unique( $order_cats ) );
			if ( YAYDP_Comparators::matches_contain( $intersect, count( $ids ), $condition ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Product ids (plus parent ids) the user bought within the condition's
	 * day window (`items_purchased_in` semantics: gte/lte widen by one day).
	 *
	 * @param int   $user_id   User id.
	 * @param array $condition Stored condition (value = days).
	 * @return int[]
	 */
	public static function product_ids_purchased_within( $user_id, array $condition ) {
		$days = max( 0, floatval( $condition['value'] ) );
		if ( in_array( $condition['comparation'], array( YAYDP_Comparators::GTE, YAYDP_Comparators::LTE ), true ) ) {
			++$days;
		}
		$args   = array(
			'customer_id' => $user_id,
			'limit'       => '-1',
			'status'      => self::placed_statuses(),
		);
		$args  += self::window_args( $days, $condition['comparation'] );
		$orders = \wc_get_orders( $args );
		$ids    = array();
		foreach ( $orders as $order ) {
			$ids = array_merge( $ids, self::order_product_ids( $order ) );
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Date bound for "N days ago" comparators: greater_than/gte look before
	 * the bound, less_than/lte after it.
	 *
	 * @param float  $days        Days back from now.
	 * @param string $comparation Comparator slug.
	 * @return array date_before | date_after arg, or empty.
	 */
	public static function window_args( $days, $comparation ) {
		$date  = new \DateTime( "- $days day" );
		$bound = $date->format( 'Y-m-d H:i:s' );
		if ( in_array( $comparation, array( YAYDP_Comparators::GREATER_THAN, YAYDP_Comparators::GTE ), true ) ) {
			return array( 'date_before' => $bound );
		}
		if ( in_array( $comparation, array( YAYDP_Comparators::LESS_THAN, YAYDP_Comparators::LTE ), true ) ) {
			return array( 'date_after' => $bound );
		}
		return array();
	}

	/**
	 * Products the user bought that sit in one of the given categories, as
	 * `{ product_id, quantity }` rows — one row per order line (historical:
	 * the same product bought in several orders contributes each quantity).
	 *
	 * @param int   $user_id      User id.
	 * @param int[] $category_ids Category ids.
	 * @return array
	 */
	public static function purchased_products_in_categories( $user_id, array $category_ids ) {
		$rows = array();
		foreach ( self::history_orders( $user_id ) as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				if ( empty( $product ) || empty( array_intersect( $product->get_category_ids(), $category_ids ) ) ) {
					continue;
				}
				$rows[] = array(
					'product_id' => $product->get_id(),
					'quantity'   => $item->get_quantity(),
				);
			}
		}
		return $rows;
	}
}
