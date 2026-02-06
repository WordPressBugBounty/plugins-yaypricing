<?php
/**
 * Handles the integration of Flatsome theme with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Themes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Flatsome_Theme_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'after_setup_theme', array( $this, 'after_theme_setup' ) );
	}

	/**
	 * After theme setup function
	 */
	public function after_theme_setup() {
		if ( ! class_exists( 'Flatsome' ) ) {
			return;
		}
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'yaydp_enqueue_pricing_table_assets', array( $this, 'enqueue_pricing_table_assets' ) );
	}

	public function enqueue_scripts() {
		if ( \is_shop() ) {
			wp_enqueue_script(
				'yaydp-integration-variation-selection',
				YAYDP_PLUGIN_URL . 'includes/integrations/themes/js/flatsome-theme-integration.js',
				array( 'jquery' ),
				YAYDP_VERSION,
				true
			);
			wp_enqueue_script(
				'yaydp-integration-pricing-table',
				YAYDP_PLUGIN_URL . "assets/js/pricing-table.js",
				array( 'jquery' ),
				YAYDP_VERSION,
				true
			);
			wp_enqueue_style(
				"yaydp-integration-pricing-table",
				YAYDP_PLUGIN_URL . "assets/css/pricing-table.css",
				array(),
				YAYDP_VERSION
			);
		}
	}

	public function enqueue_pricing_table_assets() {
		return \is_shop();
	}

}
