<?php
/**
 * Condition type: customer_role
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Customer_Role_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'customer_role';
	}

	public function label() {
		return __( 'Customer role', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CUSTOMER;
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'customer_roles',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$roles = array_intersect( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ), $ctx->user()->roles );
		return YAYDP_Comparators::matches_list( ! empty( $roles ), $condition );
	}
}
