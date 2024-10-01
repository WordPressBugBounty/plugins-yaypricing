<?php
/**
 * This class is responsible for managing the exclusions in the YAYDP system.
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\Core\Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Exclude_Manager {

	/**
	 * Check exclusions for product
	 */
	public static function check_product_exclusions( $checking_rule, $product ) {
		foreach ( \yaydp_get_running_exclude_rules() as $rule ) {
			if ( $rule->check_exclude( $checking_rule, $product ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check exclusions for coupon
	 */
	public static function check_coupon_exclusions( $checking_rule ) {
		// Note: Lock in LITE version.
		return false;
	}

}
