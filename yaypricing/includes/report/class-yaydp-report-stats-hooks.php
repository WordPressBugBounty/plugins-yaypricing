<?php
/**
 * Keep the report statistics table in step with order changes.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Stats_Hooks {

	/**
	 * Option holding the counted statuses the table was last filled with.
	 */
	const COUNTED_STATUSES_OPTION = 'yaydp_report_counted_statuses';

	/**
	 * Register hooks.
	 */
	public static function init_hooks() {
		/**
		 * Checked on init rather than admin_init, because an order can reach a
		 * counted status from the frontend, REST or cron long before anyone
		 * loads an admin page. The check itself is only an option comparison.
		 */
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
		/**
		 * Runs after the install check so a table created on this same request
		 * has already started its own backfill, leaving nothing to recover.
		 */
		add_action( 'init', array( 'YAYDP\Report\YAYDP_Report_Backfill', 'maybe_recover' ), 6 );
		/**
		 * Runs last, so a backfill started or recovered above is already using
		 * the current statuses and does not get restarted for nothing.
		 */
		add_action( 'init', array( __CLASS__, 'maybe_refresh_counted_statuses' ), 7 );
		YAYDP_Report_Backfill::init_hooks();

		/**
		 * Revenue is only final once the order reaches a counted status, so the
		 * projection reacts to status rather than to checkout.
		 */
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_status_changed' ), 20, 1 );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'on_refunded' ), 20, 1 );

		add_action( 'woocommerce_trash_order', array( __CLASS__, 'on_removed' ), 20, 1 );
		add_action( 'woocommerce_delete_order', array( __CLASS__, 'on_removed' ), 20, 1 );
		// Orders stored as posts do not fire the WooCommerce specific hooks.
		add_action( 'trashed_post', array( __CLASS__, 'on_post_removed' ), 20, 1 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_post_removed' ), 20, 1 );
	}

	/**
	 * Create the table when the structure version changed.
	 */
	public static function maybe_install() {
		$installed = YAYDP_Report_Install::maybe_install();
		if ( $installed ) {
			// A newly created table holds nothing, so existing orders need
			// walking before the report can show anything meaningful.
			YAYDP_Report_Backfill::maybe_start();
		}
	}

	/**
	 * Rebuild the table when the set of counted statuses changed.
	 *
	 * Orders in a status that was not counted before were never written to the
	 * table, so widening the set only takes effect for past orders once they are
	 * walked again. This happens when a plugin registering an extra counted
	 * status, such as Ready to Collect for WooCommerce, is activated or removed.
	 */
	public static function maybe_refresh_counted_statuses() {
		$statuses = YAYDP_Report_Stats_Writer::get_counted_statuses();
		sort( $statuses );
		$signature = implode( ',', $statuses );

		if ( get_option( self::COUNTED_STATUSES_OPTION, '' ) === $signature ) {
			return;
		}
		update_option( self::COUNTED_STATUSES_OPTION, $signature, false );

		/**
		 * A backfill that has not run yet, or that is waiting on a scheduler
		 * that is not there, will walk the current statuses when it does run.
		 * Only a run that already finished, or one part way through with the
		 * previous statuses, leaves the table disagreeing with them.
		 */
		$status = YAYDP_Report_Backfill::get_progress()['status'];
		if ( 'completed' !== $status && 'running' !== $status ) {
			return;
		}

		YAYDP_Report_Backfill::reset();
	}

	/**
	 * Rebuild an order after its status changed.
	 *
	 * @param int $order_id Given order id.
	 */
	public static function on_status_changed( $order_id ) {
		YAYDP_Report_Stats_Writer::rebuild_order( $order_id );
	}

	/**
	 * Rebuild an order after a refund, so the recorded revenue stays net.
	 *
	 * @param int $order_id Given order id.
	 */
	public static function on_refunded( $order_id ) {
		YAYDP_Report_Stats_Writer::rebuild_order( $order_id );
	}

	/**
	 * Drop the rows of an order that was trashed or deleted.
	 *
	 * @param int $order_id Given order id.
	 */
	public static function on_removed( $order_id ) {
		YAYDP_Report_Stats_Writer::delete_order( $order_id );
	}

	/**
	 * Drop the rows of an order stored as a post.
	 *
	 * @param int $post_id Given post id.
	 */
	public static function on_post_removed( $post_id ) {
		if ( 'shop_order' !== get_post_type( $post_id ) ) {
			return;
		}
		YAYDP_Report_Stats_Writer::delete_order( $post_id );
	}
}
