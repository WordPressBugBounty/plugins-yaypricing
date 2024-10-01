<?php
/**
 * Class represents a checkout fee rule for YAYDP.
 * It contains methods for setting and getting the rule's properties
 *
 * @package YayPricing\Abstract
 */

namespace YAYDP\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
abstract class YAYDP_Checkout_Fee_Rule extends YAYDP_Rule {

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
	 * Calculate the adjustment amount based on current shipping fee
	 * Must be implemented by child
	 */
	abstract public function get_adjustment_amount();

	/**
	 * Calculate total discount amount per order
	 * Must be implemented by child
	 */
	abstract public function get_total_discount_amount();

	/**
	 * Add fee to the cart
	 * Must be implemented by child
	 */
	abstract public function add_fee();

	/**
	 * Return fee content
	 */
	public function get_fee_content() {
		$tooltips = array();
		$tooltip  = $this->get_tooltip();
		if ( ! $tooltip->is_enabled() ) {
			return '';
		}
		$tooltips[] = $tooltip;
		ob_start();
		\wc_get_template(
			'fee/yaydp-cart-fee.php',
			array(
				'tooltips' => $tooltips,
			),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}
