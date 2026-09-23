<?php
/**
 * Manage settings page
 *
 * @package YayPricing\Admin
 */

namespace YAYDP\admin;

use YAYDP\YAYDP_I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Admin_Settings {

	/**
	 * Output the HTML content to settings page
	 */
	public static function render() {
		?>
		<script>
			document.querySelector("#wpbody-content").innerHTML = "";
		</script>
		<div id="dynamic-pricing"></div>
		<?php
	}

	/**
	 * Initializes class by setting up the necessary configurations and dependencies
	 */
	public static function init() {
		\YAYDP\Vite::enqueue_vite( 'admin-settings.jsx', '3050' );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueues script files to be loaded on a WordPress page
	 */
	public static function enqueue_scripts() {
		wp_enqueue_media();
		$default_data = array(
			'nonce'                     => wp_create_nonce( 'yaydp_nonce' ),
			'rest_api'                  => array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'url'   => esc_url_raw( rest_url( 'yaydp/v1' ) ),
			),
			'image_url'                 => YAYDP_PLUGIN_URL . 'assets/images',
			'search_limit'              => YAYDP_SEARCH_LIMIT,
			// Product filter vocabulary (labels, comparators, editors, defaults); the single source for the admin's filter rows.
			'product_filters'           => \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry::instance()->for_localize(),
			// Condition vocabulary (groups, labels, comparators, editors, defaults); the single source for the admin's condition rows.
			'conditions'                => \YAYDP\Condition\YAYDP_Condition_Registry::instance()->for_localize(),
			// Picker collections (products, categories, roles, …) are not inlined:
			// the admin fetches yaydp/v1/page-data/seeds once after first paint.
			'tax_classes'               => \YAYDP\API\Models\YAYDP_Data_Model::get_tax_classes(),
			'wc'                        => array(
				'currency'        => \get_woocommerce_currency(),
				'currency_symbol' => html_entity_decode( \get_woocommerce_currency_symbol(), ENT_COMPAT ),
			),
			'sample_pricing_table_data' => \YAYDP\Constants\YAYDP_Pricing_Table::get_sample_data(),
			'date_format'               => get_option( 'date_format' ),
			'time_format'               => get_option( 'time_format' ),
			'timezone_string'           => yaydp_get_timezone_offset(),
			'image_url'                 => YAYDP_PLUGIN_URL . 'assets/images/',
			'locale_direction'          => is_rtl() ? 'rtl' : 'ltr',
			'i18n'                      => YAYDP_I18n::get_translations(),
			// Pricing type metadata + per-rule-type availability; the single source for the admin's pricing fields.
			'pricing_types'             => \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::all_for_localize(),
			'version'                   => YAYDP_VERSION,
		);
		$extra_data    = apply_filters( 'yaydp_admin_extra_localize_data', array() );
		$localize_data = array_merge( $default_data, $extra_data );
		wp_localize_script(
			'module/yaydp/admin-settings.jsx',
			'yaydp_data',
			$localize_data
		);
	}
}
