<?php
/**
 * Manage report page
 *
 * @package YayPricing\Admin
 */

namespace YAYDP\admin;

use YAYDP\YAYDP_I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Admin_Report {

	/**
	 * Script and style handle of the report app.
	 */
	const HANDLE = 'yaydp-admin-report';

	/**
	 * Build output of apps/admin-report-page, relative to the plugin root.
	 */
	const BUILD_DIR = 'assets/dist/admin-report/';

	/**
	 * Output the HTML content to report page
	 */
	public static function render() {
		?>
		<script>
			document.querySelector("#wpbody-content").innerHTML = "";
		</script>
		<div id="dynamic-pricing-report">
			<?php if ( ! self::can_load() ) : ?>
				<div class="notice notice-error inline">
					<p><?php esc_html_e( 'The report needs an up-to-date WooCommerce, and the report build to be present. Update WooCommerce, or reinstall YayPricing.', 'yaypricing' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Initializes class by setting up the necessary configurations and dependencies
	 */
	public static function init() {
		// After WooCommerce registers its admin packages, at the default priority.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 20 );
	}

	/**
	 * The report is built on WooCommerce's admin components rather than
	 * bundling its own, so it can only run where WooCommerce registers them.
	 * Each dependency the build lists has to be registered, which also covers
	 * WordPress versions that predate react-jsx-runtime.
	 */
	private static function can_load() {
		$asset = self::get_asset();
		if ( is_null( $asset ) ) {
			return false;
		}
		foreach ( $asset['dependencies'] as $dependency ) {
			if ( ! wp_script_is( $dependency, 'registered' ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Dependencies and version written by wp-scripts next to the build.
	 */
	private static function get_asset() {
		$file = YAYDP_PLUGIN_PATH . self::BUILD_DIR . 'index.asset.php';
		if ( ! file_exists( $file ) ) {
			return null;
		}
		$asset = include $file;
		return is_array( $asset ) && isset( $asset['dependencies'], $asset['version'] ) ? $asset : null;
	}

	/**
	 * Enqueues script files to be loaded on a WordPress page
	 */
	public static function enqueue_scripts() {
		if ( ! self::can_load() ) {
			return;
		}
		$asset = self::get_asset();

		// WooCommerce moves scripts that depend on its packages to the footer
		// anyway, and warns when it has to; loading there avoids the warning.
		wp_enqueue_script( self::HANDLE, YAYDP_PLUGIN_URL . self::BUILD_DIR . 'index.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( self::HANDLE, YAYDP_PLUGIN_URL . self::BUILD_DIR . 'style-index.css', array( 'wp-components', 'wc-components' ), $asset['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );

		wp_localize_script(
			self::HANDLE,
			'yaydp_report_data',
			array(
				'i18n'     => YAYDP_I18n::get_report_translations(),
				'rest_api' => array(
					'nonce' => wp_create_nonce( 'wp_rest' ),
					'url'   => esc_url_raw( rest_url( 'yaydp/v1' ) ),
				),
				'currency' => array(
					'symbol'             => html_entity_decode( \get_woocommerce_currency_symbol() ),
					'position'           => get_option( 'woocommerce_currency_pos', 'left' ),
					'decimals'           => \wc_get_price_decimals(),
					'decimal_separator'  => \wc_get_price_decimal_separator(),
					'thousand_separator' => \wc_get_price_thousand_separator(),
				),
			)
		);
	}
}
