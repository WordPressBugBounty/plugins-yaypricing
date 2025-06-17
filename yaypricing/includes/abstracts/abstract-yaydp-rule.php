<?php
/**
 * Abstract class that defines the basic structure and functionality of a rule
 *
 * @package YayPricing\Abstract
 */

namespace YAYDP\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
abstract class YAYDP_Rule {

	/**
	 * Rule data
	 *
	 * @var array
	 */
	protected $data = null;

	/**
	 * Constructor
	 *
	 * @param array $data Given rule data.
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Retrieves the data stored in the $data variable.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Retrieves rule id.
	 *
	 * @return string
	 */
	public function get_id() {
		return ! empty( $this->data['id'] ) ? $this->data['id'] : '';
	}

	/**
	 * Retrieve rule randomize id
	 * It is not the normal id. It is the short one
	 */
	public function get_rule_id() {
		return ! empty( $this->data['rule_id'] ) ? $this->data['rule_id'] : '';
	}

	/**
	 * Retrieves rule name.
	 *
	 * @return string
	 */
	public function get_name() {
		return ! empty( $this->data['name'] ) ? $this->data['name'] : '';
	}

	/**
	 * Retrieves rule type.
	 *
	 * @return string
	 */
	public function get_type() {
		return ! empty( $this->data['type'] ) ? $this->data['type'] : '';
	}

	/**
	 * Retrieves the conditional logics
	 *
	 * @return array
	 */
	public function get_conditions() {
		return ! empty( $this->data['conditions']['logics'] ) ? $this->data['conditions']['logics'] : array();
	}

	/**
	 * Retrieves the condition match type.
	 *
	 * @return string
	 */
	public function get_condition_match_type() {
		return ! empty( $this->data['conditions']['match_type'] ) ? $this->data['conditions']['match_type'] : 'any';
	}

	/**
	 * Checks if a rule is currently running
	 *
	 * @return bool
	 */
	public function is_running() {
		$check = $this->is_enabled() && $this->is_in_schedule() && ! $this->is_reach_limit_uses();
		return $check;
	}

