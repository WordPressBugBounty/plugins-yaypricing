<?php
/**
 * Handles the integration of Custom Post Type UI plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Woocommerce_Subscriptions_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return;
		}
		add_filter( 'yaydp_extra_conditions', array( $this, 'has_switch_subcription_condition' ) );
		add_filter( 'yaydp_check_has_switch_subscription_condition', array( $this, 'check_has_switch_subscription_condition' ), 10, 2 );
		add_filter( 'yaydp_admin_product_filters', array( $this, 'add_subscription_status_filter' ) );
		add_filter( "yaydp_check_condition_by_products_subscription_status", array( __CLASS__, 'check_condition' ), 10, 3 );
		add_filter( "yaydp_get_matching_products_by_products_subscription_status", array( __CLASS__, 'get_matching_products' ), 10, 4 );
	}

	/**
	 * Add filter to current product filters
	 *
	 * @param array $filters Given filters.
	 *
	 * @return array
	 */
	public function has_switch_subcription_condition( $conditions ) {
		$new_condition = array(
			'value'        => 'has_switch_subscription',
			'label'        => 'Switch subscription',
			'comparations' => array(
				array(
					'value' => 'in_list',
					'label' => 'In list',
				),
				array(
					'value' => 'not_in_list',
					'label' => 'Not in list',
				),
			),
			'values'       => array(
				array(
					'value' => 'downgrade',
					'label' => 'Downgrade',
				),
				array(
					'value' => 'upgrade',
					'label' => 'Upgrade',
				),
			),
		);
		$conditions[]  = $new_condition;
		return $conditions;
	}


	/**
	 * Alter check condition result
	 *
	 * @param array       $result Result.
	 * @param \WC_Product $product  Given product.
	 * @param array       $filter Checking filter.
	 *
	 * @return bool
	 */
	public static function check_has_switch_subscription_condition( $result, $condition ) {
		$switch_items       = \wcs_cart_contains_switches( 'switch' );
		$items_switch_types = array();
		if ( false == $switch_items ) {
			return false;
		}
		foreach ( $switch_items as $item ) {
			$type = $item['upgraded_or_downgraded'];
			if ( null == $type ) {
				continue;
			}
			if ( 'crossgraded' === $type ) {
				$items_switch_types[] = 'upgrade';
				$items_switch_types[] = 'downgrade';
			}
			if ( 'upgraded' === $type ) {
				$items_switch_types[] = 'upgrade';
			}
			if ( 'downgraded' === $type ) {
				$items_switch_types[] = 'downgrade';
			}
		}

		$items_switch_types = array_unique( $items_switch_types );
		$condition_values   = array_map(
			function( $item ) {
				return $item['value'];
			},
			$condition['value']
		);
		$intersection       = array_intersect( $items_switch_types, $condition_values );
		return 'in_list' === $condition['comparation'] ? ! empty( $intersection ) : empty( $intersection );
	}

	public function add_subscription_status_filter( $filters ) {
		$filters[] = array(
			'value'        => 'products_subscription_status',
			'label'        => __( 'Subscription status', 'yaypricing' ),
			'comparations' => array(
				array(
					'value' => 'has_subscription',
					'label' => __( 'Has subscription', 'yaypricing' ),
				),
				array(
					'value' => 'no_subscription',
					'label' => __( 'No subscription', 'yaypricing' ),
				),
			),
		);
		return $filters;
	}

	public static function check_condition( $result, $product, $filter ) {
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return false;
		}

		if ( \yaydp_is_variable_product( $product ) || \yaydp_is_grouped_product( $product ) ) {
			$product_ids = array_merge( [ $product->get_id() ], $product->get_children() );
			foreach ( $product_ids as $product_id ) {
				$has_subscription = \wcs_user_has_subscription( $user_id, $product_id, 'active' ) || \wcs_user_has_subscription( $user_id, $product_id, 'on-hold' ) || \wcs_user_has_subscription( $user_id, $product_id, 'pending' );
				if ( ( $has_subscription && 'has_subscription' === $filter['comparation'] ) || ( ! $has_subscription && 'no_subscription' === $filter['comparation'] ) ) {
					return true;
				}
			}
			return false;
		} else {
			$has_subscription = \wcs_user_has_subscription( $user_id, $product->get_id(), 'active' ) || \wcs_user_has_subscription( $user_id, $product->get_id(), 'on-hold' ) || \wcs_user_has_subscription( $user_id, $product->get_id(), 'pending' );
			return 'has_subscription' === $filter['comparation'] ? $has_subscription : ! $has_subscription;
		}
	}

	public static function get_matching_products( $products, $type, $value, $comparation ) {
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return [];
		}

		$user_subscriptions = \wcs_get_subscriptions( array(
			'customer_id' => $user_id,
			'subscription_status'      => ['active', 'on-hold', 'pending'],
		) );

		if ( empty( $user_subscriptions ) ) {
			return [];
		}

		foreach ( $user_subscriptions as $subscription ) {
			$subscription_products = $subscription->get_items();
			foreach ( $subscription_products as $subscription_product ) {
				$products[] = $subscription_product->get_product();
			}
		}

		if ( 'has_subscription' === $comparation ) {
			return $products;
		}
		$product_ids = array_map( function( $product ) {
			return $product->get_id();
		}, $products );
		
		$products = \YAYDP\Helper\YAYDP_Matching_Products_Helper::get_product_by_ids( $product_ids, 'not_in_list' );
		
		return $products;

	}
}
