<?php
/**
 * Handles the integration of YayCurrency plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\B2bking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_B2BKing_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'B2bking' ) ) {
			return;
		}

		add_filter( 'yaydp_extra_conditions', array( $this, 'user_group_condition' ) );
		add_filter( 'yaydp_extra_conditions', array( $this, 'user_role_condition' ) );
		add_filter( 'yaydp_check_b2bking_user_group_condition', array( $this, 'check_b2bking_user_group_condition' ), 10, 2 );
		add_filter( 'yaydp_check_b2bking_custom_role_condition', array( $this, 'check_b2bking_user_condition' ), 10, 2 );

	}

	public function user_group_condition( $conditions ) {

		if ( ! class_exists( 'B2bking' ) ) {
			return $conditions;
		}

		$groups = get_posts([
			'post_type' => 'b2bking_group',
			  'post_status' => 'publish',
			  'numberposts' => -1,
		]);
		
		$conditions[] = array(
			'value'        => 'b2bking_user_group',
			'label'        => 'B2BKing User Group',
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
			'values'       => array_map( function( $group ) {
				return array(
					'value' => $group->ID,
					'label' => $group->post_title,
				);
			}, $groups ),
		);
		return $conditions;
	}

	public function user_role_condition( $conditions ) {

		if ( ! class_exists( 'B2bking' ) ) {
			return $conditions;
		}

		$custom_roles = get_posts([
			'post_type' => 'b2bking_custom_role',
			  'post_status' => 'publish',
			  'numberposts' => -1,
			  'orderby' => 'menu_order',
			  'order' => 'ASC',
			  'meta_query'=> array(
				  'relation' => 'AND',
				array(
					'key' => 'b2bking_custom_role_status',
					'value' => 1
				),
			)
		]);
		
		$conditions[] = array(
			'value'        => 'b2bking_custom_role',
			'label'        => 'B2BKing Custom Role',
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
			'values'       => array_map( function( $role ) {
				return array(
					'value' => 'role_' . $role->ID,
					'label' => get_the_title(apply_filters( 'wpml_object_id', $role->ID, 'post', true )),
				);
			}, $custom_roles ),
		);
		return $conditions;
	}

	public function check_b2bking_user_condition( $result, $condition ) {

		$current_user       = \wp_get_current_user();
		if ( ! $current_user ) {
			return false;
		}
		$user_role = get_user_meta($current_user->ID, 'b2bking_registration_role', true);

		$condition_values   = array_map(
			function ( $item ) {
				return $item['value'];
			},
			$condition['value']
		);
		$intersection_roles = array_intersect( $condition_values, [$user_role] );
		return 'in_list' === $condition['comparation'] ? ! empty( $intersection_roles ) : empty( $intersection_roles );

	}

	public function check_b2bking_user_group_condition( $result, $condition ) {
		if ( ! function_exists( 'b2bking' ) ) { 
			return false;
		}

		$group_id = \b2bking()->get_user_group();

		$condition_values   = array_map(
			function ( $item ) {
				return $item['value'];
			},
			$condition['value']
		);
		$intersection_groups = array_intersect( $condition_values, [ $group_id ] );
		return 'in_list' === $condition['comparation'] ? ! empty( $intersection_groups ) : empty( $intersection_groups );
		
	}

}
