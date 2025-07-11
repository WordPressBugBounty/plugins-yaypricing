<?php
/**
 * This class is responsible for managing the cart discount in the YAYDP system.
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\Core\Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Cart_Discount_Manager {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * This variable represents the combined adjustment value for a particular calculation
	 *
	 * @var object
	 */
	protected $combined_adjustment = null;

	/**
	 * This variable stores a combined coupon code string
	 *
	 * @var string
	 */
	public static $combined_coupon_code = 'yaydp_combined_coupon';

	/**
	 * This variable stores a combined coupon name string
	 *
	 * @var string
	 */
	public static $combined_coupon_name = 'Combined discount';

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_pricings' ), YAYDP_CART_CALCULATE_PRIORITY );
		add_action( 'yaydp_before_calculate_cart_discount', array( $this, 'before_calculate_pricings' ), 10 );
		add_action( 'yaydp_after_calculate_cart_discount', array( $this, 'after_calculate_pricings' ), 10 );

		add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'get_shop_coupon_data' ), 10, 3 );

		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'change_coupon_label' ), 100, 2 );
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'change_coupon_html' ), 100, 3 );
		add_filter( 'woocommerce_coupon_message', array( $this, 'change_coupon_message' ), 100, 3 );
		add_filter( 'woocommerce_coupon_error', array( $this, 'change_coupon_error' ), 100, 3 );

		// Handle use time.
		$this->handle_use_time();
	}

	/**
	 * Removes discounts generated by YayPricing from the cart.
	 *
	 * @deprecated 2.4.2
	 */
	public function remove_yaydp_discounts() {
		if ( ! empty( \WC()->cart ) ) {
			$applied_coupons = \WC()->cart->get_applied_coupons();
		} else {
			$applied_coupons = array();
		}
		add_filter( 'yaydp_prevent_recalculate_cart_discount', '__return_true' );
		foreach ( $applied_coupons as $coupon_code ) {
			if ( \yaydp_is_coupon( $coupon_code ) ) {
				\WC()->cart->remove_coupon( $coupon_code );
			}
		}
		remove_filter( 'yaydp_prevent_recalculate_cart_discount', '__return_true' );
	}

	/**
	 * This function is called before calculating the pricings for a cart
	 */
	public function before_calculate_pricings() {
		$this->remove_yaydp_discounts();
	}

	/**
	 * This function is called after calculating the pricings for a cart
	 */
	public function after_calculate_pricings() {
	}

	/**
	 * Calculate pricings
	 */
	public function calculate_pricings() {

		/**
		 * Check coupon single use only exists?
		 */
		if ( ! \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->can_use_together_with_single_use_coupon() ) {
			if ( ! empty( \WC()->cart ) ) {
				$applied_coupons = \WC()->cart->get_applied_coupons();
			} else {
				$applied_coupons = array();
			}

			foreach ( $applied_coupons as $coupon_code ) {
				$check_coupon = new \WC_Coupon( $coupon_code );
				if ( ! empty( $check_coupon ) && ( $check_coupon instanceof \WC_Coupon ) && $check_coupon->get_individual_use() ) {
					$this->remove_yaydp_discounts();
					return;
				}
			}
		}
		/** --- END --- */

		if ( \yaydp_has_cart_block() ) {
			if ( apply_filters( 'yaydp_prevent_recalculate_cart_discount', false ) ) {
				return;
			}
		} else {
			// remove_action( 'woocommerce_before_calculate_totals', array( self::get_instance(), 'calculate_pricings' ), YAYDP_CART_CALCULATE_PRIORITY );
		}

		static $has_run = false;
		if ( $has_run ) {
			return;
		}
		$has_run = true;

		do_action( 'yaydp_before_calculate_cart_discount' );

		global $yaydp_cart;
		if ( is_null( $yaydp_cart ) ) {
			$yaydp_cart                  = new \YAYDP\Core\YAYDP_Cart();
			$product_pricing_adjustments = new \YAYDP\Core\Adjustments\YAYDP_Product_Pricing_Adjustments( $yaydp_cart );
			$product_pricing_adjustments->do_stuff();
		}
		$cart_discount_adjustments = new \YAYDP\Core\Adjustments\YAYDP_Cart_Discount_Adjustments( $yaydp_cart );
		$cart_discount_adjustments->do_stuff();

		do_action( 'yaydp_after_calculate_cart_discount' );

	}

	/**
	 * Return coupon data
	 */
	public function get_shop_coupon_data( $coupon_data, $coupon_code ) {

		global $yaydp_cart;
		if ( is_null( $yaydp_cart ) ) {
			$yaydp_cart                  = new \YAYDP\Core\YAYDP_Cart();
			$product_pricing_adjustments = new \YAYDP\Core\Adjustments\YAYDP_Product_Pricing_Adjustments( $yaydp_cart );
			$product_pricing_adjustments->do_stuff();
		}
		$is_combined = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->is_combined();
		if ( $is_combined ) {
			if ( \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::is_match_coupon( $coupon_code ) ) {
				return \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::get_coupon_data( $yaydp_cart );
			}
		} else {
			$running_rules = \yaydp_get_running_cart_discount_rules();
			foreach ( $running_rules as $rule ) {
				if ( $rule->is_match_coupon( $coupon_code ) ) {
					return $rule->get_coupon_data( $yaydp_cart );
				}
			}
		}

		return $coupon_data;
	}

	/**
	 * Change coupon label
	 */
	public function change_coupon_label( $label, $coupon ) {
		$coupon_code = $coupon->get_code();

		if ( \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::is_match_coupon( $coupon_code ) ) {
			$label = \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::$coupon_name;
			return sprintf( __( '%s', 'yaypricing' ), $label );
		} else {
			$running_rules = \yaydp_get_running_cart_discount_rules();
			foreach ( $running_rules as $rule ) {
				if ( $rule->is_match_coupon( $coupon_code ) ) {
					$label = $rule->get_name();
					return esc_html__( $label, 'yaypricing' );
				}
			}
		}
		return $label;
	}

	/**
	 * Change coupon html
	 */
	public function change_coupon_html( $coupon_html, $coupon, $discount_amount_html ) {
		$coupon_code = $coupon->get_code();
		$result      = apply_filters( 'yaydp_checkout_coupon_fee_html', $discount_amount_html, $coupon_html );
		if ( \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::is_match_coupon( $coupon_code ) ) {
			$result .= \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::get_coupon_content();
			return $result;
		}
		$running_rules = \yaydp_get_running_cart_discount_rules();
		foreach ( $running_rules as $rule ) {
			if ( $rule->is_match_coupon( $coupon_code ) ) {
				$result .= $rule->get_coupon_content();
				return $result;
			}
		}
		return $coupon_html;
	}

	/**
	 * Change coupon message
	 */
	public function change_coupon_message( $message, $message_code, $coupon ) {
		if ( is_null( $coupon ) ) {
			return $message;
		}
		$coupon_code = $coupon->get_code();
		if ( \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::is_match_coupon( $coupon_code ) ) {
			return '';
		}
		$running_rules = \yaydp_get_running_cart_discount_rules();
		foreach ( $running_rules as $rule ) {
			if ( $rule->is_match_coupon( $coupon_code ) ) {
				return '';
			}
		}
		return $message;
	}

	/**
	 * Change coupon error message
	 */
	public function change_coupon_error( $message, $message_code, $coupon ) {
		if ( is_null( $coupon ) ) {
			return $message;
		}
		$coupon_code = $coupon->get_code();
		if ( \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::is_match_coupon( $coupon_code ) ) {
			return '';
		}
		$running_rules = \yaydp_get_running_cart_discount_rules();
		foreach ( $running_rules as $rule ) {
			if ( $rule->is_match_coupon( $coupon_code ) ) {
				return '';
			}
		}
		return $message;
	}

	/**
	 * Handle use time
	 */
	private function handle_use_time() {
		\YAYDP\Core\Use_Time\YAYDP_Cart_Discount_Use_Time::get_instance();
	}

}
