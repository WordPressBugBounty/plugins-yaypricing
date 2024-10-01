<?php
/**
 * Defines the constants for countdown timer settings
 *
 * @package YayPricing\Constants
 */

namespace YAYDP\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Countdown_Timer {

	/**
	 * Get default settings
	 */
	public static function get_default() {
		return array(
			'enable'     => false,
			'start_text' => '<p>[campaign_name] is available in [timer]</p>',
			'end_text'   => '<p>[campaign_name] ends in [timer]</p>',
		);
	}
}
