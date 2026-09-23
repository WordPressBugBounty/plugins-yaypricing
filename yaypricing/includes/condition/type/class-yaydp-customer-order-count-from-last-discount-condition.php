<?php
/**
 * Condition type: customer_order_count_from_last_discount
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;
use YAYDP\Condition\YAYDP_Order_History_Query;

defined( 'ABSPATH' ) || exit;

class YAYDP_Customer_Order_Count_From_Last_Discount_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'customer_order_count_from_last_discount';
	}

	public function label() {
		return __( 'Orders since last discount', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Number of orders counted from the last time they got a discount from YayPricing.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_PURCHASE_HISTORY;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::numeric();
	}

	public function editor() {
		return array( 'kind' => 'number' );
	}

	public function default_value() {
		return 100;
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( ! $ctx->is_logged_in() ) {
			return false;
		}
		$rule_id  = $ctx->rule() ? $ctx->rule()->get_id() : '';
		$meta_key = $this->usage_meta_key( $ctx->family() );
		foreach ( YAYDP_Order_History_Query::recent_orders( $ctx->user_id(), $condition['value'] + 1 ) as $index => $order ) {
			$used = get_post_meta( $order->get_id(), $meta_key, true );
			if ( is_array( $used ) && in_array( $rule_id, $used ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- stored ids may be strings.
				return YAYDP_Comparators::compare_numeric( $index, $condition );
			}
		}
		return true;
	}

	/**
	 * Order meta listing the rules of this family applied to that order.
	 *
	 * @param string|null $family Rule family.
	 * @return string
	 */
	private function usage_meta_key( $family ) {
		$keys = array(
			YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT => 'yaydp_cart_discount_rules',
			YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE  => 'yaydp_checkout_fee_rules',
		);
		return isset( $keys[ $family ] ) ? $keys[ $family ] : 'yaydp_product_pricing_rules';
	}
}
