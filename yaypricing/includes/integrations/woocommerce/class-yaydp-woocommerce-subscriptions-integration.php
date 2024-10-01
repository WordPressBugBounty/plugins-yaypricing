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
		$switch_items = \wcs_cart_contains_switches( 'switch' );
		if ( false == $switch_items ) {
			return false;
		}
		$items_switch_types = array();
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
}
