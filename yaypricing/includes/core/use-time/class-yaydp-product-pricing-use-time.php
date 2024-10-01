<?php
/**
 * Handle product pricing use time.
 *
 * @package YayPricing\Classes\UseTime
 *
 * @since 2.4
 */

namespace YAYDP\Core\Use_Time;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Use_Time extends \YAYDP\Abstracts\YAYDP_Use_Time {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Check exist rule before checkout process
	 *
	 * @override
	 *
	 * @throws \Exception $error The error if the rules are not exists.
	 */
	public function before_checkout_process() {
		$cart          = new \YAYDP\Core\YAYDP_Cart();
		$running_rules = \yaydp_get_running_product_pricing_rules();
		foreach ( $cart->get_items() as $item ) {
			if ( ! $item->can_modify() ) {
				continue;
			}
			foreach ( $item->get_modifiers() as $modifier ) {
				$rule     = $modifier->get_rule();
				$is_exist = false;
				foreach ( $running_rules as $check_rule ) {
					if ( $rule->get_id() === $check_rule->get_id() ) {
						$is_exist = true;
						break;
					}
				}
				if ( $is_exist ) {
					continue;
				}
				// translators: %s Rule name.
				throw new \Exception( sprintf( __( '%s has expired. Please reload the page to continue checkout.', 'yaypricing' ), $rule->get_name() ) );
			}
		}
	}

	/**
	 * Add applied rules id to the order meta
	 *
	 * @override
	 *
	 * @param string $order_id The id of current order.
	 */
	public function checkout_order_processed( $order_id ) {
		$cart         = new \YAYDP\Core\YAYDP_Cart();
		$list_rule_id = array();
		foreach ( $cart->get_items() as $item ) {
			if ( ! $item->can_modify() ) {
				continue;
			}
			foreach ( $item->get_modifiers() as $modifier ) {
				$rule = $modifier->get_rule();
				if ( ! \in_array( $rule->get_id(), $list_rule_id, true ) ) {
					$list_rule_id[] = $rule->get_id();
				}
			}
		}
		if ( \yaydp_check_wc_hpos() ) {
			$order = \wc_get_order( $order_id );
			$order->update_meta_data( 'yaydp_product_pricing_rules', $list_rule_id );
			$order->save();
		} else {
			update_post_meta( $order_id, 'yaydp_product_pricing_rules', $list_rule_id );
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
			$list_rule_id = get_post_meta( $order_id, 'yaydp_product_pricing_rules', true );
			if ( ! empty( $list_rule_id ) ) {
				$all_rules = \yaydp_get_product_pricing_rules();
				foreach ( $all_rules as $rule_index => $rule ) {
					if ( in_array( $rule->get_id(), $list_rule_id, true ) ) {
						$all_rules[ $rule_index ]->increase_use_time();
					}
				}
				$rules = array_map(
					function( $rule ) {
						return $rule->get_data();
					},
					$all_rules
				);
				update_option( 'yaydp_product_pricing_rules', $rules );
			}
		} else {
			delete_post_meta( $order_id, 'yaydp_product_pricing_rules' );
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
		$list_rule_id = get_post_meta( $order_id, 'yaydp_product_pricing_rules', true );
		if ( ! empty( $list_rule_id ) ) {
			$all_rules = \yaydp_get_product_pricing_rules();
			foreach ( $all_rules as $rule_index => $rule ) {
				if ( in_array( $rule->get_id(), $list_rule_id, true ) ) {
					$all_rules[ $rule_index ]->increase_use_time();
				}
			}
			$rules = array_map(
				function( $rule ) {
					return $rule->get_data();
				},
				$all_rules
			);
			update_option( 'yaydp_product_pricing_rules', $rules );
		}
		return $url;
	}
}
