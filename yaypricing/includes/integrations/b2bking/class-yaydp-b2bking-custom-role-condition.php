<?php
/**
 * Condition type: b2bking_custom_role — the customer's B2BKing registration role.
 *
 * @package YayPricing\Integrations
 * @since 3.5.8
 */

namespace YAYDP\Integrations\B2bking;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_B2BKing_Custom_Role_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'b2bking_custom_role';
	}

	public function label() {
		return __( 'B2BKing Custom Role', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		$roles = get_posts(
			array(
				'post_type'   => 'b2bking_custom_role',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => 'b2bking_custom_role_status',
						'value' => 1,
					),
				),
			)
		);
		return array(
			'kind'    => 'select',
			'options' => array_map(
				function ( $role ) {
					return array(
						'value' => 'role_' . $role->ID,
						'label' => get_the_title( apply_filters( 'wpml_object_id', $role->ID, 'post', true ) ),
					);
				},
				$roles
			),
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$role  = get_user_meta( $ctx->user_id(), 'b2bking_registration_role', true );
		$roles = array_intersect( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ), array( $role ) );
		return YAYDP_Comparators::matches_list( ! empty( $roles ), $condition );
	}
}
