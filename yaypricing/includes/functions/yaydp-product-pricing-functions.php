<?php
/**
 * YayPricing functions for product pricing
 *
 * Declare global functions
 *
 * @package YayPricing\Functions
 */

if ( ! function_exists( 'yaydp_get_product_pricing_rules' ) ) {
	/**
	 * Get all product pricing rules
	 *
	 * @since 2.4
	 */
	function yaydp_get_product_pricing_rules() {
		$database_data = get_option( 'yaydp_product_pricing_rules' );
		return array_map(
			function( $data ) {
				return \YAYDP\Factory\YAYDP_Product_Pricing_Rule_Factory::get_rule( $data );
			},
			empty( $database_data ) ? array() : $database_data
		);
	}
}

if ( ! function_exists( 'yaydp_get_running_product_pricing_rules' ) ) {
	/**
	 * Get all product pricing running rules
	 *
	 * @since 2.4
	 */
	function yaydp_get_running_product_pricing_rules() {
		$rules = \yaydp_get_product_pricing_rules();
		return array_filter(
			$rules,
			function ( $rule ) {
				return $rule->is_running();
			}
		);
	}
}

if ( ! function_exists( 'yaydp_product_pricing_is_applied_all_rules' ) ) {
	/**
	 * Check whether how to apply settings is "apply all applicable rules"
	 *
	 * @since 2.4
	 */
	function yaydp_product_pricing_is_applied_all_rules() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		return 'all' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_product_pricing_is_applied_first_rules' ) ) {
	/**
	 * Check whether how to apply settings is "apply first applicable rules"
	 *
	 * @since 2.4
	 */
	function yaydp_product_pricing_is_applied_first_rules() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		return 'first' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_product_pricing_is_applied_to_minimum_amount_per_order' ) ) {
	/**
	 * Check whether how to apply settings is "apply minimum amount"
	 *
	 * @since 2.4
	 */
	function yaydp_product_pricing_is_applied_to_minimum_amount_per_order() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		return 'smallest_amount' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_product_pricing_is_applied_to_maximum_amount_per_order' ) ) {
	/**
	 * Check whether how to apply settings is "apply maximum amount"
	 *
	 * @since 2.4
	 */
	function yaydp_product_pricing_is_applied_to_maximum_amount_per_order() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		return 'highest_amount' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_product_pricing_is_discount_based_on_regular_price' ) ) {
	/**
	 * Check whether discount based on settings is "regular price"
	 *
	 * @since 2.4
	 */
	function yaydp_product_pricing_is_discount_based_on_regular_price() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		return 'regular_price' === $settings->get_discount_base_on();
	}
}

