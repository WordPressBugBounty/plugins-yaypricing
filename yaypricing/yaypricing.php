<?php
/**
 * Plugin Name: YayPricing Lite - WooCommerce Dynamic Pricing And Discounts
 * Plugin URI: https://wordpress.org/plugins/yaypricing/
 * Description: Create automatic product pricing rules and cart discounts to design a powerful marketing strategy for your WooCommerce store.
 * Version: 3.4
 * Author: YayCommerce
 * Author URI: https://yaycommerce.com/
 * Text Domain: yaypricing
 * WC requires at least: 3.0.0
 * WC tested up to: 9.3.3
 * Requires PHP: 5.7
 * Domain Path: /languages
 *
 * @package YayPricing
 */

namespace YAYDP;

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'YAYDP\\load_plugin' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/yaydp-duplicate-fallback.php';
	add_action(
		'admin_init',
		function() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	);
	return;
}


if ( ! defined( 'YAYDP_PLUGIN_FILE' ) ) {
	define( 'YAYDP_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'YAYDP_ABSPATH' ) ) {
	define( 'YAYDP_ABSPATH', dirname( __FILE__ ) . '/' );
}
if ( ! defined( 'YAYDP_PLUGIN_PATH' ) ) {
	define( 'YAYDP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'YAYDP_PLUGIN_URL' ) ) {
	define( 'YAYDP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'YAYDP_PLUGIN_BASENAME' ) ) {
	define( 'YAYDP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'YAYDP_VERSION' ) ) {
	define( 'YAYDP_VERSION', '3.4' );
}
if ( ! defined( 'YAYDP_MINIMUM_PHP_VERSION' ) ) {
	define( 'YAYDP_MINIMUM_PHP_VERSION', 5.7 );
}
if ( ! defined( 'YAYDP_DEVELOPMENT' ) ) {
	define( 'YAYDP_DEVELOPMENT', false );
}

spl_autoload_register(
	function ( $class_name ) {

		if ( strncmp( __NAMESPACE__, $class_name, strlen( __NAMESPACE__ ) ) !== 0 ) {
			return;
		}

		$class_name = strtolower( str_replace( '_', '-', $class_name ) );

		$namespace_arr = \explode( '\\', $class_name );

		$class_name_without_namespace = $namespace_arr[ count( $namespace_arr ) - 1 ];
		$class_name_without_namespace = str_replace( '\\', '-', $class_name_without_namespace );

		$file_name_prefix = 'class';
		if ( false !== strpos( $class_name, 'traits' ) ) {
			$file_name_prefix = 'trait';
		}
		if ( false !== strpos( $class_name, 'abstracts' ) ) {
			$file_name_prefix = 'abstract';
		}
		$file_name = $file_name_prefix . '-' . $class_name_without_namespace . '.php';

		$namespace_arr[ count( $namespace_arr ) - 1 ] = $file_name;
		$namespace_arr[0]                             = 'includes';

		$file = __DIR__ . '/' . \implode( '/', $namespace_arr );

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

add_action( 'plugins_loaded', '\\YAYDP\\load_plugin' );

if ( ! function_exists( 'YAYDP\\load_plugin' ) ) {
	/**
	 * Initialize plugin instance
	 */
	function load_plugin() { //phpcs:ignore
		\YAYDP\before_load_plugin();
		if ( function_exists( 'WC' ) && \version_compare( phpversion(), YAYDP_MINIMUM_PHP_VERSION, '>=' ) ) {
			\YAYDP\YayPricing::get_instance();
		} else {
			\YAYDP\YAYDP_Fallback::get_instance();
		}
	}
}

if ( ! function_exists( 'YAYDP\\before_load_plugin' ) ) {
	/**
	 * Do stuff before load plugin
	 */
	function before_load_plugin() {
		\YAYDP\YayCommerceMenu\Register_Menu::get_instance(); // Initialize YayCommerce menu.
		if ( function_exists( 'WC' ) ) {
			/**
			 * To set plugin is compatible for WC Custom Order Table (HPOS) feature.
			 */
			add_action(
				'before_woocommerce_init',
				function() {
					if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
						\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
					}
				}
			);
		}
	}
}

register_activation_hook( __FILE__, array( 'YAYDP\\YAYDP_Activation', 'initialize' ) );
register_deactivation_hook( __FILE__, array( 'YAYDP\\YAYDP_Deactivation', 'initialize' ) );


