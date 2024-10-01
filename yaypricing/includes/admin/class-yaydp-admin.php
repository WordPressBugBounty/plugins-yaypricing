<?php
/**
 * Manage Admin stuffs
 *
 * @package YayPricing\Admin
 */

namespace YAYDP\admin;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initalize' ) );
	}

	/**
	 * Initializes class by setting up the necessary configurations and dependencies
	 */
	public function initalize() {
		include_once YAYDP_ABSPATH . 'includes/admin/class-yaydp-admin-menus.php';
	}
}

new YAYDP_Admin();
