<?php
/**
 * Managing checkout fee adjustments
 *
 * @package YayPricing\Abstracts
 *
 * @since 2.4
 */

namespace YAYDP\Core\Adjustments;

/**
 * Declare class
 */
class YAYDP_Checkout_Fee_Adjustments extends \YAYDP\Abstracts\YAYDP_Adjustments {

	/**
	 * Contains current cart
	 */
	protected $cart = null;

	/**
	 * Constructor
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function __construct( $cart ) {
		$this->cart = $cart;
	}

	/**
	 * Collect adjustment
	 *
	 * @override
	 */
	public function collect() {
		$running_rules = \yaydp_get_running_checkout_fee_rules();
		foreach ( $running_rules as $rule ) {
			$adjustment = $rule->create_possible_adjustment_from_cart( $this->cart );
			if ( ! empty( $adjustment ) ) {
				parent::add_adjustment( new \YAYDP\Core\Single_Adjustment\YAYDP_Checkout_Fee_Adjustment( $adjustment, $this->cart ) );
			}
		}

		if ( empty( $this->adjustments ) ) {
			return;
		}

		if ( \yaydp_checkout_fee_is_applied_to_maximum_amount_per_order() ) {
			parent::sort_by_desc_amount();
		}

		if ( \yaydp_checkout_fee_is_applied_to_minimum_amount_per_order() ) {
			parent::sort_by_asc_amount();
		}

	}

	/**
	 * Apply adjustments
	 *
	 * @override
	 */
	public function apply() {
		foreach ( $this->adjustments as $adjustment ) {

			$adjustment->create_fee();

			if ( \yaydp_checkout_fee_is_applied_first_rules() ) {
				break;
			}

			if ( \yaydp_checkout_fee_is_applied_to_maximum_amount_per_order() ) {
				break;
			}

			if ( \yaydp_checkout_fee_is_applied_to_minimum_amount_per_order() ) {
				break;
			}
		}
	}

	/**
	 * Get cart
	 */
	public function get_cart() {
		return $this->cart;
	}

}
