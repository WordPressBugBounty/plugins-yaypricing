<?php
/**
 * Handle Shipping Fee rule
 *
 * @package YayPricing\Rule\CheckoutFee
 */

namespace YAYDP\Core\Rule\Checkout_Fee;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Shipping_Fee extends \YAYDP\Abstracts\YAYDP_Checkout_Fee_Rule {

	/**
	 * Calculate all possible adjustment created by the rule.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart ) {

		if ( \YAYDP\Core\Manager\YAYDP_Exclude_Manager::check_coupon_exclusions( $this ) ) {
			return null;
		}

		if ( $this->check_conditions( $cart ) ) {
			return array(
				'rule' => $this,
			);
		}
		return null;
	}

	/**
	 * Calculate the adjustment amount based on current shipping fee
	 *
	 * @override
	 */
	public function get_adjustment_amount() {
		$pricing_type              = $this->get_pricing_type();
		$pricing_value             = $this->get_pricing_value();
		$maximum_adjustment_amount = $this->get_maximum_adjustment_amount();
		$cart_shipping_fee         = \yaydp_get_shipping_fee();
		$adjustment_amount         = \YAYDP\Helper\YAYDP_Pricing_Helper::calculate_adjustment_amount( $cart_shipping_fee, $pricing_type, $pricing_value, $maximum_adjustment_amount );
		return $adjustment_amount;
	}

	/**
	 * Calculate total discount amount per order
	 */
	public function get_total_discount_amount() {
		$adjustment_amount = $this->get_adjustment_amount();
		$pricing_type      = $this->get_pricing_type();
		$cart_shipping_fee = \yaydp_get_shipping_fee();
		if ( \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
			return min( $cart_shipping_fee, $adjustment_amount );
		}
		if ( \yaydp_is_fixed_pricing_type( $pricing_type ) ) {
			return min( $cart_shipping_fee, $adjustment_amount );
		}
		return 0;
	}

	/**
	 * Add fee to the cart
	 */
	public function add_fee() {

		$discount_amount = $this->get_total_discount_amount();

		if ( empty( $discount_amount ) ) {
			return;
		}

		$taxable = true;

		$fee_data = array(
			'id'     => $this->get_id(),
			'name'   => $this->get_name(),
			'amount' => \YAYDP\Helper\YAYDP_Pricing_Helper::convert_fee( - $discount_amount ),
			'taxable' => $taxable
		);
		\WC()->cart->fees_api()->add_fee( $fee_data );
	}

	/**
	 * Calculate all encouragements can be created by rule ( include condition encouragements )
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_encouragements( \YAYDP\Core\YAYDP_Cart $cart ) {
		$conditions_encouragements = parent::get_conditions_encouragements( $cart );
		if ( empty( $conditions_encouragements ) ) {
			return null;
		}
		return null;
	}

	/**
	 * Adjust shipping cost
	 *
	 * @since 3.1.1
	 */
	public function adjust_shipping( $packages ) {
		$pricing_type              = $this->get_pricing_type();
		$pricing_value             = $this->get_pricing_value();
		$maximum_adjustment_amount = $this->get_maximum_adjustment_amount();
		foreach ( $packages as $package_index => $package ) {
			foreach ( $package['rates'] as $rate_id => $rate_instance ) {
				if ( empty( $packages[ $package_index ]['rates'][ $rate_id ]->modified_rules ) ) {
					$packages[ $package_index ]['rates'][ $rate_id ]->modified_rules = array();
				}
				if ( in_array( $this->get_id(), $packages[ $package_index ]['rates'][ $rate_id ]->modified_rules ) ) {
					continue;
				}
				$rate_cost         = $rate_instance->get_cost();
				$adjustment_amount = \YAYDP\Helper\YAYDP_Pricing_Helper::calculate_adjustment_amount( $rate_cost, $pricing_type, $pricing_value, $maximum_adjustment_amount );
				$final_cost        = max( 0, $rate_cost - $adjustment_amount );
				$packages[ $package_index ]['rates'][ $rate_id ]->set_cost( $final_cost );
				$packages[ $package_index ]['rates'][ $rate_id ]->modified_rules = array_merge( $packages[ $package_index ]['rates'][ $rate_id ]->modified_rules ?? array(), [ $this->get_id() ] );
				$packages[ $package_index ]['rates'][ $rate_id ]->set_taxes( \WC_Tax::calc_shipping_tax( $final_cost, \WC_Tax::get_shipping_tax_rates() ) );
			}
		}
		return $packages;
	}
}
