<?php
/**
 * Class serves as a fallback for when a required class is not found or cannot be loaded.
 * It provides basic functionality to prevent fatal errors and allow the application to continue running
 *
 * @package YayPricing\Fallback
 */

namespace YAYDP;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Fallback {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'admin_notices', array( $this, 'add_require_woocommerce_notice' ) );
		add_action( 'admin_notices', array( $this, 'add_require_php_version_notice' ) );
		if ( ! \version_compare( phpversion(), YAYDP_MINIMUM_PHP_VERSION, '>=' ) ) {
			deactivate_plugins( YAYDP_PLUGIN_BASENAME );
		}
	}

	/**
	 * Adds a notice to the admin dashboard if WooCommerce is not installed or activated
	 */
	public function add_require_woocommerce_notice() {
		include YAYDP_ABSPATH . 'includes/admin/views/html-notice-require-wc.php';
	}

	/**
	 * Adds a notice to the WordPress dashboard if the current PHP version is lower than the required version.
	 * It is used to ensure that the plugin or theme is running on a compatible PHP version
	 */
	public function add_require_php_version_notice() {
		include YAYDP_ABSPATH . 'includes/admin/views/html-require-php-version.php';
	}
}
