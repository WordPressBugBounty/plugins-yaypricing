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

		$this->store_discount_amounts( $order_id, $cart );
	}

	/**
	 * Store the discount amount contributed by each product pricing rule.
	 *
	 * Extra items are included so that free items given by BOGO and Buy X Get Y
	 * rules are valued at the amount they discounted, rather than being ignored.
	 *
	 * @param string                $order_id The id of current order.
	 * @param \YAYDP\Core\YAYDP_Cart $cart    Current cart.
	 */
	private function store_discount_amounts( $order_id, $cart ) {
		$amounts = array();
		foreach ( $cart->get_items_include_extra() as $item ) {
			if ( ! $item->can_modify() ) {
				continue;
			}
			foreach ( $item->get_modifiers() as $modifier ) {
				$rule_id = $modifier->get_rule()->get_id();
				$amount  = $modifier->get_discount_per_unit() * $modifier->get_modify_quantity();
				if ( ! isset( $amounts[ $rule_id ] ) ) {
					$amounts[ $rule_id ] = 0;
				}
				$amounts[ $rule_id ] += $amount;
			}
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
		$list_rule_id = $order->get_meta( 'yaydp_product_pricing_rules' );
		if ( empty( $list_rule_id ) || ! is_array( $list_rule_id ) ) {
			return;
		}
		$this->with_rules_lock(
			'yaydp_product_pricing_rules',
			function() use ( $order, $list_rule_id ) {
				// Claimed inside the lock so two completions of the same order
				// ( gateway webhook and admin at the same instant ) cannot both count.
				if ( ! $this->claim_order_completion( $order, '_yaydp_product_pricing_use_time_counted' ) ) {
					return;
				}
				$this->increment_stored_use_time( 'yaydp_product_pricing_rules', $list_rule_id );
			}
		);
	}
}
