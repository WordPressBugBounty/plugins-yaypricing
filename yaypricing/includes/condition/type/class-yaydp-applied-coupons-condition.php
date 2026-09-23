<?php
/**
 * Condition type: applied_coupons
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Applied_Coupons_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'applied_coupons';
	}

	public function label() {
		return __( 'Coupon used', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_OTHERS;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'coupons',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$coupons = $ctx->applied_coupons();
		if ( empty( $coupons ) && YAYDP_Comparators::IN_LIST !== $condition['comparation'] ) {
			return false;
		}
		$lower   = function ( $code ) {
			return is_null( $code ) ? null : mb_strtolower( $code, 'UTF-8' );
		};
		$codes   = array_map( $lower, array_map( 'wc_get_coupon_code_by_id', \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) ) );
		$applied = array_map( $lower, $coupons );
		return YAYDP_Comparators::matches_list( ! empty( array_intersect( $applied, $codes ) ), $condition );
	}
}
