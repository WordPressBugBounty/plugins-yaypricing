<?php
/**
 * Handle Rule controller in v1
 *
 * @package YayPricing\Rest
 */

namespace YAYDP\API\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_REST_RULE_V1_CONTROLLER {

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
	private $rest_base = 'rule';

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
			"/$this->rest_base/matching-products",
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'get_matching_products' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

	}

	/**
	 * Retrieves a list of products that match the specified criteria.
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_matching_products( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$req_body       = $request->get_json_params();
		$match_type     = $req_body['filter']['match_type'];
		$filters        = $req_body['filter']['filters'];
		$is_buy_x_get_y = $req_body['is_buy_x_get_y'];
		$products       = \YAYDP\Helper\YAYDP_Matching_Products_Helper::get_raw_matching_products_by_rule( $filters, $match_type, $is_buy_x_get_y );
		do_action( 'yaydp_remove_3rd_currency_format' );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'products' => \YAYDP\Helper\YAYDP_Matching_Products_Helper::get_formatted_response_products( $products ),
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
