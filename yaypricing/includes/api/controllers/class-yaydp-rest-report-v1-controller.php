<?php
/**
 * Handle Report controller in v1
 *
 * @package YayPricing\Rest
 */

namespace YAYDP\API\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_REST_REPORT_V1_CONTROLLER {

	/**
	 * Namespace of controller
	 *
	 * @var string
	 */
	private $namespace = 'yaydp/v1';

	/**
	 * Router base name
	 *
	 * @var string
	 */
	private $rest_base = 'report';

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Registers routes for this controller
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			"/$this->rest_base/orders",
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'get_orders_report' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/$this->rest_base/rule",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rule_by_id' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

	}

	/**
	 * Retrieves a report of all orders in the database
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_orders_report( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$body       = $request->get_json_params();
		$range_type = $body['range_type'];
		$from       = $body['from'];
		$to         = $body['to'];
		$order_by   = $body['order_by'];
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => \YAYDP\API\Models\YAYDP_Report_Model::get_orders( $range_type, $from, $to, $order_by ),
			)
		);
	}

	/**
	 * Retrieves a rule from the database by its ID.
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_rule_by_id( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$rule_id = ! is_null( $request->get_param( 'rule_id' ) ) ? $request->get_param( 'rule_id' ) : '';
		return new \WP_REST_Response(
			array(
				'success' => true,
				'rule'    => \YAYDP\API\Models\YAYDP_Report_Model::get_rule_by_id( $rule_id ),
			)
		);
	}

	/**
	 * Check if the current user has the necessary permissions to access the endpoint.
	 * It should return true if the user has permission, and false otherwise
	 */
	public function permission_callback() {
		return true;
	}

}
