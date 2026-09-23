<?php
/**
 * Facade over YAYDP_Schedule_Evaluator, adds the "next active time" lookup
 *
 * @package YayPricing\Classes\Schedule
 */

namespace YAYDP\Schedule;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Schedule_Manager {

	/**
	 * Evaluator used to run the active checks.
	 *
	 * @var YAYDP_Schedule_Evaluator
	 */
	private $evaluator;

	/**
	 * Constructor
	 *
	 * @param YAYDP_Schedule_Evaluator|null $evaluator Optional evaluator.
	 */
	public function __construct( $evaluator = null ) {
		$this->evaluator = is_null( $evaluator ) ? new YAYDP_Schedule_Evaluator() : $evaluator;
	}

	/**
	 * Checks whether the schedule is active at the given time
	 *
	 * @param array          $schedule Schedule data.
	 * @param \DateTime|null $check_time Time to check against.
	 * @return bool
	 */
	public function is_active( $schedule, $check_time = null ) {
		return $this->evaluator->is_schedule_active( $schedule, $check_time );
	}

	/**
	 * Retrieves the end of the occurrence running at the given time
	 *
	 * @param array          $schedule Schedule data.
	 * @param \DateTime|null $check_time Time to check against.
	 * @return \DateTime|null
	 */
	public function get_current_window_end( $schedule, $check_time = null ) {
		return $this->evaluator->get_current_window_end( $schedule, $check_time );
	}

	/**
	 * Retrieves the next time the schedule becomes active.
	 *
	 * Returns an array with the keys active, next_time and reason.
	 *
	 * @param array          $schedule Schedule data.
	 * @param \DateTime|null $from_time Starting point, default is the current time.
	 * @return array
	 */
	public function get_next_active_time( $schedule, $from_time = null ) {
		$from_time = is_null( $from_time ) ? $this->get_current_time() : $from_time;

		if ( empty( $schedule ) || empty( $schedule['enabled'] ) || is_null( $from_time ) ) {
			return array(
				'active'    => true,
				'next_time' => null,
				'reason'    => 'schedule_disabled',
			);
		}

		$end_date = isset( $schedule['end_date'] ) ? trim( (string) $schedule['end_date'] ) : '';
		if ( '' !== $end_date ) {
			try {
				if ( $from_time > new \DateTime( $end_date, $this->get_timezone() ) ) {
					return array(
						'active'    => false,
						'next_time' => null,
						'reason'    => 'beyond_end_date',
					);
				}
			} catch ( \Exception $e ) {
				// Unparseable end date, fall through to the active check below.
			}
		}

		if ( $this->is_active( $schedule, $from_time ) ) {
			return array(
				'active'    => true,
				'next_time' => $from_time,
				'reason'    => 'currently_active',
			);
		}

		$next = $this->find_next_active_instant( $schedule, $from_time );
		if ( is_null( $next ) ) {
			return array(
				'active'    => false,
				'next_time' => null,
				'reason'    => 'no_next_active_found',
			);
		}

		return array(
			'active'    => true,
			'next_time' => $next,
			'reason'    => 'found_next_active',
		);
	}

	/**
	 * Finds the first instant after the given time at which the schedule is active.
	 *
	 * A window can only open either at the recurring start time on one of its active
	 * days, or at the schedule start date itself, so only those instants are probed.
	 * That keeps a full year within reach — a yearly recurrence is found the same way
	 * a weekly one is — without walking every hour of it.
	 *
	 * @param array     $schedule Schedule data.
	 * @param \DateTime $from_time Starting point.
	 * @return \DateTime|null
	 */
	private function find_next_active_instant( $schedule, $from_time ) {
		$recurring = isset( $schedule['recurring'] ) ? $schedule['recurring'] : array();

		// A schedule never opens before its own start date.
		$begin      = clone $from_time;
		$start_date = isset( $schedule['start_date'] ) ? trim( (string) $schedule['start_date'] ) : '';
		if ( '' !== $start_date ) {
			try {
				$start = new \DateTime( $start_date, $this->get_timezone() );
				if ( $start > $begin ) {
					$begin = $start;
				}
			} catch ( \Exception $e ) {
				// Unparseable start date, keep scanning from the given time.
				$begin = clone $from_time;
			}
		}

		// Without a recurrence the only window opens at the start date.
		if ( empty( $recurring['enabled'] ) ) {
			return $this->is_active( $schedule, $begin ) ? $begin : null;
		}

		// The start date can land inside a recurring window that is already open.
		if ( $begin > $from_time && $this->is_active( $schedule, $begin ) ) {
			return $begin;
		}

		$open_time = isset( $recurring['start_time'] ) ? trim( (string) $recurring['start_time'] ) : '';
		$open_time = '' === $open_time ? '00:00:00' : $open_time;
		$parts     = array_map( 'intval', array_pad( explode( ':', $open_time ), 3, 0 ) );

		// Probe the moment each following day opens, far enough ahead to cover a
		// yearly recurrence in a leap year.
		$probe = clone $begin;
		for ( $day = 0; $day <= 366; $day++ ) {
			$candidate = clone $probe;
			$candidate->setTime( $parts[0], $parts[1], $parts[2] );
			if ( $candidate > $from_time && $this->is_active( $schedule, $candidate ) ) {
				return $candidate;
			}
			$probe->modify( '+1 day' );
		}

		return null;
	}

	/**
	 * Retrieves the current time in the site timezone
	 *
	 * @return \DateTime|null
	 */
	private function get_current_time() {
		try {
			return new \DateTime( 'now', $this->get_timezone() );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Retrieves the site timezone
	 *
	 * @return \DateTimeZone
	 */
	private function get_timezone() {
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}
		return new \DateTimeZone( wp_timezone_string() );
	}
}
