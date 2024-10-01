<?php
/**
 * YayPricing functions for cart discount
 *
 * Declare global functions
 *
 * @package YayPricing\Functions
 * @since 2.4
 */

if ( ! function_exists( 'yaydp_get_cart_discount_rules' ) ) {
	/**
	 * Get all cart discount rules
	 */
	function yaydp_get_cart_discount_rules() {
		$database_data = get_option( 'yaydp_cart_discount_rules' );
		return array_map(
			function( $data ) {
				return \YAYDP\Factory\YAYDP_Cart_Discount_Rule_Factory::get_rule( $data );
			},
			empty( $database_data ) ? array() : $database_data
		);
	}
}

if ( ! function_exists( 'yaydp_get_running_cart_discount_rules' ) ) {
	/**
	 * Get all running cart discount rules
	 */
	function yaydp_get_running_cart_discount_rules() {
		$rules = \yaydp_get_cart_discount_rules();
		return array_filter(
			$rules,
			function ( $rule ) {
				return $rule->is_running();
			}
		);
	}
}

if ( ! function_exists( 'yaydp_cart_discount_is_applied_all_rules' ) ) {
	/**
	 * Check whether how to apply settings is "apply all applicable rules"
	 */
	function yaydp_cart_discount_is_applied_all_rules() {
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		return 'all' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_cart_discount_is_applied_first_rules' ) ) {
	/**
	 * Check whether how to apply settings is "apply first applicable rule"
	 */
	function yaydp_cart_discount_is_applied_first_rules() {
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		return 'first' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_cart_discount_is_applied_to_minimum_amount_per_order' ) ) {
	/**
	 * Check whether how to apply settings is "apply minimum"
	 */
	function yaydp_cart_discount_is_applied_to_minimum_amount_per_order() {
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		return 'smallest_amount' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_cart_discount_is_applied_to_maximum_amount_per_order' ) ) {
	/**
	 * Check whether how to apply settings is "apply maximum"
	 */
	function yaydp_cart_discount_is_applied_to_maximum_amount_per_order() {
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		return 'highest_amount' === $settings->get_how_to_apply();
	}
}
