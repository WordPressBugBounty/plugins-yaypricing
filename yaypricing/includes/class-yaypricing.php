<?php
/**
 * Main class for the plugin function
 *
 * This class is responsible for initializing the plugin, registering the necessary hooks and filters, and providing the main functionality of the plugin
 *
 * @package YayPricing\Classes
 */

namespace YAYDP;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YayPricing {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Defines plugin constants
	 */
	private function define_constants() {
		\YAYDP\YAYDP_Constants::get_instance();
	}

	/**
	 * Include files
	 */
	private function includes() {

		// load i18n
		YAYDP_I18n::load_plugin_text_domain();

		/**
		 * Global functions.
		 */
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-core-functions.php';
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-variable-product-functions.php';
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-compare-functions.php';
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-rule-functions.php';
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-product-pricing-functions.php';
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-cart-discount-functions.php';
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-checkout-fee-functions.php';
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-exclude-functions.php';

		include_once YAYDP_ABSPATH . 'includes/yaydp-caching.php';

		/**
		 * Integrations
		 */
		include_once YAYDP_ABSPATH . 'includes/class-yaydp-integrations.php';

		// include_once YAYDP_ABSPATH . 'blocks/blocks.php';

		/**
		 * Include this file only when the user is on an admin page
		 */
		if ( yaydp_is_request( 'admin' ) ) {
			include_once YAYDP_ABSPATH . 'includes/admin/class-yaydp-admin.php';
			include_once YAYDP_ABSPATH . 'includes/admin/class-yaydp-order-manager.php';
		}

		/**
		 * Include this file only when the user is on frontend page
		 */
		if ( yaydp_is_request( 'frontend' ) ) {
			include_once YAYDP_ABSPATH . 'includes/class-yaydp-enqueue-frontend.php';
			include_once YAYDP_ABSPATH . 'includes/core/manager/class-yaydp-pricing-manager.php';
		}
	}

	/**
	 * Registers all the necessary hooks and filters for the plugin to function properly
	 */
	private function init_hooks() {
		$this->register_rest_api();
		$this->register_assistant_hooks();
	}

	/**
	 * Initializes the assistant hooks function
	 * This function sets up the necessary hooks for the assistant to function properly
	 */
	public function register_assistant_hooks() {
		\YAYDP\Helper\YAYDP_Matching_Products_Helper::init_hooks();
		add_filter( 'plugin_action_links_' . YAYDP_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
		add_filter( 'plugin_action_links_' . YAYDP_PLUGIN_BASENAME, array( $this, 'add_more_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_extra_links' ), 10, 2 );
	}

	/**
	 * Register Rest API
	 */
	public function register_rest_api() {
		\YAYDP\API\YAYDP_Rest::get_instance();
	}

	/**
	 * Adds action links to the plugin by hooking into the 'plugin_action_links' filter and appending the links to the existing links
	 *
	 * @param array $links Existing links.
	 *
	 * @return array Links after extending.
	 */
	public function add_action_links( $links ) {
		$yaydp_setting_links = array(
			// Translators: link href.
			sprintf( __( '%1$s Settings %2$s', 'yaypricing' ), '<a href="' . esc_url( admin_url() . 'admin.php?page=yaypricing' ) . '">', '</a>' ),
		);
		return array_merge( $yaydp_setting_links, $links );
	}

	public function add_more_links( $links ) {
		$links[] = '<a target="_blank" href="https://yaycommerce.com/yaypricing-woocommerce-dynamic-pricing-and-discounts/?utm_source=yaypricing-lite&utm_medium=gopro" style="color: #43B854; font-weight: bold">' . __( 'Go Pro', 'yaypricing' ) . '</a>';
		return $links;
	}

	/**
	 * Adds extra links to the plugin settings page
	 *
	 * @param array  $plugin_meta Plugin meta data.
	 * @param string $plugin_file Plugin basename.
	 *
	 * @return array
	 */
	public function add_plugin_extra_links( $plugin_meta, $plugin_file ) {
		if ( YAYDP_PLUGIN_BASENAME === $plugin_file ) {
			// Translators: link href.
			$plugin_meta[] = sprintf( __( '%1$s Docs %2$s', 'yaypricing' ), '<a href="https://docs.yaycommerce.com/yaypricing/features">', '</a>' );
			// Translators: link href.
			$plugin_meta[] = sprintf( __( '%1$s Support %2$s', 'yaypricing' ), '<a href="https://yaycommerce.com/support/">', '</a>' );
		}
		return $plugin_meta;
	}

}
