<?php
/**
 * Upgrades stored rules from the flat schedule shape to the nested one
 *
 * Legacy shape: schedule { enable, start, end } + schedule_recurring { type }
 * New shape:    schedule { enabled, start_date, end_date, recurring { ... } }
 *
 * The mapping keeps the behaviour of the old evaluation: a recurring rule derived
 * its active days from the start/end datetimes and ignored the datetimes themselves,
 * so the derived days are written out explicitly and the datetimes are cleared.
 *
 * @package YayPricing\Classes\Schedule
 */

namespace YAYDP\Schedule;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Schedule_Migration {

	const VERSION_OPTION = 'yaydp_schedule_schema_version';
	const VERSION        = 2;

	/**
	 * Options holding the rules that carry a schedule.
	 *
	 * @var array
	 */
	private static $rule_options = array(
		'yaydp_product_pricing_rules',
		'yaydp_cart_discount_rules',
		'yaydp_checkout_fee_rules',
		'yaydp_removed_product_pricing_rules',
		'yaydp_removed_cart_discount_rules',
		'yaydp_removed_checkout_fee_rules',
	);

	/**
	 * Runs the upgrade once per site
	 */
	public static function maybe_migrate() {
		if ( (int) get_option( self::VERSION_OPTION, 0 ) >= self::VERSION ) {
			return;
		}

		foreach ( self::$rule_options as $option ) {
			$rules = get_option( $option, array() );
			if ( ! is_array( $rules ) || empty( $rules ) ) {
				continue;
			}
			update_option( $option, array_map( array( __CLASS__, 'migrate_rule' ), $rules ) );
		}

		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * Converts the schedule of a single rule
	 *
	 * @param mixed $rule Stored rule.
	 * @return mixed
	 */
	public static function migrate_rule( $rule ) {
		if ( ! is_array( $rule ) || ! isset( $rule['schedule'] ) || ! is_array( $rule['schedule'] ) ) {
			return $rule;
		}

		// Already using the new shape.
		if ( array_key_exists( 'enabled', $rule['schedule'] ) ) {
			unset( $rule['schedule_recurring'] );
			return $rule;
		}

		$legacy         = $rule['schedule'];
		$recurring_type = isset( $rule['schedule_recurring']['type'] ) ? $rule['schedule_recurring']['type'] : 'off';
		$schedule       = YAYDP_Schedule_Definition::get_default();

		$schedule['enabled']    = ! empty( $legacy['enable'] );
		$schedule['start_date'] = self::normalize_date( isset( $legacy['start'] ) ? $legacy['start'] : null );
		$schedule['end_date']   = self::normalize_date( isset( $legacy['end'] ) ? $legacy['end'] : null );

		if ( in_array( $recurring_type, array( 'weekly', 'monthly', 'yearly' ), true ) ) {
			$schedule['recurring'] = self::build_recurring(
				$recurring_type,
				$schedule['start_date'],
				$schedule['end_date']
			);
			// The old evaluation ignored the datetimes for recurring rules.
			$schedule['start_date'] = '';
			$schedule['end_date']   = '';
		}

		$rule['schedule'] = $schedule;
		unset( $rule['schedule_recurring'] );

		return $rule;
	}

	/**
	 * Builds the recurring data derived from the legacy start and end datetimes
	 *
	 * @param string $type weekly, monthly or yearly.
	 * @param string $start_date Y-m-d H:i:s.
	 * @param string $end_date Y-m-d H:i:s.
	 * @return array
	 */
	private static function build_recurring( $type, $start_date, $end_date ) {
		$recurring            = YAYDP_Schedule_Definition::get_default_recurring();
		$recurring['enabled'] = true;
		$recurring['type']    = $type;

		$start = self::to_datetime( $start_date );
		$end   = self::to_datetime( $end_date );
		if ( is_null( $start ) || is_null( $end ) ) {
			return $recurring;
		}

		if ( 'weekly' === $type ) {
			// The old check compared ISO weekdays (Monday is 1, Sunday is 7) as a range.
			// A range whose start weekday falls after its end weekday, eg. Friday to
			// Tuesday, never matched anything back then; wrap it over the weekend so
			// the days the merchant picked survive the upgrade.
			$start_iso = (int) $start->format( 'N' );
			$end_iso   = (int) $end->format( 'N' );
			$length    = ( ( $end_iso - $start_iso + 7 ) % 7 ) + 1;
			$days      = array();
			for ( $offset = 0; $offset < $length; $offset++ ) {
				$iso     = ( ( $start_iso - 1 + $offset ) % 7 ) + 1;
				$days[]  = 7 === $iso ? 0 : $iso;
			}
			$recurring['days_of_week'] = $days;
			return $recurring;
		}

		if ( 'monthly' === $type ) {
			$recurring['start_day'] = (int) $start->format( 'j' );
			$recurring['end_day']   = (int) $end->format( 'j' );
			return $recurring;
		}

		$recurring['year_start_month_day'] = $start->format( 'm-d' );
		$recurring['year_end_month_day']   = $end->format( 'm-d' );

		return $recurring;
	}

	/**
	 * Converts a legacy ISO datetime into the stored Y-m-d H:i:s format
	 *
	 * @param string|null $value Raw value.
	 * @return string
	 */
	private static function normalize_date( $value ) {
		$date = self::to_datetime( $value );
		return is_null( $date ) ? '' : $date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Parses a datetime string
	 *
	 * @param string|null $value Raw value.
	 * @return \DateTime|null
	 */
	private static function to_datetime( $value ) {
		if ( empty( $value ) ) {
			return null;
		}
		try {
			return new \DateTime( (string) $value );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
