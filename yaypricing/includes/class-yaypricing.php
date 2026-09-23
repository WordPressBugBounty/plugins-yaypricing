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
		include_once YAYDP_ABSPATH . 'includes/functions/yaydp-tooltip-functions.php';
		include_once YAYDP_ABSPATH . 'includes/admin/class-yaydp-admin-menus.php';

		include_once YAYDP_ABSPATH . 'includes/yaydp-caching.php';

		$this->load_legacy_compat();

		/**
		 * Integrations
		 */
		include_once YAYDP_ABSPATH . 'includes/class-yaydp-integrations.php';

		/**
		 * Register order-status-completed listeners in every request context
		 * (admin manual completion, REST, cron) so usage counts update reliably.
		 * Singletons are idempotent — frontend re-instantiation via pricing manager is a no-op.
		 */
		\YAYDP\Core\Use_Time\YAYDP_Product_Pricing_Use_Time::get_instance();
		\YAYDP\Core\Use_Time\YAYDP_Cart_Discount_Use_Time::get_instance();
		\YAYDP\Core\Use_Time\YAYDP_Checkout_Fee_Use_Time::get_instance();

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
			include_once YAYDP_ABSPATH . 'includes/frontend/class-yaydp-gift-product-display.php';
			include_once YAYDP_ABSPATH . 'includes/class-yaydp-checkout-billing-email-sync.php';
		}
	}

	/**
	 * Pre-3.5.8 condition / product-filter hooks and helper class names.
	 * Loaded eagerly because each file aliases class names third-party code
	 * may call before the registries boot. Removed in 3.6.0.
	 */
	private function load_legacy_compat() {
		include_once YAYDP_ABSPATH . 'includes/condition/class-yaydp-legacy-condition-hooks.php';
		include_once YAYDP_ABSPATH . 'includes/product-filter/class-yaydp-legacy-product-filter-hooks.php';
	}

	/**
	 * Registers all the necessary hooks and filters for the plugin to function properly
	 */
	private function init_hooks() {
		\YAYDP\Schedule\YAYDP_Schedule_Migration::maybe_migrate();
		$this->register_rest_api();
		$this->register_assistant_hooks();
		$this->clean_cache_hooks();
		$this->register_report_hooks();
	}

	/**
	 * Keep the report statistics table in step with order changes.
	 */
	public function register_report_hooks() {
		\YAYDP\Report\YAYDP_Report_Stats_Hooks::init_hooks();
	}

	/**
	 * Initializes the assistant hooks function
	 * This function sets up the necessary hooks for the assistant to function properly
	 */
	public function register_assistant_hooks() {
		\YAYDP\Helper\YAYDP_Matching_Products_Helper::init_hooks();
	}

	/**
	 * Register Rest API
	 */
	public function register_rest_api() {
		\YAYDP\API\YAYDP_Rest::get_instance();
	}

	private function clean_cache_hooks() {
		add_action( 'woocommerce_new_product', 'yaydp_clear_shortcode_cache', 10, 1 );
		add_action( 'woocommerce_update_product', 'yaydp_clear_shortcode_cache', 10, 1 );

		$options = array( 'yaydp_product_pricing_rules', 'yaydp_cart_discount_rules', 'yaydp_checkout_fee_rules', 'yaydp_exclude_rules', 'yaydp_core_settings' );
		foreach ( $options as $option ) {
			add_action( 'update_option_' . $option, 'yaydp_clear_shortcode_cache' );
		}
	}
}
