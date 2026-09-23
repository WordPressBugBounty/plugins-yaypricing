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
		$amounts       = array();
		foreach ( $cart_fees as $fee ) {
			foreach ( $running_rules as $rule ) {
				if ( $rule->get_id() === $fee->id ) {
					$list_rule_id[] = $rule->get_id();
					// Fees are money gained rather than given up, so they are stored
					// negated to keep the reported discount cost netting correctly.
					$amounts[ $rule->get_id() ] = - $fee->amount;
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
			// These rules adjust the shipping cost instead of adding a cart fee,
			// so their amount comes from what they changed the chosen shipping
			// rate by. It stays null when nothing was observed, because an
			// unknown amount must not be reported as zero.
			if ( ! isset( $amounts[ $rule_id ] ) ) {
				$amounts[ $rule_id ] = \YAYDP\Helper\YAYDP_Shipping_Adjustment_Tracker::get_chosen_amount( $rule_id );
			}
		}
		if ( \yaydp_check_wc_hpos() ) {
			$order = \wc_get_order( $order_id );
			$order->update_meta_data( 'yaydp_checkout_fee_rules', $list_rule_id );
			$order->save();
		} else {
			update_post_meta( $order_id, 'yaydp_checkout_fee_rules', $list_rule_id );
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
		$list_rule_id = $order->get_meta( 'yaydp_checkout_fee_rules' );
		if ( empty( $list_rule_id ) || ! is_array( $list_rule_id ) ) {
			return;
		}
		$this->with_rules_lock(
			'yaydp_checkout_fee_rules',
			function() use ( $order, $list_rule_id ) {
				// Claimed inside the lock so two completions of the same order
				// ( gateway webhook and admin at the same instant ) cannot both count.
				if ( ! $this->claim_order_completion( $order, '_yaydp_checkout_fee_use_time_counted' ) ) {
					return;
				}
				$this->increment_stored_use_time( 'yaydp_checkout_fee_rules', $list_rule_id );
			}
		);
	}
}
