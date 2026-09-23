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
		// Only percentage and money-amount types reduce shipping, never by more than the fee itself.
		return ( \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_percentage_adjustment( $pricing_type ) || \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_money_amount( $pricing_type ) ) ? min( $cart_shipping_fee, $adjustment_amount ) : 0;
	}

	/**
	 * Add fee to the cart
	 */
	public function add_fee() {
		if ( ! function_exists( 'WC' ) || empty( \WC()->cart ) ) {
			return;
		}
		$discount_amount = $this->get_total_discount_amount();
		$settings = \YAYDP\Settings\YAYDP_Checkout_Fee_Settings::get_instance();
		$tax_class = $this->get_tax_class() !== 'standard' ? $this->get_tax_class() : '';

		if ( empty( $discount_amount ) ) {
			return;
		}	

		$default_taxable = $settings->checkout_fees_include_tax();
		
		$taxable = apply_filters( 
			'yaydp_checkout_fee_taxable', 
			$default_taxable, 
			$this->get_id(), 
			$this->get_name(),
			$discount_amount,
			$tax_class
		);

		$fee_data = array(
			'id'        => $this->get_id(),
			'name'      => $this->get_translated_name(),
			'amount'    => \YAYDP\Helper\YAYDP_Pricing_Helper::convert_fee( - $discount_amount ),
			'taxable'   => $taxable,
			'tax_class' => $tax_class,
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
		return new \YAYDP\Core\Encouragement\YAYDP_Checkout_Fee_Encouragement(
			array(
				'cart'                      => $cart,
				'rule'                      => $this,
				'conditions_encouragements' => $conditions_encouragements,
			)
		);
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
				// No fee line carries this amount, so it is recorded here for the report.
				\YAYDP\Helper\YAYDP_Shipping_Adjustment_Tracker::record( $this->get_id(), $package_index, $rate_id, $rate_cost - $final_cost );
				$packages[ $package_index ]['rates'][ $rate_id ]->modified_rules = array_merge( $packages[ $package_index ]['rates'][ $rate_id ]->modified_rules ?? array(), array( $this->get_id() ) );
				$packages[ $package_index ]['rates'][ $rate_id ]->set_taxes( \WC_Tax::calc_shipping_tax( $final_cost, \WC_Tax::get_shipping_tax_rates() ) );
			}
		}
		return $packages;
	}
}
