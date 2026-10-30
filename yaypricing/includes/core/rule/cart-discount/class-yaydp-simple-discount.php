<?php
/**
 * Handle Simple Discount rule
 *
 * @package YayPricing\Rule\CartDiscount
 */

namespace YAYDP\Core\Rule\Cart_Discount;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Simple_Discount extends \YAYDP\Abstracts\YAYDP_Cart_Discount_Rule {

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
	 * Cart subtotal the discount is calculated from.
	 *
	 * Currency plugins can return the subtotal the customer actually sees, so that a percentage
	 * discount matches the displayed prices after their conversion and rounding.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 *
	 * @return float
	 */
	protected function get_discount_base_subtotal( $cart ) {
		return apply_filters( 'yaydp_cart_discount_subtotal', $cart->get_cart_subtotal(), $cart );
	}

	/**
	 * Calculate the adjustment amount based on current cart
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_adjustment_amount( $cart ) {
		$pricing_type              = $this->get_pricing_type();
		$pricing_value             = $this->get_pricing_value();
		$maximum_adjustment_amount = $this->get_maximum_adjustment_amount();
		$cart_subtotal             = $this->get_discount_base_subtotal( $cart );
		$adjustment_amount         = \YAYDP\Helper\YAYDP_Pricing_Helper::calculate_adjustment_amount( $cart_subtotal, $pricing_type, $pricing_value, $maximum_adjustment_amount );
		return $adjustment_amount;
	}

	/**
	 * Calculate the total discount amount
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_total_discount_amount( $cart ) {
		$adjustment_amount         = $this->get_adjustment_amount( $cart );
		$pricing_type              = $this->get_pricing_type();
		$maximum_adjustment_amount = $this->get_maximum_adjustment_amount();
		$cart_total_quantity       = $cart->get_cart_quantity();
		$cart_subtotal             = $this->get_discount_base_subtotal( $cart );
		if ( \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_percentage_adjustment( $pricing_type ) ) {
			return min( $cart_subtotal, $adjustment_amount );
		}
		if ( 'fixed_product' === $pricing_type ) {
			return min( $cart_subtotal, min( $maximum_adjustment_amount, $adjustment_amount * $cart_total_quantity ) );
		}
		if ( \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_money_amount( $pricing_type ) ) {
			return min( $cart_subtotal, $adjustment_amount );
		}
		return 0;
	}

	/**
	 * Returns coupon data
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_coupon_data( $cart ) {
		$discount_type   = 'fixed_cart';
		$discount_amount = $this->get_total_discount_amount( $cart );

		if ( empty( $discount_amount ) ) {
			return false;
		}

		return array(
			'discount_type'              => $discount_type,
			'amount'                     => $discount_amount,
			'expiry_date'                => '',
			'individual_use'             => false,
			'product_ids'                => array(),
			'exclude_product_ids'        => array(),
			'usage_limit'                => '',
			'usage_limit_per_user'       => '',
			'limit_usage_to_x_items'     => '',
			'usage_count'                => '',
			'free_shipping'              => false,
			'product_categories'         => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items'         => false,
			'minimum_amount'             => '',
			'maximum_amount'             => '',
			'customer_email'             => array(),
		);
	}

	/**
	 * Handle add coupon
	 */
	public function add_coupon() {
		if ( ! function_exists( 'WC' ) || empty( \WC()->cart ) ) {
			return;
		}
		$code = $this->get_coupon_code();

		/*
		 * A real coupon owns this code, so it is not ours to apply. Asking
		 * WooCommerce to apply it again on every recalculation only produces a
		 * "coupon already applied" notice for the customer.
		 */
		if ( \yaydp_coupon_code_is_claimed( $code ) ) {
			return;
		}
		\WC()->cart->add_discount( $code );
	}

	/**
	 * Display coupon content
	 */
	public function get_coupon_content() {
		return \yaydp_render_tooltips( array( $this->get_tooltip() ) );
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
		return new \YAYDP\Core\Encouragement\YAYDP_Cart_Discount_Encouragement(
			array(
				'cart'                      => $cart,
				'rule'                      => $this,
				'conditions_encouragements' => $conditions_encouragements,
			)
		);
	}
}
