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
	 * Output the HTML content to report page
	 */
	public static function render() {
		?>
		<script>
			document.querySelector("#wpbody-content").innerHTML = "";
		</script>
		<div id="dynamic-pricing-report"></div>
		<?php
	}

	/**
	 * Initializes class by setting up the necessary configurations and dependencies
	 */
	public static function init() {
		\YAYDP\Vite::enqueue_vite( 'admin-report.jsx', '3002' );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueues script files to be loaded on a WordPress page
	 */
	public static function enqueue_scripts() {
		wp_localize_script(
			'module/yaydp/admin-report.jsx',
			'yaydp_report_data',
			array(
				'nonce'                 => wp_create_nonce( 'yaydp_nonce' ),
				'i18n'                  => YAYDP_I18n::get_report_translations(),
				'rest_api'              => array(
					'nonce' => wp_create_nonce( 'wp_rest' ),
					'url'   => esc_url_raw( rest_url( 'yaydp/v1' ) ),
				),
				'currency'              => array(
					'symbol'             => html_entity_decode( \get_woocommerce_currency_symbol() ),
					'position'           => get_option( 'woocommerce_currency_pos', 'left' ),
					'decimals'           => \wc_get_price_decimals(),
					'decimal_separator'  => \wc_get_price_decimal_separator(),
					'thousand_separator' => \wc_get_price_thousand_separator(),
				),
				'product_pricing_rules' => \YAYDP\API\Models\YAYDP_Report_Model::get_all_product_pricing_rules(),
				'cart_discount_rules'   => \YAYDP\API\Models\YAYDP_Report_Model::get_all_cart_discount_rules(),
				'checkout_fee_rules'    => \YAYDP\API\Models\YAYDP_Report_Model::get_all_checkout_fee_rules(),
			)
		);
	}
}
