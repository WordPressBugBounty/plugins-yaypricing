<?php
/**
 * Handle the report backfill controller in v1
 *
 * @package YayPricing\Rest
 */

namespace YAYDP\API\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_REST_REPORT_BACKFILL_V1_CONTROLLER {

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
	private $rest_base = 'report/backfill-status';

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
			"/$this->rest_base",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'restart' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
	}

	/**
	 * Reports how far the historical backfill has progressed.
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_status( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => self::get_state(),
			)
		);
	}

	/**
	 * Runs the historical backfill again from the beginning.
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function restart( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		\YAYDP\Report\YAYDP_Report_Backfill::reset();
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => self::get_state(),
			)
		);
	}

	/**
	 * Progress plus the date from which orders record their own amounts.
	 */
	private static function get_state() {
		$state                  = \YAYDP\Report\YAYDP_Report_Backfill::get_progress();
		$state['boundary_date'] = \YAYDP\Report\YAYDP_Report_Backfill::get_boundary_date();
		return $state;
	}

	/**
	 * Check if the current user has the necessary permissions to access the endpoint.
	 */
	public function permission_callback() {
		return current_user_can( 'manage_woocommerce' );
	}
}
