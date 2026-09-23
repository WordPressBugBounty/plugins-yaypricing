<?php
/**
 * Centralized schedule evaluation for every rule type.
 *
 * Uses the WordPress timezone so the results stay consistent with wp_date() / current_time().
 * Fail-safe: invalid or incomplete schedule data makes the rule inactive.
 *
 * @package YayPricing\Classes\Schedule
 */

namespace YAYDP\Schedule;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Schedule_Evaluator {

	/**
	 * Checks whether the schedule is active at the given time
	 *
	 * @param array          $schedule Schedule data, see YAYDP_Schedule_Definition::get_default().
	 * @param \DateTime|null $check_time Time to check against, default is the current time.
	 * @return bool
	 */
	public function is_schedule_active( $schedule, $check_time = null ) {
		if ( empty( $schedule ) || empty( $schedule['enabled'] ) ) {
			return true;
		}

		$check_time = is_null( $check_time ) ? $this->create_now() : $check_time;
		if ( is_null( $check_time ) ) {
			return false;
		}

		if ( ! $this->is_within_datetime_range(
			isset( $schedule['start_date'] ) ? $schedule['start_date'] : '',
			isset( $schedule['end_date'] ) ? $schedule['end_date'] : '',
			$check_time
		) ) {
			return false;
		}

		$recurring = isset( $schedule['recurring'] ) ? $schedule['recurring'] : array();
		if ( empty( $recurring ) || empty( $recurring['enabled'] ) ) {
			return true;
		}

		return $this->is_recurring_active( $recurring, $check_time );
	}

