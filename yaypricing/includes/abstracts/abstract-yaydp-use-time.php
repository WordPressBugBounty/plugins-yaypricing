<?php
/**
 * Abstract class for managing the use time
 *
 * @package YayPricing\UseTime
 *
 * @since 2.4
 */

namespace YAYDP\Abstracts;

/**
 * Declare class
 */
abstract class YAYDP_Use_Time {

	/**
	 * Order statuses that count as a use. Pending, failed, cancelled, refunded
	 * and draft orders do not. Pro keeps this on YAYDP_Customer_Use_Limit,
	 * which the free edition does not ship.
	 */
	const COUNTED_STATUSES = array( 'wc-processing', 'wc-on-hold', 'wc-completed' );

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'woocommerce_before_checkout_process', array( $this, 'before_checkout_process' ), 10 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'handle_store_api_order_processed' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'sync_order_use_time' ), 10, 1 );
		add_action( 'woocommerce_trash_order', array( $this, 'sync_order_use_time' ), 10, 1 );
		add_action( 'woocommerce_untrash_order', array( $this, 'sync_order_use_time' ), 10, 1 );
		add_action( 'woocommerce_before_delete_order', array( $this, 'release_order_use_time' ), 10, 1 );
		// The posts order store trashes and deletes from wp-admin without the woocommerce_* hooks.
		add_action( 'trashed_post', array( $this, 'sync_post_order_use_time' ), 10, 1 );
		add_action( 'untrashed_post', array( $this, 'sync_post_order_use_time' ), 10, 1 );
		add_action( 'before_delete_post', array( $this, 'release_post_order_use_time' ), 10, 1 );
	}

	/**
	 * Bridge for the Block (Store API) checkout, which passes an order object
	 * instead of an order id. Normalizes to id and delegates to checkout_order_processed.
	 *
	 * @param \WC_Order $order Given order.
	 */
	public function handle_store_api_order_processed( $order ) {
		if ( $order instanceof \WC_Order ) {
			$this->checkout_order_processed( $order->get_id() );
		}
	}

	/**
	 * Count or uncount the order against the rules it applied, so use_time
	 * equals the number of orders in self::COUNTED_STATUSES plus the counted
	 * orders that kept their use.
	 *
	 * An order meta flag records whether the order is currently counted, so
	 * repeated hooks and transitions between two counted statuses ( processing
	 * → completed ) change nothing. See should_order_count() for when a
	 * counted order gives its use back.
	 *
	 * @param int $order_id Given order id.
	 */
	public function sync_order_use_time( $order_id ) {
		$this->apply_order_use_time( $order_id, null );
	}

	/**
	 * Give the order's use back before it is deleted.
	 *
	 * @param int $order_id Given order id.
	 */
	public function release_order_use_time( $order_id ) {
		$this->apply_order_use_time( $order_id, false );
	}

	/**
	 * Bridge for trashed_post / untrashed_post on the posts order store.
	 *
	 * @param int $post_id Given post id.
	 */
	public function sync_post_order_use_time( $post_id ) {
		if ( $this->is_order_post( $post_id ) ) {
			$this->sync_order_use_time( $post_id );
		}
	}

	/**
	 * Bridge for before_delete_post on the posts order store.
	 *
	 * @param int $post_id Given post id.
	 */
	public function release_post_order_use_time( $post_id ) {
		if ( $this->is_order_post( $post_id ) ) {
			$this->release_order_use_time( $post_id );
		}
	}

	/**
	 * Whether a post is an order.
	 *
	 * @param int $post_id Given post id.
	 * @return bool
	 */
	private function is_order_post( $post_id ) {
		return function_exists( 'wc_get_order_types' ) && in_array( get_post_type( $post_id ), \wc_get_order_types(), true );
	}

	/**
	 * Whether an order in the given status uses up the limit.
	 *
	 * Processing, on-hold and completed orders always do. An order that was
	 * counted keeps its use in any other status, unless the merchant chose that
	 * status to give it back ( General settings ). Trashed orders never count.
	 *
	 * @param string $status     Order status without "wc-".
	 * @param bool   $is_counted Whether the order is counted now.
	 * @return bool
	 */
	private function should_order_count( $status, $is_counted ) {
		if ( 'trash' === $status ) {
			return false;
		}
		if ( in_array( 'wc-' . $status, self::COUNTED_STATUSES, true ) ) {
			return true;
		}
		$restore_statuses = \YAYDP\Settings\YAYDP_General_Settings::get_instance()->get_use_limit_restore_statuses();
		return $is_counted && ! in_array( $status, $restore_statuses, true );
	}

	/**
	 * Move the order's use to match its state.
	 *
	 * @param int       $order_id     Given order id.
	 * @param bool|null $should_count Forced state, or null to read it from the order status.
	 */
	private function apply_order_use_time( $order_id, $should_count ) {
		$type          = $this->get_rule_type();
		$rules_key     = "yaydp_{$type}_rules";
		$flag_meta_key = "_yaydp_{$type}_use_time_counted";
		$this->with_rules_lock(
			$rules_key,
			function() use ( $order_id, $should_count, $rules_key, $flag_meta_key ) {
				// Read inside the lock so two hooks for the same order ( gateway
				// webhook and admin at the same instant ) cannot both move the count.
				$order = \wc_get_order( $order_id );
				if ( ! $order instanceof \WC_Order ) {
					return;
				}
				$list_rule_id = $order->get_meta( $rules_key );
				if ( empty( $list_rule_id ) || ! is_array( $list_rule_id ) ) {
					return;
				}
				$is_counted = ! empty( $order->get_meta( $flag_meta_key ) );
				if ( null === $should_count ) {
					$should_count = $this->should_order_count( $order->get_status(), $is_counted );
				}
				if ( $should_count === $is_counted ) {
					return;
				}
				if ( $should_count ) {
					$order->update_meta_data( $flag_meta_key, time() );
				} else {
					$order->delete_meta_data( $flag_meta_key );
				}
				$order->save_meta_data();

				$this->adjust_stored_use_time( $rules_key, $list_rule_id, $should_count ? 1 : -1 );
			}
		);
	}

	/**
	 * Run the rules read-increment-write under a named database lock.
	 *
	 * The counter lives inside the rules option, so two orders completing at
	 * the same time would otherwise both read the same value and one increment
	 * would be lost. Once the lock is held the option cache is dropped, because
	 * the value loaded at bootstrap may predate another process's increment.
	 * The lock is best effort: if the server refuses it the callback still
	 * runs, so counting never silently stops.
	 *
	 * @param string   $option_name Rules option name; doubles as the lock name.
	 * @param callable $callback    Work to run while holding the lock.
	 */
	protected function with_rules_lock( $option_name, $callback ) {
		global $wpdb;
		// Named locks are server-wide, so prefix with the table prefix to keep
		// sites sharing one MySQL server from contending with each other.
		$lock_name = $wpdb->prefix . $option_name;
		// A host without GET_LOCK support must not print a DB error into the response.
		$show_errors = $wpdb->suppress_errors();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$locked = '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, 5 ) );
		$wpdb->suppress_errors( $show_errors );
		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		try {
			$callback();
		} finally {
			if ( $locked ) {
				$show_errors = $wpdb->suppress_errors();
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
				$wpdb->suppress_errors( $show_errors );
			}
		}
	}

	/**
	 * Add $delta to use_time on the stored rules whose id is in the list, floored
	 * at 0, writing the option back exactly as read. Works on the raw option
	 * rather than the rule loaders on purpose: the loaders drop rule types this
	 * edition does not ship, and those entries must survive a status change untouched.
	 *
	 * @param string   $option_name  Rules option name.
	 * @param string[] $list_rule_id Ids of the rules used by the order.
	 * @param int      $delta        1 to count the order, -1 to give its use back.
	 */
	protected function adjust_stored_use_time( $option_name, array $list_rule_id, $delta ) {
		$rules = get_option( $option_name, array() );
		if ( ! is_array( $rules ) ) {
			return;
		}
		foreach ( $rules as &$data ) {
			if ( is_array( $data ) && isset( $data['id'] ) && in_array( $data['id'], $list_rule_id, true ) ) {
				$data['use_time'] = max( 0, (int) ( $data['use_time'] ?? 0 ) + $delta );
			}
		}
		unset( $data );
		update_option( $option_name, $rules );
	}

	/**
	 * This function is called before the checkout process begins.
	 * It must be implemented by child class.
	 */
	abstract public function before_checkout_process();

	/**
	 * This function is responsible for processing the order after it has been checked out.
	 * It must be implemented by child class.
	 *
	 * @param int $order_id Given order id.
	 */
	abstract public function checkout_order_processed( $order_id );

	/**
	 * Rule type slug, as in the yaydp_{type}_rules option and order meta.
	 * It must be implemented by child class.
	 *
	 * @return string
	 */
	abstract protected function get_rule_type();
}
