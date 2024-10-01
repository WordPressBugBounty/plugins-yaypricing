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
	 * Calculate the adjustment amount based on current cart
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_adjustment_amount( $cart ) {
		$pricing_type              = $this->get_pricing_type();
		$pricing_value             = $this->get_pricing_value();
		$maximum_adjustment_amount = $this->get_maximum_adjustment_amount();
		$cart_subtotal             = $cart->get_cart_subtotal();
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
		$cart_subtotal             = $cart->get_cart_subtotal();
		if ( \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
			return min( $cart_subtotal, $adjustment_amount );
		}
		if ( \yaydp_is_fixed_product_pricing_type( $pricing_type ) ) {
			return min( $cart_subtotal, min( $maximum_adjustment_amount, $adjustment_amount * $cart_total_quantity ) );
		}
		if ( \yaydp_is_fixed_pricing_type( $pricing_type ) ) {
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
		\WC()->cart->add_discount( $this->get_coupon_code() );
	}

	/**
	 * Display coupon content
	 */
	public function get_coupon_content() {
		$tooltips = array();
		$tooltip  = $this->get_tooltip();
		if ( ! $tooltip->is_enabled() ) {
			return '';
		}
		$tooltips[] = $tooltip;
		ob_start();
		\wc_get_template(
			'coupon/yaydp-cart-coupon.php',
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
