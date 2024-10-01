<?php
/**
 * Handle Custom Fee rule
 *
 * @package YayPricing\Rule\CheckoutFee
 */

namespace YAYDP\Core\Rule\Checkout_Fee;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Custom_Fee extends \YAYDP\Abstracts\YAYDP_Checkout_Fee_Rule {

	/**
	 * Calculate all possible adjustment created by the rule.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart ) {
		// Note: Lock in LITE version.
		return null;
	}

	/**
	 * Calculate the adjustment amount based on current shipping fee
	 *
	 * @override
	 */
	public function get_adjustment_amount() {
		// Note: Lock in LITE version.
		return 0;
	}

	/**
	 * Calculate total discount amount per order
	 */
	public function get_total_discount_amount() {
		// Note: Lock in LITE version.
		return 0;
	}

	/**
	 * Add fee to the cart
	 */
	public function add_fee() {
		// Note: Lock in LITE version.
	}

	/**
	 * Calculate all encouragements can be created by rule ( include condition encouragements )
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_encouragements( \YAYDP\Core\YAYDP_Cart $cart ) {
		return null;
	}
}
