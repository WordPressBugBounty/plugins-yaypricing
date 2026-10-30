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
	 * Rule type slug.
	 *
	 * @override
	 */
	protected function get_rule_type() {
		return 'checkout_fee';
	}
}
