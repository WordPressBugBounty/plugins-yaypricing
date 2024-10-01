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
		\YAYDP\Vite::enqueue_vite( 'admin-settings.jsx', '3001' );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueues script files to be loaded on a WordPress page
	 */
	public static function enqueue_scripts() {
		wp_enqueue_media();
		$default_data  = array(
			'nonce'                     => wp_create_nonce( 'yaydp_nonce' ),
			'rest_api'                  => array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'url'   => esc_url_raw( rest_url( 'yaydp/v1' ) ),
			),
			'image_url'                 => YAYDP_PLUGIN_URL . 'assets/images',
			'search_limit'              => YAYDP_SEARCH_LIMIT,
			'product_filters'           => \YAYDP\Constants\YAYDP_Admin_Settings::get_product_filters(),
			'conditions'                => \YAYDP\Constants\YAYDP_Admin_Settings::get_extra_conditions(),
			'products'                  => \YAYDP\API\Models\YAYDP_Data_Model::get_products(),
			'product_variations'        => \YAYDP\API\Models\YAYDP_Data_Model::get_variations(),
			'product_categories'        => \YAYDP\API\Models\YAYDP_Data_Model::get_categories(),
			'product_attributes'        => \YAYDP\API\Models\YAYDP_Data_Model::get_attributes(),
			'product_tags'              => \YAYDP\API\Models\YAYDP_Data_Model::get_tags(),
			'customer_roles'            => \YAYDP\API\Models\YAYDP_Data_Model::get_customer_roles(),
			'customers'                 => \YAYDP\API\Models\YAYDP_Data_Model::get_customers(),
			'payment_methods'           => \YAYDP\API\Models\YAYDP_Data_Model::get_payment_methods(),
			'shipping_regions'          => \YAYDP\API\Models\YAYDP_Data_Model::get_shipping_regions(),
			'coupons'                   => \YAYDP\API\Models\YAYDP_Data_Model::get_coupons(),
			'wc'                        => array(
				'currency'        => \get_woocommerce_currency(),
				'currency_symbol' => html_entity_decode( \get_woocommerce_currency_symbol() ),
			),
			'sample_pricing_table_data' => \YAYDP\Constants\YAYDP_Pricing_Table::get_sample_data(),
			'date_format'               => get_option( 'date_format' ),
			'time_format'               => get_option( 'time_format' ),
			'timezone_string'           => yaydp_get_timezone_offset(),
			'image_url'                 => YAYDP_PLUGIN_URL . 'assets/images/',
			'locale_direction'          => is_rtl() ? 'rtl' : 'ltr',
			'i18n'                      => YAYDP_I18n::get_translations(),
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
