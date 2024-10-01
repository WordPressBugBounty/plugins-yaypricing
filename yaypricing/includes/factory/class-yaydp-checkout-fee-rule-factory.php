<?php
/**
 * This class is responsible for creating instances of checkout fee rules for checkout process
 *
 * @package YAYDP\Factory
 */

namespace YAYDP\Factory;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Checkout_Fee_Rule_Factory extends \YAYDP\Abstracts\YAYDP_Rule_Factory {

	/**
	 * This function takes a rule data as input with the given type in that data and returns an instance of the corresponding
	 *
	 * @param array $rule_data Rule data.
	 */
	public static function get_rule( $rule_data ) {
		$path       = '\YAYDP\Core\Rule\Checkout_Fee';
		$type       = isset( $rule_data['type'] ) ? $rule_data['type'] : null;
		$rule_class = null;
		if ( 'shipping_fee' === $type ) {
			$rule_class = "{$path}\YAYDP_Shipping_Fee";
		}
		if ( 'custom_fee' === $type ) {
			$rule_class = "{$path}\YAYDP_Custom_Fee";
		}
		// if ( 'custom_shipping_fee' === $type ) {
		// 	$rule_class = "{$path}\YAYDP_Custom_Shipping_Fee";
		// }
		if ( is_null( $rule_class ) ) {
			return null;
		}

		return new $rule_class( $rule_data );

	}

}
