<?php
/**
 * Condition type: shipping_method
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Shipping_Method_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'shipping_method';
	}

	public function label() {
		return __( 'Shipping method', 'yaypricing' );
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
			'source' => 'shipping_methods',
		);
	}

	public function frontend_requirements() {
		return array( 'shipping' );
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$session = $ctx->session();
		$methods = $session ? $session->get( 'chosen_shipping_methods' ) : array();
		$posted  = $ctx->posted( 'shipping_method' );
		if ( ! empty( $posted ) && is_array( $posted ) ) {
			$methods = array_map( 'sanitize_text_field', $posted );
		}
		if ( empty( $methods ) ) {
			return false;
		}
		$ids     = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$in_list = false;
		foreach ( $methods as $method ) {
			$method_id = explode( ':', $method )[0];
			if ( in_array( $method, $ids ) || in_array( $method_id, $ids ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- instance ids may be stored as ints.
				$in_list = true;
				break;
			}
		}
		return YAYDP_Comparators::matches_list( $in_list, $condition );
	}
}