	/**
	 * Checks if a rule is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->data['is_enabled'] );
	}

	/**
	 * Checks if a rule is within the schedule
	 *
	 * @return bool
	 */
	public function is_in_schedule() {
		if ( ! $this->is_enabled_schedule() ) {
			return true;
		}
		$is_enabled_schedule_recurring = $this->is_enabled_schedule_recurring();

		$start        = is_null( $this->data['schedule']['start'] ) ? '1999-01-30T14:59:23+07:00Z' : $this->data['schedule']['start'];
		$end          = is_null( $this->data['schedule']['end'] ) ? '3000-01-30T14:59:23+07:00Z' : $this->data['schedule']['end'];
		$current_date = new \DateTime('now', new \DateTimeZone( wp_timezone_string() ) );
		$start_date   = new \DateTime( $start, new \DateTimeZone( wp_timezone_string() ) );
		$end_date     = new \DateTime( $end, new \DateTimeZone( wp_timezone_string() ) );

		if ( $is_enabled_schedule_recurring ) {
			$schedule_recurring_type = $this->get_schedule_recurring_type();
			if ( 'weekly' == $schedule_recurring_type ) {
				$current_day_of_week = $current_date->format( 'N' );
				$start_day_of_week   = $start_date->format( 'N' );
				$end_day_of_week     = $end_date->format( 'N' );

				return $current_day_of_week >= $start_day_of_week && $current_day_of_week <= $end_day_of_week;
			} elseif ( 'monthly' == $schedule_recurring_type ) {
				$current_day = $current_date->format( 'j' );
				$start_day   = $start_date->format( 'j' );
				$end_day     = $end_date->format( 'j' );

				return $current_day >= $start_day && $current_day <= $end_day;
			} elseif ( 'yearly' == $schedule_recurring_type ) {
				$current_month = $current_date->format( 'n' );
				$start_month   = $start_date->format( 'n' );
				$end_month     = $end_date->format( 'n' );
				if ( $current_month >= $start_month && $current_month <= $end_month ) {
					$current_day_of_year = $current_date->format( 'z' );
					$start_day_of_year   = $start_date->format( 'z' );
					$end_day_of_year     = $end_date->format( 'z' );

					return $current_day_of_year >= $start_day_of_year && $current_day_of_year <= $end_day_of_year;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return $current_date >= $start_date && $current_date <= $end_date;
		}
	}

	/**
	 * Checks if a rule is reach the limit use time
	 *
	 * @return bool
	 */
	public function is_reach_limit_uses() {
		if ( empty( $this->data['maximum_uses']['enable'] ) ) {
			return false;
		}
		if ( $this->data['use_time'] >= $this->data['maximum_uses']['value'] ) {
			return true;
		}
		return false;

	}

	/**
	 * Retrieves rule pricing type
	 */
	public function get_pricing_type() {
		return ! empty( $this->data['pricing']['type'] ) ? $this->data['pricing']['type'] : 'fixed_discount';
	}

	/**
	 * Retrieves rule pricing value
	 */
	public function get_pricing_value() {
		return ! empty( $this->data['pricing']['value'] ) ? $this->data['pricing']['value'] : 0;
	}

	/**
	 * Retrieves rule maximum discount amount
	 */
	public function get_maximum_adjustment_amount() {
		$maximum_value = $this->data['pricing']['maximum_value'];
		return is_null( $maximum_value ) ? PHP_INT_MAX : $maximum_value;
	}

	/**
	 * Check whether given cart match rule conditions
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function check_conditions( $cart ) {
		return \YAYDP\Helper\YAYDP_Condition_Helper::check_conditions( $cart, $this );
	}

	/**
	 * Get rule tooltip.
	 */
	public function get_tooltip( $modifier = null ) {
		$tooltip_data = empty( $this->data['tooltip'] ) ? array() : $this->data['tooltip'];
		if ( $this instanceof YAYDP_Product_Pricing_Rule ) {
			return new \YAYDP\Core\Tooltip\YAYDP_Product_Pricing_Tooltip( $tooltip_data, $modifier );
		}
		if ( $this instanceof YAYDP_Cart_Discount_Rule ) {
			return new \YAYDP\Core\Tooltip\YAYDP_Cart_Discount_Tooltip( $tooltip_data, $this );
		}
		if ( $this instanceof YAYDP_Checkout_Fee_Rule ) {
			return new \YAYDP\Core\Tooltip\YAYDP_Checkout_Fee_Tooltip( $tooltip_data, $this );
		}
		return null;
	}

	/**
	 * Increase use time of rule
	 */
	public function increase_use_time() {
		$this->data['use_time']++;
	}

	/**
	 * Calculate all conditions encouragements can be created by rule
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_conditions_encouragements( \YAYDP\Core\YAYDP_Cart $cart ) {
		return \YAYDP\Helper\YAYDP_Incomplete_Condition_Helper::get_incomplete_conditions( $cart, $this );
	}

	/**
	 * Check whether the rule not start yet
	 */
	public function is_upcoming() {
		$current_date = new \DateTime();
		$start        = empty( $this->data['schedule']['start'] ) ? '' : $this->data['schedule']['start'];
		$start_date   = new \DateTime( empty( $start ) ? '1999-01-30T14:59:23+07:00Z' : $start, new \DateTimeZone( wp_timezone_string() ) );

		if ( $this->is_enabled_schedule_recurring() ) {
			$schedule_recurring_type = $this->get_schedule_recurring_type();
			if ( 'weekly' == $schedule_recurring_type ) {
				$current_day_of_week = $current_date->format( 'N' );
				$start_day_of_week   = $start_date->format( 'N' );
				return $current_day_of_week < $start_day_of_week;
			} elseif ( 'monthly' == $schedule_recurring_type ) {
				$current_day = $current_date->format( 'j' );
				$start_day   = $start_date->format( 'j' );

				return $current_day < $start_day;
			} elseif ( 'yearly' == $schedule_recurring_type ) {
				$current_month = $current_date->format( 'n' );
				$start_month   = $start_date->format( 'n' );

				if ( $current_month < $start_month ) {
					return true;
				}

				$current_day_of_year = $current_date->format( 'z' );
				$start_day_of_year   = $start_date->format( 'z' );

				return $current_day_of_year < $start_day_of_year;

			} else {
				return false;
			}
		}

		if ( $current_date < $start_date && ! empty( $start ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check whether the rule enables schedule
	 */
	public function is_enabled_schedule() {
		if ( ! empty( $this->data['schedule']['enable'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check whether the rule enables schedule recurring
	 */
	public function is_enabled_schedule_recurring() {
		if ( empty( $this->data['schedule_recurring']['type'] ) ) {
			return false;
		}
		return in_array( $this->data['schedule_recurring']['type'], array( 'weekly', 'monthly', 'yearly' ) );
	}

	/**
	 * Gets recurring type of schedule
	 *
	 * @return string weekly | monthly | yearly
	 */
	public function get_schedule_recurring_type() {
		return $this->data['schedule_recurring']['type'];
	}

	/**
	 * Check whether the rule has end time
	 */
	public function is_end_in_future() {
		$current_date = new \DateTime();
		$start        = empty( $this->data['schedule']['start'] ) ? '' : $this->data['schedule']['start'];
		$start_date   = new \DateTime( empty( $start ) ? '1999-01-30T14:59:23+07:00Z' : $start, new \DateTimeZone( wp_timezone_string() ) );
		$end          = empty( $this->data['schedule']['end'] ) ? '' : $this->data['schedule']['end'];
		$end_date     = new \DateTime( empty( $end ) ? '3000-01-30T14:59:23+07:00Z' : $end, new \DateTimeZone( wp_timezone_string() ) );

		if ( $this->is_enabled_schedule_recurring() ) {
			$schedule_recurring_type = $this->get_schedule_recurring_type();
			if ( 'weekly' == $schedule_recurring_type ) {
				$current_day_of_week = $current_date->format( 'N' );
				$start_day_of_week   = $start_date->format( 'N' );
				$end_day_of_week     = $end_date->format( 'N' );

				return $current_day_of_week >= $start_day_of_week && $current_day_of_week <= $end_day_of_week;
			} elseif ( 'monthly' == $schedule_recurring_type ) {
				$current_day = $current_date->format( 'j' );
				$start_day   = $start_date->format( 'j' );
				$end_day     = $end_date->format( 'j' );

				return $current_day >= $start_day && $current_day <= $end_day;
			} elseif ( 'yearly' == $schedule_recurring_type ) {
				$current_month = $current_date->format( 'n' );
				$start_month   = $start_date->format( 'n' );
				$end_month     = $end_date->format( 'n' );
				if ( $current_month >= $start_month && $current_month <= $end_month ) {
					$current_day_of_year = $current_date->format( 'z' );
					$start_day_of_year   = $start_date->format( 'z' );
					$end_day_of_year     = $end_date->format( 'z' );

					return $current_day_of_year >= $start_day_of_year && $current_day_of_year <= $end_day_of_year;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		if ( $current_date >= $start_date && $current_date <= $end_date && ! empty( $end ) ) {
			return true;
		}
		return false;
	}

}
