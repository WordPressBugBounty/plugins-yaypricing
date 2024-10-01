<?php
/**
 * Handles RESTful API requests and responses
 *
 * @package YayPricing\Rest
 */

namespace YAYDP\API;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Rest {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'on_rest_api_init' ) );
	}

	/**
	 * Initializes the REST API endpoints for the plugin
	 */
	public function on_rest_api_init() {
		foreach ( $this->get_rest_namespaces() as $controllers ) {
			foreach ( $controllers as $controller ) {
				$class_name         = '\\YAYDP\\API\\Controllers\\' . $controller;
				$current_controller = new $class_name();
				$current_controller->register_routes();
			}
		}
	}

	/**
	 * Retrieves the registered REST API namespaces
	 */
	public function get_rest_namespaces() {
		return array(
			'yaydp/v1' => $this->get_v1_controllers(),
		);
	}

	/**
	 * Retrieves all v1 controllers from the database
	 */
	public function get_v1_controllers() {
		return array(
			'page-data' => 'YAYDP_REST_PAGE_DATA_V1_CONTROLLER',
			'rule'      => 'YAYDP_REST_RULE_V1_CONTROLLER',
			'report'    => 'YAYDP_REST_REPORT_V1_CONTROLLER',
		);
	}

}
