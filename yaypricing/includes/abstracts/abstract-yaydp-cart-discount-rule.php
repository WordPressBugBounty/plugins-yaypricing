<?php
/**
 * Class represents a cart discount rule for YAYDP products.
 * It contains methods for setting and getting the rule's properties, as well as applying the rule to a cart
 *
 * @package YayPricing\Abstract
 */

namespace YAYDP\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
abstract class YAYDP_Cart_Discount_Rule extends YAYDP_Rule {

	/**
	 * Calculate all possible adjustment created by the rule.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	abstract public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart );

	/**
	 * Calculate all encouragements can be created by rule ( include condition encouragements )
	 * Must be implemented by child
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	abstract public function get_encouragements( \YAYDP\Core\YAYDP_Cart $cart );

	/**
	 * Return coupon code based on rule data
	 */
	public function get_coupon_code() {
		$use_id_as_code = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->use_id_as_code();
		return $use_id_as_code ? $this->get_id() : $this->get_name();
	}

	/**
	 * Is given coupon code match with this rule.
	 *
	 * @param string $code Coupon code.
	 */
	public function is_match_coupon( $code ) {
		if ( \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->use_id_as_code() ) {
			return $this->get_id() === $code;
		}
		return strtolower( $this->get_name() ) === $code;
	}
}
