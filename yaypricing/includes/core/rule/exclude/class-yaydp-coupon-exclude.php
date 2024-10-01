<?php
/**
 * Represents a class for managing coupon exclusions in YAYDP
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\Core\Rule\Exclude;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Coupon_Exclude extends \YAYDP\Abstracts\YAYDP_Exclude_Rule {

	/**
	 * Get list coupons for checking
	 *
	 * @return array
	 */
	public function get_coupon_condition() {
		return isset( $this->data['coupon_condition'] ) ? $this->data['coupon_condition'] : array();
	}

	/**
	 * Check if current applied coupon matching coupon condition
	 *
	 * @return bool
	 */
	public function have_coupon_applied() {
		// Note: Lock in LITE version.
		return false;
	}

	/**
	 * Check if given rule is excluded
	 *
	 * @param object $checking_rule Given rule.
	 *
	 * @return bool
	 */
	public function check_exclude( $checking_rule, $product = null ) {
		// Note: Lock in LITE version.
		return false;
	}
}
