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
		// Pro-only stored rules resolve to null at the factory; drop them here so no
		// consumer ever sees a null rule. The option itself is never rewritten.
		return array_filter(
			array_map(
				function( $data ) {
					return \YAYDP\Factory\YAYDP_Exclude_Rule_Factory::get_rule( $data );
				},
				empty( $database_data ) ? array() : $database_data
			)
		);
	}
}

if ( ! function_exists( 'yaydp_get_running_exclude_rules' ) ) {
	/**
	 * Get all running exclude rules
	 */
	function yaydp_get_running_exclude_rules() {
		// Memoized for the request: the exclude ruleset is otherwise rebuilt twice
		// per applicability check (check_product_exclusions + check_coupon_exclusions),
		// i.e. products x variations x rules x 2 full rebuilds on an archive page.
		// Flushed on yaydp_clear_cache.
		return \YAYDP\Core\Caches\YAYDP_Request_Cache::get_instance()->remember(
			'rules',
			'running_exclude',
			function () {
				$rules = \yaydp_get_exclude_rules();
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
