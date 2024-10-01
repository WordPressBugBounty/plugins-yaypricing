<?php
/**
 * This class is responsible for creating instances of exclude rules
 *
 * @package YAYDP\Factory
 */

namespace YAYDP\Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Declare class */
class YAYDP_Exclude_Rule_Factory extends \YAYDP\Abstracts\YAYDP_Rule_Factory {

	/**
	 * This function takes a rule data as input with the given type in that data and returns an instance of the corresponding
	 *
	 * @param array $rule_data Rule data.
	 */
	public static function get_rule( $rule_data ) {

		$path       = '\YAYDP\Core\Rule\Exclude';
		$type       = isset( $rule_data['type'] ) ? $rule_data['type'] : null;
		$rule_class = "{$path}\YAYDP_Simple_Exclude";

		if ( 'coupon_exclusions' === $type ) {
			$rule_class = "{$path}\YAYDP_Coupon_Exclude";
		}

		return new $rule_class( $rule_data );

	}

}
