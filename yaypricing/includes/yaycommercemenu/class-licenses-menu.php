<?php
/**
 * YayCommerce licenses menu
 *
 * @package YayPricing\Admin
 */

namespace YAYDP\YayCommerceMenu;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class Licenses_Menu {

	/**
	 * Render page in HTML
	 */
	public static function render() {?>
	<script>
		document.querySelector("#wpbody-content").innerHTML = "";
	</script>
		<?php
		include plugin_dir_path( __FILE__ ) . 'views/licenses.php';
	}

	/**
	 * Load screen
	 */
	public static function load_data() {
		self::enqueue_scripts();
	}

	/**
	 * Enqueue script for that page.
	 */
	public static function enqueue_scripts() {
		wp_enqueue_style( 'yaycommerce-licenses', plugin_dir_url( __FILE__ ) . 'assets/css/licenses.css', array(), '1.0' );
	}

}
