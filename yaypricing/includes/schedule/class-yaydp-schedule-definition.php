<?php
/**
 * Defines the schedule data structure and its constants
 *
 * @package YayPricing\Classes\Schedule
 */

namespace YAYDP\Schedule;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Schedule_Definition {

	const RECURRING_DAILY   = 'daily';
	const RECURRING_WEEKLY  = 'weekly';
	const RECURRING_MONTHLY = 'monthly';
	const RECURRING_YEARLY  = 'yearly';

	const SUNDAY    = 0;
	const MONDAY    = 1;
	const TUESDAY   = 2;
	const WEDNESDAY = 3;
	const THURSDAY  = 4;
	const FRIDAY    = 5;
	const SATURDAY  = 6;

	/**
	 * Retrieves the allowed recurring types
	 *
	 * @return array
	 */
	public static function get_recurring_types() {
		return array(
			self::RECURRING_DAILY,
			self::RECURRING_WEEKLY,
			self::RECURRING_MONTHLY,
			self::RECURRING_YEARLY,
		);
	}

	/**
	 * Retrieves the day names, indexed by the day number used in the schedule data
	 *
	 * @return array
	 */
	public static function get_day_names() {
		return array(
			self::SUNDAY    => __( 'Sunday', 'yaypricing' ),
			self::MONDAY    => __( 'Monday', 'yaypricing' ),
			self::TUESDAY   => __( 'Tuesday', 'yaypricing' ),
			self::WEDNESDAY => __( 'Wednesday', 'yaypricing' ),
			self::THURSDAY  => __( 'Thursday', 'yaypricing' ),
			self::FRIDAY    => __( 'Friday', 'yaypricing' ),
			self::SATURDAY  => __( 'Saturday', 'yaypricing' ),
		);
	}

	/**
	 * Retrieves the default recurring structure
	 *
	 * @return array
	 */
	public static function get_default_recurring() {
		return array(
			'enabled'              => false,
			'type'                 => self::RECURRING_WEEKLY,
			'days_of_week'         => array(),
			'days_of_month'        => array(),
			'days_of_year'         => array(),
			'start_day'            => null,
			'end_day'              => null,
			'year_start_month_day' => '',
			'year_end_month_day'   => '',
			'start_time'           => '',
			'end_time'             => '',
		);
	}

	/**
	 * Retrieves the default schedule structure
	 *
	 * @return array
	 */
	public static function get_default() {
		return array(
			'enabled'    => false,
			'start_date' => '',
			'end_date'   => '',
			'recurring'  => self::get_default_recurring(),
		);
	}

	/**
	 * Fills the missing keys of a schedule with their default value
	 *
	 * @param mixed $schedule Raw schedule data.
	 * @return array
	 */
	public static function normalize( $schedule ) {
		if ( ! is_array( $schedule ) ) {
			return self::get_default();
		}
		$normalized              = array_merge( self::get_default(), $schedule );
		$recurring               = isset( $schedule['recurring'] ) && is_array( $schedule['recurring'] ) ? $schedule['recurring'] : array();
		$normalized['recurring'] = array_merge( self::get_default_recurring(), $recurring );
		return $normalized;
	}
}
