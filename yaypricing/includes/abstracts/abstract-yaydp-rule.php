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
	 * Schedule manager, created on first use
	 *
	 * @var \YAYDP\Schedule\YAYDP_Schedule_Manager|null
	 */
	protected $schedule_manager = null;

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
	 * Retrieves rule name translated with WPML, Polylang or the plugin text domain
	 * (Loco Translate). Use this for customer-facing output only; get_name() stays
	 * untranslated so coupon codes and stored rule matching remain language-agnostic.
	 *
	 * @return string
	 */
	public function get_translated_name() {
		$rule_id = $this->get_id();
		$name    = empty( $rule_id ) ? '' : "rule_{$rule_id}_name";
		return \YAYDP\Helper\YAYDP_Helper::translate_user_string( $this->get_name(), 'yaypricing', $name );
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
	 * Retrieves the rule schedule, with every missing key filled with its default.
	 *
	 * Rules still carrying the pre 3.6 shape are converted on read, so a rule that
	 * the stored data upgrade has not reached yet keeps its schedule instead of
	 * being treated as unscheduled and running unconditionally.
	 *
	 * @return array
	 */
	public function get_schedule() {
		$data = \YAYDP\Schedule\YAYDP_Schedule_Migration::migrate_rule( $this->data );
		return \YAYDP\Schedule\YAYDP_Schedule_Definition::normalize( isset( $data['schedule'] ) ? $data['schedule'] : array() );
	}

	/**
	 * Retrieves the schedule manager
	 *
	 * @return \YAYDP\Schedule\YAYDP_Schedule_Manager
	 */
	protected function get_schedule_manager() {
		if ( is_null( $this->schedule_manager ) ) {
			$this->schedule_manager = new \YAYDP\Schedule\YAYDP_Schedule_Manager();
		}
		return $this->schedule_manager;
	}

	/**
	 * Checks if a rule is within the schedule
	 *
	 * @return bool
	 */
	public function is_in_schedule() {
		return $this->get_schedule_manager()->is_active( $this->get_schedule() );
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
		$use_time = (int) ( $this->data['use_time'] ?? 0 );
		$limit    = (int) ( $this->data['maximum_uses']['value'] ?? 0 );
		return $use_time >= $limit;
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
		return \YAYDP\Condition\YAYDP_Condition_Registry::instance()->evaluate_list(
			$this->get_conditions(),
			$this->get_condition_match_type(),
			\YAYDP\Condition\YAYDP_Condition_Context::from_rule( $cart, $this )
		);
	}

	/**
	 * Get rule tooltip.
	 */
	public function get_tooltip( $modifier = null ) {
		$tooltip_data = empty( $this->data['tooltip'] ) ? array() : $this->data['tooltip'];
		if ( $this instanceof YAYDP_Product_Pricing_Rule ) {
			return new \YAYDP\Core\Tooltip\YAYDP_Product_Pricing_Tooltip( $tooltip_data, $this, $modifier );
		}
		if ( $this instanceof YAYDP_Cart_Discount_Rule || $this instanceof YAYDP_Checkout_Fee_Rule ) {
			return new \YAYDP\Core\Tooltip\YAYDP_Rule_Tooltip( $tooltip_data, $this );
		}
		return null;
	}

	/**
	 * Increase use time of rule
	 */
	public function increase_use_time() {
		$this->data['use_time'] = (int) ( $this->data['use_time'] ?? 0 ) + 1;
	}

	/**
	 * Calculate all conditions encouragements can be created by rule
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_conditions_encouragements( \YAYDP\Core\YAYDP_Cart $cart ) {
		return \YAYDP\Condition\YAYDP_Condition_Registry::instance()->incomplete_for(
			$this->get_conditions(),
			$this->get_condition_match_type(),
			\YAYDP\Condition\YAYDP_Condition_Context::from_rule( $cart, $this )
		);
	}

	/**
	 * Check whether the rule not start yet
	 *
	 * @return bool
	 */
	public function is_upcoming() {
		if ( ! $this->is_enabled_schedule() ) {
			return false;
		}
		$next = $this->get_schedule_manager()->get_next_active_time( $this->get_schedule() );
		return 'found_next_active' === $next['reason'];
	}

	/**
	 * Check whether the rule enables schedule
	 *
	 * @return bool
	 */
	public function is_enabled_schedule() {
		$schedule = $this->get_schedule();
		return ! empty( $schedule['enabled'] );
	}

	/**
	 * Check whether the rule enables schedule recurring
	 *
	 * @return bool
	 */
	public function is_enabled_schedule_recurring() {
		$schedule = $this->get_schedule();
		return ! empty( $schedule['recurring']['enabled'] );
	}

	/**
	 * Gets recurring type of schedule
	 *
	 * @return string daily | weekly | monthly | yearly
	 */
	public function get_schedule_recurring_type() {
		$schedule = $this->get_schedule();
		return $schedule['recurring']['type'];
	}

	/**
	 * Check whether the rule is running and has an end time in the future
	 *
	 * @return bool
	 */
	public function is_end_in_future() {
		$window_end = $this->get_schedule_manager()->get_current_window_end( $this->get_schedule() );
		if ( is_null( $window_end ) ) {
			return false;
		}
		return $window_end > new \DateTime( 'now', new \DateTimeZone( wp_timezone_string() ) );
	}

}
