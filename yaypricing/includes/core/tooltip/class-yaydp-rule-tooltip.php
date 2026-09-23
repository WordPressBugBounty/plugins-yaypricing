<?php
/**
 * Tooltip describing a whole rule: cart discount coupon rows and checkout fee rows.
 * Product pricing tooltips are per cart item instead (YAYDP_Product_Pricing_Tooltip).
 *
 * @package YayPricing\Classes\Tooltip
 *
 * @since 3.5.8
 */

namespace YAYDP\Core\Tooltip;

/**
 * Declare class
 */
class YAYDP_Rule_Tooltip extends \YAYDP\Abstracts\YAYDP_Tooltip {

	/**
	 * Get tooltip content
	 * Replace all variables
	 *
	 * @override
	 */
	public function get_content() {
		$raw_content = parent::get_raw_content();
		if ( empty( $this->rule ) ) {
			return $raw_content;
		}
		return str_replace(
			array( '[discount_value]', '[discount_amount]' ),
			array( $this->get_formatted_pricing_value(), $this->get_formatted_total_amount() ),
			$raw_content
		);
	}

	/**
	 * [discount_value]: the configured value, e.g. "10%" or "5 $" (money converted to the display currency).
	 */
	private function get_formatted_pricing_value() {
		$pricing_type  = $this->rule->get_pricing_type();
		$pricing_value = $this->rule->get_pricing_value();
		if ( \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_money_amount( $pricing_type ) ) {
			$pricing_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $pricing_value );
		}
		return \yaydp_get_formatted_pricing_value( $pricing_value, $pricing_type );
	}

	/**
	 * [discount_amount]: the total the rule takes off (or adds, for fees) on the current cart.
	 */
	private function get_formatted_total_amount() {
		$cart   = new \YAYDP\Core\YAYDP_Cart();
		$amount = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $this->rule->get_total_discount_amount( $cart ) );
		return \wc_price( $amount );
	}
}
