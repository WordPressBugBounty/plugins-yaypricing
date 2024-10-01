<?php
/**
 * Handles the integration of YayCurrency plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\MarketPress;

use YAYDP\Helper\YAYDP_Condition_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_B2B_Market_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( class_exists( 'BM_Price' ) ) {
			add_filter(
				'bm_recalculate_prices_set_item_price',
				function( $res, $product, $item ) {
					if ( function_exists( 'yaydp_unserialize_cart_data' ) && isset( $item['modifiers'] ) ) {
						$modifiers = \yaydp_unserialize_cart_data( $item['modifiers'] );
						if ( ! empty( $modifiers ) ) {
							$res = false;
						}
					}
					return $res;
				},
				100,
				3
			);

			add_filter( 'yaydp_extra_conditions', array( $this, 'user_group_condition' ) );
			add_filter( 'yaydp_check_b2b_market_user_group_condition', array( $this, 'check_b2b_market_user_group_condition' ), 10, 2 );

		}
	}

	public function user_group_condition( $conditions ) {

		if ( ! class_exists( 'BM_User' ) || ! class_exists( 'BM_Helper' ) ) {
			return $conditions;
		}

		$groups          = \BM_User::get_instance();
		$all_user_groups = $groups->get_all_customer_groups();
		$values          = array();
		foreach ( $all_user_groups as $group ) {
			foreach ( $group as $group_slug => $group_id ) {
				$values[] = array(
					'value' => $group_slug,
					'label' => \BM_Helper::get_group_title( $group_id ),
				);
			}
		}
		$conditions[] = array(
			'value'        => 'b2b_market_user_group',
			'label'        => 'B2B Market User Group',
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
			'values'       => $values,
		);
		return $conditions;
	}

	public function check_b2b_market_user_group_condition( $result, $condition ) {

		return YAYDP_Condition_Helper::check_customer_role( $condition );
	}

}
