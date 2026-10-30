<?php
/**
 * Handle cart discount use time.
 *
 * @package YayPricing\Classes\UseTime
 *
 * @since 2.4
 */

namespace YAYDP\Core\Use_Time;

/**
 * Declare class
 */
class YAYDP_Cart_Discount_Use_Time extends \YAYDP\Abstracts\YAYDP_Use_Time {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Check exist rule before checkout process
	 *
	 * @override
	 *
	 * @throws \Exception $error The error if the rules are not exists.
	 */
	public function before_checkout_process() {
	}

	/**
	 * Add applied rules id to the order meta
	 *
	 * @override
	 *
	 * @param string $order_id The id of current order.
	 */
	public function checkout_order_processed( $order_id ) {
		$list_rule_id = array();
		if ( ! empty( \WC()->cart ) ) {
			$applied_coupons = \WC()->cart->get_applied_coupons();
		} else {
			$applied_coupons = array();
		}
		$rules_by_coupon = array();
		foreach ( $applied_coupons as $coupon_code ) {
			/*
			 * Running rules only, and the combined discount is not considered:
			 * this records which rules an order applied, and the combined coupon
			 * is not a rule. Unchanged from before the resolver.
			 */
			foreach ( \yaydp_resolve_coupon_rules( $coupon_code, array( 'combined' => 'skip' ) ) as $rule ) {
				$rule_id = $rule->get_id();
				if ( ! \in_array( $rule_id, $list_rule_id, true ) ) {
					$list_rule_id[] = $rule_id;
				}
				$rules_by_coupon[ $coupon_code ][] = $rule_id;
			}
		}
		if ( \yaydp_check_wc_hpos() ) {
			$order = \wc_get_order( $order_id );
			$order->update_meta_data( 'yaydp_cart_discount_rules', $list_rule_id );
			$order->save();
		} else {
			update_post_meta( $order_id, 'yaydp_cart_discount_rules', $list_rule_id );
		}

		$this->store_discount_amounts( $order_id, $rules_by_coupon );
	}

	/**
	 * Store the discount amount contributed by each cart discount rule.
	 *
	 * The amount is taken from the coupon the rule was applied through. When a
	 * single coupon covers several rules ( the combined coupon ), its total
	 * cannot be split between them, so those rules are recorded as unknown
	 * rather than given a fabricated share.
	 *
	 * @param string $order_id        The id of current order.
	 * @param array  $rules_by_coupon Map of coupon code => list of rule ids.
	 */
	private function store_discount_amounts( $order_id, $rules_by_coupon ) {
		if ( empty( $rules_by_coupon ) || empty( \WC()->cart ) ) {
			return;
		}
		$amounts = array();
		foreach ( $rules_by_coupon as $coupon_code => $rule_ids ) {
			$discount = \WC()->cart->get_coupon_discount_amount( $coupon_code, true );
			if ( count( $rule_ids ) > 1 ) {
				/**
				 * The coupon total covers several rules and cannot be split, so
				 * it is recorded against the combined placeholder. The rules it
				 * covers stay unknown rather than each being credited a share
				 * they may not have given.
				 */
				$combined             = \YAYDP\Helper\YAYDP_Rule_Discount_Helper::COMBINED_RULE_ID;
				$current              = isset( $amounts[ $combined ] ) ? $amounts[ $combined ] : 0;
				$amounts[ $combined ] = $current + $discount;
				foreach ( $rule_ids as $rule_id ) {
					$amounts[ $rule_id ] = null;
				}
				continue;
			}
			$amounts[ reset( $rule_ids ) ] = $discount;
		}
		\YAYDP\Helper\YAYDP_Rule_Discount_Helper::merge( $order_id, $amounts );
	}

	/**
	 * Rule type slug.
	 *
	 * @override
	 */
	protected function get_rule_type() {
		return 'cart_discount';
	}
}
