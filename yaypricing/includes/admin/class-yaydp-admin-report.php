<?php
/**
 * Manage report page
 *
 * @package YayPricing\Admin
 */

namespace YAYDP\admin;

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
				'rest_api'              => array(
					'nonce' => wp_create_nonce( 'wp_rest' ),
					'url'   => esc_url_raw( rest_url( 'yaydp/v1' ) ),
				),
				'product_pricing_rules' => \YAYDP\API\Models\YAYDP_Report_Model::get_all_product_pricing_rules(),
				'cart_discount_rules'   => \YAYDP\API\Models\YAYDP_Report_Model::get_all_cart_discount_rules(),
				'checkout_fee_rules'    => \YAYDP\API\Models\YAYDP_Report_Model::get_all_checkout_fee_rules(),
			)
		);
	}
}
