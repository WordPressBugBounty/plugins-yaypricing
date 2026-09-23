<?php
/**
 * Condition type: payment_method
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Payment_Method_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'payment_method';
	}

	public function label() {
		return __( 'Payment method', 'yaypricing' );
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
			'source' => 'payment_methods',
		);
	}

	public function frontend_requirements() {
		return array( 'payment' );
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$chosen = $ctx->session() ? $this->chosen_method( $ctx ) : null;
		if ( empty( $chosen ) ) {
			return false;
		}
		return YAYDP_Comparators::matches_list( in_array( $chosen, \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ), true ), $condition );
	}

	/**
	 * Posted form value, else German Market's block-checkout choice, else the
	 * first enabled gateway on block checkout, else the classic session choice.
	 *
	 * @param YAYDP_Condition_Context $ctx Context.
	 * @return string|null
	 */
	private function chosen_method( YAYDP_Condition_Context $ctx ) {
		$posted = $ctx->posted( 'payment_method' );
		if ( ! empty( $posted ) ) {
			return sanitize_text_field( $posted );
		}
		if ( class_exists( 'Woocommerce_German_Market' ) ) {
			$blocks_choice = $ctx->session()->get( 'german_market_wc_blocks_active_payment_method' );
			return ! empty( $blocks_choice ) ? $blocks_choice : $ctx->first_enabled_gateway_id();
		}
		if ( has_block( 'woocommerce/checkout' ) ) {
			return $ctx->first_enabled_gateway_id();
		}
		$chosen = $ctx->session()->get( 'chosen_payment_method' );
		return is_string( $chosen ) && ! empty( $chosen ) ? $chosen : null;
	}
}
