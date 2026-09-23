<?php
/**
 * Handle Page Data controller in v1
 *
 * @package YayPricing\Rest
 */

namespace YAYDP\API\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_REST_PAGE_DATA_V1_CONTROLLER {

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
	private $rest_base = 'page-data';

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
			"/{$this->rest_base}/",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_page_data' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_page_data' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/custom-filter",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_custom_filter' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

		// One route per picker collection that declares one (YAYDP_Collections).
		foreach ( \YAYDP\API\Models\YAYDP_Collections::all() as $source => $entry ) {
			if ( empty( $entry['route'] ) ) {
				continue;
			}
			register_rest_route(
				$this->namespace,
				"/{$this->rest_base}/{$entry['route']}",
				array(
					array(
						'methods'             => \WP_REST_Server::READABLE,
						'callback'            => function ( \WP_REST_Request $request ) use ( $source ) {
							return $this->get_collection( $request, $source );
						},
						'permission_callback' => array( $this, 'permission_callback' ),
					),
				)
			);
		}

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/seeds",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_seeds' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
	}

	/**
	 * Retrieves data for a Settings page from the database
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_page_data( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$rules    = \YAYDP\API\Models\YAYDP_Rule_Model::get_all();
		$settings = \YAYDP\API\Models\YAYDP_Setting_Model::get_all();
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'rules'    => $rules,
					'settings' => $settings,
				),
			)
		);
	}

	/**
	 * Retrieves the removing rules from the database.
	 *
	 * @param array $current_rules Rules from the database.
	 * @param array $saving_rules Rules are saving from the request.
	 * @param array $removed_rules Removed rules that saved in database.
	 */
	public function get_removing_rules( $current_rules, $saving_rules, $removed_rules ) {
		$saving_rules  = ! empty( $saving_rules ) ? $saving_rules : array();
		$removed_rules = ! empty( $removed_rules ) ? $removed_rules : array();
		$current_rules = ! empty( $current_rules ) ? $current_rules : array();

		$result = array_filter(
			! empty( $current_rules ) ? $current_rules : array(),
			function( $rule ) use ( $saving_rules ) {
				$in_list = false;
				foreach ( $saving_rules as $r ) {
					if ( $rule['id'] === $r['id'] ) {
						$in_list = true;
						break;
					}
				}
				return ! $in_list && ! empty( $rule['use_time'] );
			}
		);

		$result = array_merge( $removed_rules, $result );
		return $result;
	}

	/**
	 * Carry the stored use_time of each rule onto the incoming rule of the same id.
	 *
	 * The settings page sends back the use_time it loaded, which is stale as soon
	 * as any order completes while the page is open. The server is the source of
	 * truth for that counter, so an admin save must never overwrite it.
	 *
	 * @param array $current_rules Rules from the database.
	 * @param array $saving_rules  Rules are saving from the request.
	 * @return array Saving rules with use_time restored from the database.
	 */
	public function preserve_use_time( $current_rules, $saving_rules ) {
		$saving_rules  = ! empty( $saving_rules ) ? $saving_rules : array();
		$current_rules = ! empty( $current_rules ) ? $current_rules : array();

		$stored_use_times = array();
		foreach ( $current_rules as $rule ) {
			if ( ! empty( $rule['id'] ) ) {
				$stored_use_times[ $rule['id'] ] = (int) ( $rule['use_time'] ?? 0 );
			}
		}

		return array_map(
			function( $rule ) use ( $stored_use_times ) {
				if ( ! empty( $rule['id'] ) && isset( $stored_use_times[ $rule['id'] ] ) ) {
					$rule['use_time'] = $stored_use_times[ $rule['id'] ];
				}
				return $rule;
			},
			$saving_rules
		);
	}

	/**
	 * Saving the removing rules in the database.
	 *
	 * @param array $body Data.
	 */
	public function save_removing_rules( $body ) {

		$removing_product_pricing_rules = $this->get_removing_rules( get_option( 'yaydp_product_pricing_rules' ), $body['rules']['product_pricing'], get_option( 'yaydp_removed_product_pricing_rules' ) );
		$removing_cart_discount_rules   = $this->get_removing_rules( get_option( 'yaydp_cart_discount_rules' ), $body['rules']['cart_discount'], get_option( 'yaydp_removed_cart_discount_rules' ) );
		$removing_checkout_fee_rules    = $this->get_removing_rules( get_option( 'yaydp_checkout_fee_rules' ), $body['rules']['checkout_fee'], get_option( 'yaydp_removed_checkout_fee_rules' ) );

		update_option( 'yaydp_removed_product_pricing_rules', $removing_product_pricing_rules );
		update_option( 'yaydp_removed_cart_discount_rules', $removing_cart_discount_rules );
		update_option( 'yaydp_removed_checkout_fee_rules', $removing_checkout_fee_rules );
	}

	/**
	 * Saving page data to the database.
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function save_page_data( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$params = $request->get_json_params();
		try {
			$body = $params['body'];
			$this->save_removing_rules( $body );

			$body['rules']['product_pricing'] = $this->preserve_use_time( get_option( 'yaydp_product_pricing_rules' ), $body['rules']['product_pricing'] );
			$body['rules']['cart_discount']   = $this->preserve_use_time( get_option( 'yaydp_cart_discount_rules' ), $body['rules']['cart_discount'] );
			$body['rules']['checkout_fee']    = $this->preserve_use_time( get_option( 'yaydp_checkout_fee_rules' ), $body['rules']['checkout_fee'] );

			update_option( 'yaydp_product_pricing_rules', $body['rules']['product_pricing'] );
			update_option( 'yaydp_cart_discount_rules', $body['rules']['cart_discount'] );
			update_option( 'yaydp_checkout_fee_rules', $body['rules']['checkout_fee'] );
			update_option( 'yaydp_exclude_rules', $body['rules']['exclude'] );
			update_option( 'yaydp_core_settings', $body['settings'] );

			do_action( 'yaydp_after_saving_data', $body );
			return new \WP_REST_Response(
				array(
					'success' => true,
				)
			);
		} catch ( \Exception $error ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => $error,
				)
			);
		} catch ( \Error $error ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => $error,
				)
			);
		}
	}

	/**
	 * Retrieves custom filter data from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_custom_filter( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$filter_name = ! is_null( $request->get_param( 'filter_name' ) ) ? $request->get_param( 'filter_name' ) : '';
		$type        = \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry::instance()->get( $filter_name );
		$result      = $type ? $type->search_options( $search_text, $page, $limit ) : null;
		if ( is_null( $result ) ) {
			$result = \YAYDP\Product_Filter\YAYDP_Legacy_Product_Filter_Hooks::search_options( $filter_name, $search_text, $page, $limit );
		}
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $result,
			)
		);
	}

	/**
	 * One page of a picker collection: `search`, `page`, `limit` query params,
	 * `{ success, data_arr }` envelope, at most `limit + 1` rows.
	 *
	 * @param \WP_REST_Request $request Rest request.
	 * @param string           $source  Collection key in YAYDP_Collections::all().
	 */
	public function get_collection( \WP_REST_Request $request, $source ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$collections = \YAYDP\API\Models\YAYDP_Collections::all();
		if ( ! isset( $collections[ $source ] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Unknown collection',
				),
				404
			);
		}
		$search = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page   = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit  = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => call_user_func( $collections[ $source ]['getter'], $search, $page, $limit ),
			)
		);
	}

	/**
	 * First page of every seed collection in one reply: `{ source: rows }`.
	 * The admin fetches this once after first paint instead of receiving the
	 * lists inline.
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_seeds( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => \YAYDP\API\Models\YAYDP_Collections::seeds(),
			)
		);
	}

	/**
	 * Check if the current user has the necessary permissions to access the endpoint.
	 * It should return true if the user has permission, and false otherwise
	 */
	public function permission_callback() {
		return current_user_can( 'manage_woocommerce' );
	}
}
