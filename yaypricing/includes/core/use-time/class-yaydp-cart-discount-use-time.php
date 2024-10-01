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
		$list_rule_id    = array();
		$applied_coupons = \WC()->cart->get_applied_coupons();
		$running_rules   = \yaydp_get_running_cart_discount_rules();
		foreach ( $applied_coupons as $coupon_code ) {
			foreach ( $running_rules as $rule ) {
				if ( $rule->is_match_coupon( $coupon_code ) ) {
					if ( ! \in_array( $rule->get_id(), $list_rule_id, true ) ) {
						$list_rule_id[] = $rule->get_id();
					}
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
	}

	/**
	 * Increase use time after payment success
	 * If the payment successfully, increase the use_time, otherwise
	 *
	 * @override
	 *
	 * @param array  $result Result of the payment.
	 * @param string $order_id Id of the current order.
	 *
	 * @return array
	 */
	public function after_payment_successful( $result, $order_id ) {
		if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
			$list_rule_id = get_post_meta( $order_id, 'yaydp_cart_discount_rules', true );
			if ( ! empty( $list_rule_id ) ) {
				$all_rules = \yaydp_get_cart_discount_rules();
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
				update_option( 'yaydp_cart_discount_rules', $rules );
			}
		} else {
			delete_post_meta( $order_id, 'yaydp_cart_discount_rules' );
		}
		return $result;
	}

	/**
	 * Increase use time after payment success
	 * No checking because there is no payment method
	 *
	 * @override
	 *
	 * @param string    $url Redirect url.
	 * @param \WC_Order $order Current order.
	 *
	 * @return string
	 */
	public function checkout_no_payment_needed_redirect( $url, $order ) {
		$order_id     = $order->get_id();
		$list_rule_id = get_post_meta( $order_id, 'yaydp_cart_discount_rules', true );
		if ( ! empty( $list_rule_id ) ) {
			$all_rules = \yaydp_get_cart_discount_rules();
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
			update_option( 'yaydp_cart_discount_rules', $rules );
		}
		return $url;
	}
}
