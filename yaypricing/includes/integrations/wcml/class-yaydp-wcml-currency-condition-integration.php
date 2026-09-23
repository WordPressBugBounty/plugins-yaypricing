<?php
/**
 * Handles the integration of WCML multi-currency with YayPricing conditions
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WCML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds "WCML current currency" as a rule condition
 * so admins can restrict pricing rules to specific currencies.
 */
class YAYDP_WCML_Currency_Condition_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor — only hooks when WCML multi-currency is active
	 */
	protected function __construct() {
		if ( ! function_exists( 'wcml_is_multi_currency_on' ) || ! wcml_is_multi_currency_on() ) {
			return;
		}

		add_action( 'yaydp_register_conditions', array( $this, 'register_conditions' ) );
	}

	/**
	 * Register this integration's condition types.
	 *
	 * @param \YAYDP\Condition\YAYDP_Condition_Registry $registry Registry.
	 */
	public function register_conditions( $registry ) {
		$registry->register( new YAYDP_WCML_Currency_Condition() );
	}
}
