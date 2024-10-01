<?php
/**
 * YayPricing functions for exclude
 *
 * Declare global functions
 *
 * @package YayPricing\Functions
 * @since 2.4
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'yaydp_get_exclude_rules' ) ) {
	/**
	 * Get all exclude rules
	 */
	function yaydp_get_exclude_rules() {
		$database_data = get_option( 'yaydp_exclude_rules' );
		return array_map(
			function( $data ) {
				return \YAYDP\Factory\YAYDP_Exclude_Rule_Factory::get_rule( $data );
			},
			empty( $database_data ) ? array() : $database_data
		);
	}
}

if ( ! function_exists( 'yaydp_get_running_exclude_rules' ) ) {
	/**
	 * Get all running exclude rules
	 */
	function yaydp_get_running_exclude_rules() {
		$rules = \yaydp_get_exclude_rules();
		return array_filter(
			$rules,
			function ( $rule ) {
				return $rule->is_running();
			}
		);
	}
}
