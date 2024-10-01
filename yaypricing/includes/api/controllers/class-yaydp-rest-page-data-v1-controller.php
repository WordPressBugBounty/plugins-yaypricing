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
			"/{$this->rest_base}/products",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/variations",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_variations' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/categories",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_categories' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/tags",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tags' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/customer-roles",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_customer_roles' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/customers",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_customers' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/shipping-regions",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_shipping_regions' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/payment-methods",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_payment_methods' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/coupons",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_coupons' ),
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

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/attributes",
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_attributes' ),
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
	 * Retrieves products from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_products( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$products    = \YAYDP\API\Models\YAYDP_Data_Model::get_products( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $products,
			)
		);
	}

	/**
	 * Retrieves product variations from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_variations( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$variations  = \YAYDP\API\Models\YAYDP_Data_Model::get_variations( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $variations,
			)
		);
	}

	/**
	 * Retrieves product categories from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_categories( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$categories  = \YAYDP\API\Models\YAYDP_Data_Model::get_categories( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $categories,
			)
		);
	}

	/**
	 * Retrieves product tags from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_tags( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$tags        = \YAYDP\API\Models\YAYDP_Data_Model::get_tags( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $tags,
			)
		);
	}

	/**
	 * Retrieves customer roles from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_customer_roles( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$tags        = \YAYDP\API\Models\YAYDP_Data_Model::get_customer_roles( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $tags,
			)
		);
	}

	/**
	 * Retrieves customers from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_customers( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$tags        = \YAYDP\API\Models\YAYDP_Data_Model::get_customers( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $tags,
			)
		);
	}

	/**
	 * Retrieves shipping regions from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_shipping_regions( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$tags        = \YAYDP\API\Models\YAYDP_Data_Model::get_shipping_regions( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $tags,
			)
		);
	}

	/**
	 * Retrieves payment methods from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_payment_methods( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$tags        = \YAYDP\API\Models\YAYDP_Data_Model::get_payment_methods( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $tags,
			)
		);
	}

	/**
	 * Retrieves all available coupons from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_coupons( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$coupons     = \YAYDP\API\Models\YAYDP_Data_Model::get_coupons( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $coupons,
			)
		);
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
		$result      = apply_filters( "yaydp_admin_custom_filter_{$filter_name}_result", array(), $filter_name, $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $result,
			)
		);
	}

	/**
	 * Retrieves product categories from the database based on the specified parameters
	 *
	 * @param \WP_REST_Request $request Rest request.
	 */
	public function get_attributes( \WP_REST_Request $request ) {
		if ( ! \YAYDP\Helper\YAYDP_Helper::verify_rest_nonce( $request ) ) {
			return \YAYDP\Helper\YAYDP_Helper::get_verify_rest_nonce_failure_response();
		}
		$search_text = ! is_null( $request->get_param( 'search' ) ) ? $request->get_param( 'search' ) : '';
		$page        = ! is_null( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1;
		$limit       = ! is_null( $request->get_param( 'limit' ) ) ? $request->get_param( 'limit' ) : YAYDP_SEARCH_LIMIT;
		$categories  = \YAYDP\API\Models\YAYDP_Data_Model::get_attributes( $search_text, $page, $limit );
		return new \WP_REST_Response(
			array(
				'success'  => true,
				'data_arr' => $categories,
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