	/**
	 * Checks the outer date window. Both bounds empty means no boundary.
	 *
	 * @param string    $start_date Y-m-d H:i:s.
	 * @param string    $end_date Y-m-d H:i:s.
	 * @param \DateTime $check_time Time to check against.
	 * @return bool
	 */
	public function is_within_datetime_range( $start_date, $end_date, $check_time ) {
		$start_date = trim( (string) $start_date );
		$end_date   = trim( (string) $end_date );

		if ( '' === $start_date && '' === $end_date ) {
			return true;
		}

		try {
			$timezone = $this->get_timezone();

			// Open-ended: active from the start date onward.
			if ( '' !== $start_date && '' === $end_date ) {
				return $check_time >= new \DateTime( $start_date, $timezone );
			}

			// Open-ended the other way: active up to the end date. The pre 3.6
			// schedule allowed an empty start in its range picker and treated it
			// as no lower bound, so keep that meaning.
			if ( '' === $start_date ) {
				return $check_time <= new \DateTime( $end_date, $timezone );
			}

			$start = new \DateTime( $start_date, $timezone );
			$end   = new \DateTime( $end_date, $timezone );

			return $check_time >= $start && $check_time <= $end;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Checks the time of day window. Supports windows crossing midnight.
	 *
	 * @param string    $start_time H:i:s, empty means no lower bound.
	 * @param string    $end_time H:i:s, empty means no upper bound.
	 * @param \DateTime $check_time Time to check against.
	 * @return bool
	 */
	public function is_within_time_range( $start_time, $end_time, $check_time ) {
		$start_time = trim( (string) $start_time );
		$end_time   = trim( (string) $end_time );

		if ( '' === $start_time && '' === $end_time ) {
			return true;
		}

		$current_time = $check_time->format( 'H:i:s' );

		if ( '' === $end_time ) {
			return $current_time >= $start_time;
		}

		if ( '' === $start_time ) {
			return $current_time <= $end_time;
		}

		if ( $start_time <= $end_time ) {
			return $current_time >= $start_time && $current_time <= $end_time;
		}

		// Window spans midnight.
		return $current_time >= $start_time || $current_time <= $end_time;
	}

	/**
	 * Checks the weekday filter. An empty list means every day matches.
	 *
	 * @param array     $days_of_week List of day numbers, 0 is Sunday.
	 * @param \DateTime $check_time Time to check against.
	 * @return bool
	 */
	public function is_valid_weekday( $days_of_week, $check_time ) {
		// A weekly schedule with no day selected matches nothing. Treating it as
		// "every day" would turn incomplete data into an always running rule.
		if ( ! is_array( $days_of_week ) || empty( $days_of_week ) ) {
			return false;
		}

		$days_of_week = array_map( 'intval', $days_of_week );

		return in_array( (int) $check_time->format( 'w' ), $days_of_week, true );
	}

	/**
	 * Checks the monthly filter, either a day range or an explicit list of days.
	 *
	 * @param array     $recurring Recurring data.
	 * @param \DateTime $check_time Time to check against.
	 * @return bool
	 */
	public function is_within_month_range( $recurring, $check_time ) {
		$days_of_month = isset( $recurring['days_of_month'] ) ? $recurring['days_of_month'] : array();
		$start_day     = isset( $recurring['start_day'] ) && ! is_null( $recurring['start_day'] ) ? (int) $recurring['start_day'] : null;
		$end_day       = isset( $recurring['end_day'] ) && ! is_null( $recurring['end_day'] ) ? (int) $recurring['end_day'] : null;
		$current_day   = (int) $check_time->format( 'd' );

		if ( ! is_null( $start_day ) && ! is_null( $end_day ) && $start_day >= 1 && $end_day >= 1 ) {
			// Clamp the range to the length of the current month so day 31 still matches in February.
			$days_in_month = (int) $check_time->format( 't' );
			$range_start   = min( $start_day, $days_in_month );
			$range_end     = min( $end_day, $days_in_month );

			return $current_day >= $range_start && $current_day <= $range_end;
		}

		if ( empty( $days_of_month ) || ! is_array( $days_of_month ) ) {
			return true;
		}

		return in_array( $current_day, array_map( 'intval', $days_of_month ), true );
	}

	/**
	 * Checks the yearly filter, either a month-day range or an explicit list of month-days.
	 *
	 * @param array     $recurring Recurring data.
	 * @param \DateTime $check_time Time to check against.
	 * @return bool
	 */
	public function is_within_year_range( $recurring, $check_time ) {
		$days_of_year  = isset( $recurring['days_of_year'] ) ? $recurring['days_of_year'] : array();
		$year_start    = trim( isset( $recurring['year_start_month_day'] ) ? (string) $recurring['year_start_month_day'] : '' );
		$year_end      = trim( isset( $recurring['year_end_month_day'] ) ? (string) $recurring['year_end_month_day'] : '' );
		$current_md    = $check_time->format( 'm-d' );

		if ( '' !== $year_start && '' !== $year_end
			&& $this->is_valid_month_day( $year_start )
			&& $this->is_valid_month_day( $year_end ) ) {

			if ( $year_start <= $year_end ) {
				return $current_md >= $year_start && $current_md <= $year_end;
			}

			// Range wrapping over new year, eg. Nov to Feb.
			return $current_md >= $year_start || $current_md <= $year_end;
		}

		if ( empty( $days_of_year ) || ! is_array( $days_of_year ) ) {
			return true;
		}

		return in_array( $current_md, $days_of_year, true );
	}

	/**
	 * Retrieves the end of the occurrence that is running at the given time.
	 * Returns null when the schedule is not active.
	 *
	 * @param array          $schedule Schedule data.
	 * @param \DateTime|null $check_time Time to check against.
	 * @return \DateTime|null
	 */
	public function get_current_window_end( $schedule, $check_time = null ) {
		if ( ! $this->is_schedule_active( $schedule, $check_time ) ) {
			return null;
		}

		$check_time = is_null( $check_time ) ? $this->create_now() : $check_time;
		if ( is_null( $check_time ) ) {
			return null;
		}

		$timezone  = $this->get_timezone();
		$recurring = isset( $schedule['recurring'] ) ? $schedule['recurring'] : array();

		if ( empty( $recurring ) || empty( $recurring['enabled'] ) ) {
			$end_date = trim( isset( $schedule['end_date'] ) ? (string) $schedule['end_date'] : '' );
			return '' === $end_date ? null : $this->build_datetime( $end_date, '', $timezone );
		}

		$end_time = $this->normalize_end_time( isset( $recurring['end_time'] ) ? $recurring['end_time'] : '' );
		$type     = empty( $recurring['type'] ) ? YAYDP_Schedule_Definition::RECURRING_WEEKLY : $recurring['type'];

		if ( YAYDP_Schedule_Definition::RECURRING_MONTHLY === $type ) {
			$end_day = isset( $recurring['end_day'] ) && ! is_null( $recurring['end_day'] ) ? (int) $recurring['end_day'] : null;
			if ( ! is_null( $end_day ) && $end_day >= 1 ) {
				$day = min( $end_day, (int) $check_time->format( 't' ) );
				return $this->build_datetime( $check_time->format( 'Y-m-' ) . sprintf( '%02d', $day ), $end_time, $timezone );
			}
			return $this->build_datetime( $check_time->format( 'Y-m-d' ), $end_time, $timezone );
		}

		if ( YAYDP_Schedule_Definition::RECURRING_YEARLY === $type ) {
			$year_end = trim( isset( $recurring['year_end_month_day'] ) ? (string) $recurring['year_end_month_day'] : '' );
			if ( '' !== $year_end && $this->is_valid_month_day( $year_end ) ) {
				return $this->build_datetime( $check_time->format( 'Y-' ) . $year_end, $end_time, $timezone );
			}
			return $this->build_datetime( $check_time->format( 'Y-m-d' ), $end_time, $timezone );
		}

		if ( in_array( $type, array( YAYDP_Schedule_Definition::RECURRING_DAILY, YAYDP_Schedule_Definition::RECURRING_WEEKLY ), true ) ) {
			return $this->build_datetime( $check_time->format( 'Y-m-d' ), $end_time, $timezone );
		}

		return null;
	}

	/**
	 * Checks the recurring part of a schedule
	 *
	 * @param array     $recurring Recurring data.
	 * @param \DateTime $check_time Time to check against.
	 * @return bool
	 */
	private function is_recurring_active( $recurring, $check_time ) {
		$type = empty( $recurring['type'] ) ? YAYDP_Schedule_Definition::RECURRING_WEEKLY : $recurring['type'];

		switch ( $type ) {
			case YAYDP_Schedule_Definition::RECURRING_DAILY:
				// The outer date range is already checked, daily only restricts the time of day.
				break;
			case YAYDP_Schedule_Definition::RECURRING_WEEKLY:
				if ( ! $this->is_valid_weekday( isset( $recurring['days_of_week'] ) ? $recurring['days_of_week'] : array(), $check_time ) ) {
					return false;
				}
				break;
			case YAYDP_Schedule_Definition::RECURRING_MONTHLY:
				if ( ! $this->is_within_month_range( $recurring, $check_time ) ) {
					return false;
				}
				break;
			case YAYDP_Schedule_Definition::RECURRING_YEARLY:
				if ( ! $this->is_within_year_range( $recurring, $check_time ) ) {
					return false;
				}
				break;
			default:
				return false;
		}

		if ( empty( $recurring['start_time'] ) && empty( $recurring['end_time'] ) ) {
			return true;
		}

		return $this->is_within_time_range(
			isset( $recurring['start_time'] ) ? $recurring['start_time'] : '',
			isset( $recurring['end_time'] ) ? $recurring['end_time'] : '',
			$check_time
		);
	}

	/**
	 * Builds a DateTime from a date and an optional time
	 *
	 * @param string        $date Y-m-d or a full datetime.
	 * @param string        $time H:i:s, may be empty.
	 * @param \DateTimeZone $timezone Timezone.
	 * @return \DateTime|null
	 */
	private function build_datetime( $date, $time, $timezone ) {
		try {
			return new \DateTime( '' === $time ? $date : $date . ' ' . $time, $timezone );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Defaults an empty end time to the end of the day
	 *
	 * @param string $end_time Raw end time.
	 * @return string
	 */
	private function normalize_end_time( $end_time ) {
		$end_time = trim( (string) $end_time );
		if ( '' === $end_time ) {
			return '23:59:59';
		}
		if ( preg_match( '/^\d{1,2}:\d{2}$/', $end_time ) ) {
			return $end_time . ':59';
		}
		return $end_time;
	}

	/**
	 * Checks whether a string is a valid mm-dd value
	 *
	 * @param string $month_day Value to check.
	 * @return bool
	 */
	private function is_valid_month_day( $month_day ) {
		return (bool) preg_match( '/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $month_day );
	}

	/**
	 * Retrieves the current time in the site timezone
	 *
	 * @return \DateTime|null
	 */
	private function create_now() {
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
