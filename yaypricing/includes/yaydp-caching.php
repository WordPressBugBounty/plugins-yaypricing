<?php
/**
 * Handles caching
 *
 * @package YayPricing
 */

namespace YAYDP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Caching {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'yaydp_after_saving_data', 'yaydp_clear_cache' );
		add_action( 'yaydp_after_saving_data', array( $this, 'handle_schedule_cache' ) );
	}

	private function get_all_running_rules() {

		$rules         = yaydp_get_product_pricing_rules();
		$rules         = array_merge( $rules, yaydp_get_cart_discount_rules() );
		$rules         = array_merge( $rules, yaydp_get_checkout_fee_rules() );
		$running_rules = array_filter(
			$rules,
			function( $rule ) {
				return $rule->is_enabled();
			}
		);

		return $running_rules;
	}

	public function handle_schedule_cache() {
		$running_rules = $this->get_all_running_rules();

		if ( ! function_exists( '_get_cron_array' ) ) {
			return;
		}

		foreach ( _get_cron_array() as $time_stamp => $hooks ) {
			foreach ( array_keys( $hooks ) as $hook_name ) {
				if ( 'yaydp_clear_cache' === $hook_name ) {
					wp_unschedule_event( $time_stamp, $hook_name );
				}
			}
		}

		foreach ( $running_rules as $rule ) {
			if ( ! $rule->is_enabled_schedule() ) {
				continue;
			}
			$rule_data = $rule->get_data();
			if ( ! empty( $rule_data['schedule']['start'] ) && strtotime( $rule_data['schedule']['start'] ) > strtotime( 'now' ) ) {
				wp_schedule_event( strtotime( $rule_data['schedule']['start'] ), 'hourly', 'yaydp_clear_cache' );
			}
			if ( ! empty( $rule_data['schedule']['end'] ) && strtotime( $rule_data['schedule']['end'] ) > strtotime( 'now' ) ) {
				wp_schedule_event( strtotime( $rule_data['schedule']['end'] ), 'hourly', 'yaydp_clear_cache' );
			}
		}
	}
}

YAYDP_Caching::get_instance();
