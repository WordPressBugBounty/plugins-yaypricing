<?php
/**
 * This class is responsible for creating instances of cart discount rules for checkout process
 *
 * @package YAYDP\Factory
 */

namespace YAYDP\Factory;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Cart_Discount_Rule_Factory extends \YAYDP\Abstracts\YAYDP_Rule_Factory {

	/**
	 * This function takes a rule data as input with the given type in that data and returns an instance of the corresponding
	 *
	 * @param array $rule_data Rule data.
	 */
	public static function get_rule( $rule_data ) {
		$path       = '\YAYDP\Core\Rule\Cart_Discount';
		$rule_class = "{$path}\YAYDP_Simple_Discount";

		return new $rule_class( $rule_data );

	}

}
