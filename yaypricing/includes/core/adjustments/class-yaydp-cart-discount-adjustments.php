<?php
/**
 * Managing cart discount adjustments
 *
 * @package YayPricing\Abstracts
 *
 * @since 2.4
 */

namespace YAYDP\Core\Adjustments;

/**
 * Declare class
 */
class YAYDP_Cart_Discount_Adjustments extends \YAYDP\Abstracts\YAYDP_Adjustments {

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
		$running_rules = \yaydp_get_running_cart_discount_rules();
		foreach ( $running_rules as $rule ) {
			$adjustment = $rule->create_possible_adjustment_from_cart( $this->cart );
			if ( ! empty( $adjustment ) ) {
				parent::add_adjustment( new \YAYDP\Core\Single_Adjustment\YAYDP_Cart_Discount_Adjustment( $adjustment, $this->cart ) );
			}
		}

		if ( empty( $this->adjustments ) ) {
			return;
		}

		if ( \yaydp_cart_discount_is_applied_to_maximum_amount_per_order() ) {
			parent::sort_by_desc_amount();
		}

		if ( \yaydp_cart_discount_is_applied_to_minimum_amount_per_order() ) {
			parent::sort_by_asc_amount();
		}

	}

	/**
	 * Apply adjustments
	 *
	 * @override
	 */
	public function apply() {
		if ( \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->is_combined() ) {
			\YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::add_coupon();
			return;
		}
		foreach ( $this->adjustments as $adjustment ) {

			add_filter( 'yaydp_prevent_recalculate_cart_discount', '__return_true' );

			$adjustment->create_discount();

			remove_filter( 'yaydp_prevent_recalculate_cart_discount', '__return_true' );

			if ( \yaydp_cart_discount_is_applied_first_rules() ) {
				break;
			}

			if ( \yaydp_cart_discount_is_applied_to_maximum_amount_per_order() ) {
				break;
			}

			if ( \yaydp_cart_discount_is_applied_to_minimum_amount_per_order() ) {
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
