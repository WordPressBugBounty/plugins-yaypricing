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
		$running_rules      = \yaydp_get_running_cart_discount_rules();
		$rules_by_coupon    = array();
		foreach ( $applied_coupons as $coupon_code ) {
			foreach ( $running_rules as $rule ) {
				if ( $rule->is_match_coupon( $coupon_code ) ) {
					if ( ! \in_array( $rule->get_id(), $list_rule_id, true ) ) {
						$list_rule_id[] = $rule->get_id();
					}
					$rules_by_coupon[ $coupon_code ][] = $rule->get_id();
				}
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
	 * Increment use_time on applied rules when the order is completed.
	 *
	 * @override
	 *
	 * @param int $order_id Given order id.
	 */
	public function on_order_completed( $order_id ) {
		$order = \wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$list_rule_id = $order->get_meta( 'yaydp_cart_discount_rules' );
		if ( empty( $list_rule_id ) || ! is_array( $list_rule_id ) ) {
			return;
		}
		$this->with_rules_lock(
			'yaydp_cart_discount_rules',
			function() use ( $order, $list_rule_id ) {
				// Claimed inside the lock so two completions of the same order
				// ( gateway webhook and admin at the same instant ) cannot both count.
				if ( ! $this->claim_order_completion( $order, '_yaydp_cart_discount_use_time_counted' ) ) {
					return;
				}
				$this->increment_stored_use_time( 'yaydp_cart_discount_rules', $list_rule_id );
			}
		);
	}
}
