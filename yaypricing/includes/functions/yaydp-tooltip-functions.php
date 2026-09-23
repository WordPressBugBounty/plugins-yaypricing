<?php
/**
 * Rule tooltip helpers.
 *
 * @package YayPricing\Functions
 *
 * @since 3.5.8
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'yaydp_filter_enabled_tooltips' ) ) {

	/**
	 * Keeps the tooltips that exist and are switched on in their rule settings.
	 *
	 * @param array $tooltips Tooltip objects, possibly containing nulls.
	 *
	 * @return \YAYDP\Abstracts\YAYDP_Tooltip[]
	 */
	function yaydp_filter_enabled_tooltips( $tooltips ) {
		return array_values(
			array_filter(
				(array) $tooltips,
				function ( $tooltip ) {
					return $tooltip instanceof \YAYDP\Abstracts\YAYDP_Tooltip && $tooltip->is_enabled();
				}
			)
		);
	}
}

if ( ! function_exists( 'yaydp_render_tooltips' ) ) {

	/**
	 * Renders the tooltip icon + popover for the given tooltips, one block per tooltip.
	 * Disabled tooltips are skipped; returns an empty string when nothing is left.
	 *
	 * @param array $tooltips Tooltip objects.
	 *
	 * @return string HTML.
	 */
	function yaydp_render_tooltips( $tooltips ) {
		$tooltips = yaydp_filter_enabled_tooltips( $tooltips );
		if ( empty( $tooltips ) ) {
			return '';
		}
		return \wc_get_template_html(
			'tooltip.php',
			array( 'tooltips' => $tooltips ),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
	}
}
