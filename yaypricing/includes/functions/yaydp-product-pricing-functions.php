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
		// Pro-only stored rules resolve to null at the factory; drop them here so no
		// consumer ever sees a null rule. The option itself is never rewritten.
		return array_filter(
			array_map(
				function( $data ) {
					return \YAYDP\Factory\YAYDP_Product_Pricing_Rule_Factory::get_rule( $data );
				},
				empty( $database_data ) ? array() : $database_data
			)
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
		// Building the running set re-reads the option, re-instantiates every rule
		// object via the factory, and runs is_running() (which constructs several
		// DateTime objects) on each. On archive pages this is called per product and
		// per variation, so memoize it for the request. Rule objects are stateless
		// w.r.t. the product being checked (they take $product as a method arg), so
		// sharing instances within a request is safe. Flushed on yaydp_clear_cache.
		return \YAYDP\Core\Caches\YAYDP_Request_Cache::get_instance()->remember(
			'rules',
			'running_product_pricing',
			function () {
				$rules = \yaydp_get_product_pricing_rules();
				return array_filter(
					$rules,
					function ( $rule ) {
						return $rule->is_running();
					}
				);
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

if ( ! function_exists( 'yaydp_product_pricing_is_applied_to_maximum_amount_per_item' ) ) {
	/**
	 * Check whether how to apply settings is "apply maximum amount"
	 *
	 * @since 2.4
	 */
	function yaydp_product_pricing_is_applied_to_maximum_amount_per_item() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		return 'highest_amount_per_item' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_product_pricing_is_applied_to_minimum_amount_per_item' ) ) {
	/**
	 * Check whether how to apply settings is "apply minimum amount"
	 *
	 * @since 2.4
	 */
	function yaydp_product_pricing_is_applied_to_minimum_amount_per_item() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		return 'smallest_amount_per_item' === $settings->get_how_to_apply();
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

if ( ! function_exists( 'yaydp_product_pricing_is_applied_to_non_discount_product' ) ) {
	/**
	 * Check whether how to apply settings is "apply maximum amount"
	 *
	 * @since 3.4
	 */
	function yaydp_product_pricing_is_applied_to_non_discount_product() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		return 'apply_to_non_discount_product' === $settings->get_how_to_apply();
	}
}
