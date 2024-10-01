<?php
/**
 * Handle checkout fee tooltip
 *
 * @package YayPricing\Classes\Tooltip
 *
 * @since 2.4
 */

namespace YAYDP\Core\Tooltip;

/**
 * Declare class
 */
class YAYDP_Checkout_Fee_Tooltip extends \YAYDP\Abstracts\YAYDP_Tooltip {

	/**
	 * Get tooltip content
	 * Replace all variables
	 *
	 * @override
	 */
	public function get_content() {
		$raw_content      = parent::get_raw_content();
		$replaced_content = $this->replace_discount_value( $raw_content );
		$replaced_content = $this->replace_discount_amount( $replaced_content );
		return $replaced_content;
	}

	/**
	 * Replace [discount_value] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_discount_value( $raw_content ) {
		$rule          = $this->modifier;
		$pricing_type  = $rule->get_pricing_type();
		$pricing_value = $rule->get_pricing_value();
		if ( \yaydp_is_fixed_pricing_type( $pricing_type ) ) {
			$pricing_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $pricing_value );
		}
		$formatted_discount_value = \yaydp_get_formatted_pricing_value( $pricing_value, $pricing_type );
		$replaced_content         = str_replace( '[discount_value]', $formatted_discount_value, $raw_content );
		return $replaced_content;

	}

	/**
	 * Replace [discount_amount] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_discount_amount( $raw_content ) {
		$rule                  = $this->modifier;
		$cart                  = new \YAYDP\Core\YAYDP_Cart();
		$total_discount_amount = $rule->get_total_discount_amount( $cart );
		$total_discount_amount = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $total_discount_amount );
		$replaced_content      = str_replace( '[discount_amount]', \wc_price( $total_discount_amount ), $raw_content );
		return $replaced_content;
	}

}
