<?php
/**
 * Condition type: b2b_market_user_group — B2B Market groups are WP roles, so
 * this is the customer-role check with the group list as its options.
 *
 * @package YayPricing\Integrations
 * @since 3.5.8
 */

namespace YAYDP\Integrations\MarketPress;

use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_B2B_Market_User_Group_Condition extends \YAYDP\Condition\Type\YAYDP_Customer_Role_Condition {

	public function slug() {
		return 'b2b_market_user_group';
	}

	public function label() {
		return __( 'B2B Market User Group', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_OTHERS;
	}

	public function editor() {
		$options = array();
		if ( class_exists( 'BM_User' ) && class_exists( 'BM_Helper' ) ) {
			foreach ( \BM_User::get_instance()->get_all_customer_groups() as $group ) {
				foreach ( $group as $slug => $id ) {
					$options[] = array(
						'value' => $slug,
						'label' => \BM_Helper::get_group_title( $id ),
					);
				}
			}
		}
		return array(
			'kind'    => 'select',
			'options' => $options,
		);
	}
}
