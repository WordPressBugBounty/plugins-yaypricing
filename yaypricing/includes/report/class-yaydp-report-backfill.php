<?php
/**
 * Populate the report statistics table from orders placed before the plugin
 * started recording them.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Backfill {

	const BATCH_HOOK      = 'yaydp_report_backfill_batch';
	const ACTION_GROUP    = 'yaydp-report';
	const BATCH_SIZE      = 200;
	const PROGRESS_OPTION = 'yaydp_report_backfill_progress';
	const BOUNDARY_OPTION = 'yaydp_report_backfill_boundary_date';

	/**
	 * Throttles the recovery check, which would otherwise query the scheduler on
	 * every request.
	 */
	const RECOVERY_TRANSIENT = 'yaydp_report_backfill_recovery_check';

	/**
	 * Register the batch handler.
	 */
	public static function init_hooks() {
		add_action( self::BATCH_HOOK, array( __CLASS__, 'run_batch' ), 10, 1 );
	}

	/**
	 * Whether Action Scheduler is usable. It ships with WooCommerce, but a site
	 * can disable it, so the backfill must never assume it is there.
	 */
	public static function has_scheduler() {
		return function_exists( 'as_schedule_single_action' ) && function_exists( 'as_has_scheduled_action' );
	}

	/**
	 * Start the backfill unless it already ran or is running.
	 */
	public static function maybe_start() {
		$progress = self::get_progress();
		if ( ! empty( $progress['status'] ) && 'pending' !== $progress['status'] ) {
			return;
		}
		self::start();
	}

	/**
	 * Pick a backfill back up after it was left unable to finish.
	 *
	 * Two states never resolve on their own: a site whose scheduler was missing
	 * when the backfill was first attempted, and a run whose queued batch was
	 * lost, for instance because the scheduler pruned it or a batch died
	 * mid-flight. Both leave historical orders permanently out of the report, so
	 * they are re-armed here rather than waiting for someone to reset by hand.
	 */
	public static function maybe_recover() {
		if ( get_transient( self::RECOVERY_TRANSIENT ) ) {
			return;
		}
		set_transient( self::RECOVERY_TRANSIENT, 1, HOUR_IN_SECONDS );

		if ( ! self::has_scheduler() ) {
			return;
		}

		$progress = self::get_progress();

		// The scheduler was unavailable at the time, and nothing was imported,
		// so this starts over rather than resuming.
		if ( 'unavailable' === $progress['status'] ) {
			self::start();
			return;
		}

		if ( 'running' !== $progress['status'] || self::has_queued_batch() ) {
			return;
		}

		/**
		 * Batches are offset based and walk the orders oldest id first, so the
		 * number already processed is exactly where the next batch begins.
		 */
		self::schedule_batch( absint( $progress['processed'] ) );
	}

	/**
	 * Whether a batch is still waiting to run or running right now.
	 *
	 * Any offset counts: only the absence of every batch means the run stalled.
	 */
	private static function has_queued_batch() {
		return \as_has_scheduled_action( self::BATCH_HOOK, null, self::ACTION_GROUP );
	}

	/**
	 * Begin a fresh backfill.
	 */
	public static function start() {
		if ( ! self::has_scheduler() ) {
			self::update_progress( array( 'status' => 'unavailable' ) );
			return;
		}

		// Orders from this point onwards record their amounts at checkout, so
		// anything older is what the report describes as partially known.
		if ( ! get_option( self::BOUNDARY_OPTION ) ) {
			update_option( self::BOUNDARY_OPTION, gmdate( 'Y-m-d H:i:s' ) );
		}

		self::update_progress(
			array(
				'status'    => 'running',
				'total'     => self::count_orders(),
				'processed' => 0,
			)
		);
		self::schedule_batch( 0 );
	}

	/**
	 * Queue one batch.
	 *
	 * @param int $offset Offset to start the batch at.
	 */
	private static function schedule_batch( $offset ) {
		if ( \as_has_scheduled_action( self::BATCH_HOOK, array( 'offset' => $offset ), self::ACTION_GROUP ) ) {
			return;
		}
		\as_schedule_single_action( time(), self::BATCH_HOOK, array( 'offset' => $offset ), self::ACTION_GROUP );
	}

	/**
	 * Process one batch of orders and queue the next.
	 *
	 * @param int $offset Offset to start the batch at.
	 */
	public static function run_batch( $offset = 0 ) {
		$offset    = absint( $offset );
		$order_ids = self::get_order_ids( $offset );

		if ( empty( $order_ids ) ) {
			self::update_progress( array( 'status' => 'completed' ) );
			return;
		}

		// A rule may have been renamed or removed while the backfill runs.
		YAYDP_Report_Rule_Map::flush();
		YAYDP_Report_Stats_Writer::rebuild_orders( $order_ids );

		self::update_progress( array( 'processed' => $offset + count( $order_ids ) ) );

		self::schedule_batch( $offset + self::BATCH_SIZE );
	}

	/**
	 * Orders eligible for the report, oldest id first.
	 *
	 * Ascending id order keeps paging stable: orders created while the backfill
	 * runs are appended after the cursor instead of shifting earlier pages, and
	 * those orders are handled by the live hooks anyway.
	 *
	 * @param int $offset Offset to start at.
	 */
	private static function get_order_ids( $offset ) {
		$orders = \wc_get_orders(
			array(
				'status'  => YAYDP_Report_Stats_Writer::get_counted_statuses(),
				'limit'   => self::BATCH_SIZE,
				'offset'  => $offset,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'return'  => 'ids',
			)
		);
		return is_array( $orders ) ? $orders : array();
	}

	/**
	 * Total number of orders the backfill will walk through.
	 *
	 * Counted through the same query the batches use, so the progress total can
	 * never disagree with the number of orders actually visited.
	 */
	private static function count_orders() {
		$result = \wc_get_orders(
			array(
				'status'   => YAYDP_Report_Stats_Writer::get_counted_statuses(),
				'limit'    => 1,
				'return'   => 'ids',
				'paginate' => true,
			)
		);
		return isset( $result->total ) ? (int) $result->total : 0;
	}

	/**
	 * Current progress.
	 */
	public static function get_progress() {
		$progress = get_option( self::PROGRESS_OPTION, array() );
		$defaults = array(
			'status'    => 'pending',
			'total'     => 0,
			'processed' => 0,
		);
		return is_array( $progress ) ? array_merge( $defaults, $progress ) : $defaults;
	}

	/**
	 * Merge values into the stored progress.
	 *
	 * @param array $values Values to merge.
	 */
	private static function update_progress( array $values ) {
		update_option( self::PROGRESS_OPTION, array_merge( self::get_progress(), $values ), false );
	}

	/**
	 * Date from which orders record their own amounts.
	 */
	public static function get_boundary_date() {
		return get_option( self::BOUNDARY_OPTION, '' );
	}

	/**
	 * Discard progress so the backfill can be run again from the start.
	 */
	public static function reset() {
		delete_option( self::PROGRESS_OPTION );
		self::start();
	}
}
