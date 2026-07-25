<?php
/**
 * Handle checkout fee use time.
 *
 * @package YayPricing\Classes\UseTime
 *
 * @since 2.4
 */

namespace YAYDP\Core\Use_Time;

/**
 * Declare class
 */
class YAYDP_Checkout_Fee_Use_Time extends \YAYDP\Abstracts\YAYDP_Use_Time {

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
		$list_rule_id  = array();
		$running_rules = \yaydp_get_running_checkout_fee_rules();
		$cart_fees     = ( function_exists( 'WC' ) && ! empty( \WC()->cart ) ) ? \WC()->cart->get_fees() : array();
		foreach ( $cart_fees as $fee ) {
			foreach ( $running_rules as $rule ) {
				if ( $rule->get_id() === $fee->id ) {
					$list_rule_id[] = $rule->get_id();
				}
			}
		}
		// Rules applied directly to shipping (apply_to_shipping enabled) do not
		// produce a WC cart fee, so collect their IDs from the adjustment tracker.
		$shipping_applied_ids = \YAYDP\Core\Single_Adjustment\YAYDP_Checkout_Fee_Adjustment::get_applied_to_shipping_rule_ids();
		foreach ( $shipping_applied_ids as $rule_id ) {
			if ( ! in_array( $rule_id, $list_rule_id, true ) ) {
				$list_rule_id[] = $rule_id;
			}
		}
		if ( \yaydp_check_wc_hpos() ) {
			$order = \wc_get_order( $order_id );
			$order->update_meta_data( 'yaydp_checkout_fee_rules', $list_rule_id );
			$order->save();
		} else {
			update_post_meta( $order_id, 'yaydp_checkout_fee_rules', $list_rule_id );
		}
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
		$list_rule_id = $order->get_meta( 'yaydp_checkout_fee_rules' );
		if ( empty( $list_rule_id ) || ! is_array( $list_rule_id ) ) {
			return;
		}
		$all_rules = \yaydp_get_checkout_fee_rules();
		foreach ( $all_rules as $rule ) {
			if ( in_array( $rule->get_id(), $list_rule_id, true ) ) {
				$rule->increase_use_time();
			}
		}
		$rules = array_map(
			function( $rule ) {
				return $rule->get_data();
			},
			$all_rules
		);
		update_option( 'yaydp_checkout_fee_rules', $rules );
	}
}
