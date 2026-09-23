<?php
/**
 * Condition type: b2bking_user_group — the customer's B2BKing group.
 *
 * @package YayPricing\Integrations
 * @since 3.5.8
 */

namespace YAYDP\Integrations\B2bking;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_B2BKing_User_Group_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'b2bking_user_group';
	}

	public function label() {
		return __( 'B2BKing User Group', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		$groups = get_posts(
			array(
				'post_type'   => 'b2bking_group',
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);
		return array(
			'kind'    => 'select',
			'options' => array_map(
				function ( $group ) {
					return array(
						'value' => $group->ID,
						'label' => $group->post_title,
					);
				},
				$groups
			),
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( ! function_exists( 'b2bking' ) ) {
			return false;
		}
		$groups = array_intersect( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ), array( \b2bking()->get_user_group() ) );
		return YAYDP_Comparators::matches_list( ! empty( $groups ), $condition );
	}
}
