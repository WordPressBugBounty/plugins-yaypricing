<?php
/**
 * Add menu or submenu in admin
 *
 * @package YayPricing\Admin
 */

namespace YAYDP\admin;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Admin_Menus {

	/**
	 * Array of submenus
	 *
	 * @var array
	 */
	protected $submenus = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'settings_menu' ), YAYDP_MENU_PRIORITY );

		$this->submenus = array(
			'yaypricing'        => array(
				'parent'             => 'yaycommerce',
				'name'               => __( 'YayPricing', 'yaypricing' ),
				'capability'         => 'manage_woocommerce',
				'render_callback'    => array( $this, 'settings_page' ),
				'load_data_callback' => array( $this, 'load_settings_page' ),
				'position'           => 0,
			),
			'yaypricing-report' => array(
				'parent'             => 'wc-admin&path=/analytics/overview',
				'name'               => __( 'YayPricing Report', 'yaypricing' ),
				'capability'         => 'view_woocommerce_reports',
				'render_callback'    => array( $this, 'report_page' ),
				'load_data_callback' => array( $this, 'load_report_page' ),
			),
		);
	}

	/**
	 * Add YayPricing menus
	 */
	public function settings_menu() {
		foreach ( $this->submenus as $id => $submenu ) {
			$page_id = add_submenu_page(
				$submenu['parent'],
				$submenu['name'],
				$submenu['name'],
				$submenu['capability'],
				$id,
				$submenu['render_callback'],
				isset( $submenu['position'] ) ? $submenu['position'] : null
			);
			add_action( 'load-' . $page_id, $submenu['load_data_callback'] );
		}
	}

	/**
	 * Render settings page html
	 */
	public function settings_page() {
		YAYDP_Admin_Settings::render();
	}

	/**
	 * Load settings page data
	 */
	public function load_settings_page() {
		YAYDP_Admin_Settings::init();
	}

	/**
	 * Render report page html
	 */
	public function report_page() {
		\YAYDP\Admin\YAYDP_Admin_Report::render();
	}

	/**
	 * Load report page data
	 */
	public function load_report_page() {
		\YAYDP\Admin\YAYDP_Admin_Report::init();
	}
}

new YAYDP_Admin_Menus();
