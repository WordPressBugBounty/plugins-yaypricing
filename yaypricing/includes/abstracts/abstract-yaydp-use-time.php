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
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'woocommerce_before_checkout_process', array( $this, 'before_checkout_process' ), 10 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'handle_store_api_order_processed' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ), 10, 1 );
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
	 * Mark the order as counted for this rule type, once.
	 *
	 * woocommerce_order_status_completed fires on every transition into
	 * completed ( completed → processing → completed, refund flows, manual
	 * edits ), so the first pass stamps the order and later passes bail out.
	 *
	 * @param \WC_Order $order         Given order.
	 * @param string    $flag_meta_key Order meta key that records the count.
	 * @return bool True when this call owns the count, false when already counted.
	 */
	protected function claim_order_completion( $order, $flag_meta_key ) {
		if ( ! empty( $order->get_meta( $flag_meta_key ) ) ) {
			return false;
		}
		$order->update_meta_data( $flag_meta_key, time() );
		$order->save_meta_data();
		return true;
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
	 * Increment use_time on the stored rules whose id is in the list, writing
	 * the option back exactly as read. Works on the raw option rather than the
	 * rule loaders on purpose: the loaders drop rule types this edition does not
	 * ship, and those entries must survive an order completion untouched.
	 *
	 * @param string   $option_name  Rules option name.
	 * @param string[] $list_rule_id Ids of the rules used by the order.
	 */
	protected function increment_stored_use_time( $option_name, array $list_rule_id ) {
		$rules = get_option( $option_name, array() );
		if ( ! is_array( $rules ) ) {
			return;
		}
		foreach ( $rules as &$data ) {
			if ( is_array( $data ) && isset( $data['id'] ) && in_array( $data['id'], $list_rule_id, true ) ) {
				$data['use_time'] = (int) ( $data['use_time'] ?? 0 ) + 1;
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
	 * Called when an order transitions to the completed status.
	 * Implementations should increment use_time on rules applied to this order.
	 * It must be implemented by child class.
	 *
	 * @param int $order_id Given order id.
	 */
	abstract public function on_order_completed( $order_id );
}
