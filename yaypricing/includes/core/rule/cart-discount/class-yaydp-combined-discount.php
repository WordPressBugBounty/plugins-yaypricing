<?php
/**
 * Handle Combined discount
 *
 * @package YayPricing\Rule\CartDiscount
 */

namespace YAYDP\Core\Rule\Cart_Discount;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Combined_Discount {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {

	}

	/**
	 * Define combined coupon code
	 *
	 * @var string
	 */
	public static $coupon_code = 'yaydp_combined_coupon';

	/**
	 * Fallback name, used when the merchant has not set a custom one.
	 *
	 * @var string
	 */
	const DEFAULT_COUPON_NAME = 'Combined discount';

	/**
	 * Raw merchant-facing name. Doubles as the coupon code when the discount id
	 * is not used as the code.
	 *
	 * Deliberately untranslated: the code must be identical in every language,
	 * otherwise `is_match_coupon` stops recognising the coupon `add_coupon`
	 * created. Use `get_translated_coupon_name` for anything shown to a user.
	 *
	 * @return string
	 */
	public static function get_coupon_name() {
		$name = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->get_combined_name();
		$name = sanitize_text_field( $name );
		return '' === $name ? self::DEFAULT_COUPON_NAME : $name;
	}

	/**
	 * Display name, translated through WPML/Polylang/Loco when one is active.
	 *
	 * Display only. Must never reach the coupon code.
	 *
	 * @return string
	 */
	public static function get_translated_coupon_name() {
		$name = self::get_coupon_name();

		/*
		 * The default name ships in the .pot, and WPML/Polylang never register it
		 * because there is no merchant-entered string to register. Translate it
		 * through the text domain so existing .mo translations keep working.
		 */
		if ( self::DEFAULT_COUPON_NAME === $name ) {
			return __( 'Combined discount', 'yaypricing' );
		}

		return \YAYDP\Helper\YAYDP_Helper::translate_user_string(
			$name,
			'yaypricing',
			'combined_discount_name'
		);
	}

	/**
	 * Returns coupon data
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public static function get_coupon_data( $cart ) {
		$adjustments           = self::get_adjustments_from_cart( $cart );
		$total_discount_amount = 0;
		foreach ( $adjustments->get_adjustments() as $adjustment ) {
			$total_discount_amount += $adjustment->get_total_discount_amount_per_order();
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
		if ( empty( $total_discount_amount ) ) {
			return false;
		}
		return array(
			'discount_type'              => 'fixed_cart',
			'amount'                     => $total_discount_amount,
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
	 * Get all cart discount adjustments from cart
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	private static function get_adjustments_from_cart( $cart ) {
		$adjustments = new \YAYDP\Core\Adjustments\YAYDP_Cart_Discount_Adjustments( $cart );
		$adjustments->collect();
		return $adjustments;
	}

	/**
	 * Handle add coupon
	 */
	public static function add_coupon() {
		if ( ! function_exists( 'WC' ) || empty( \WC()->cart ) ) {
			return;
		}
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		if ( $settings->use_id_as_code() ) {
			$coupon = self::$coupon_code;
		} else {
			$coupon = self::get_coupon_name();
		}

		// Not ours to apply; see YAYDP_Simple_Discount::add_coupon().
		if ( \yaydp_coupon_code_is_claimed( $coupon ) ) {
			return;
		}
		\WC()->cart->add_discount( $coupon );
	}

	/**
	 * Display coupon content
	 */
	public static function get_coupon_content() {
		$cart        = new \YAYDP\Core\YAYDP_Cart();
		$adjustments = new \YAYDP\Core\Adjustments\YAYDP_Cart_Discount_Adjustments( $cart );
		$adjustments->collect();
		$tooltips = array();
		foreach ( $adjustments->get_adjustments() as $adjustment ) {
			$tooltips[] = $adjustment->get_rule()->get_tooltip();
		}
		return \yaydp_render_tooltips( $tooltips );
	}

	/**
	 * Is given coupon code match with this rule.
	 *
	 * @param string $code Coupon code.
	 */
	public static function is_match_coupon( $code ) {
		if ( \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->use_id_as_code() ) {
			return self::$coupon_code === $code;
		}
		// Raw name, never the translated one: the code must not vary by language.
		return wc_format_coupon_code( self::get_coupon_name() ) === wc_format_coupon_code( $code );
	}
}
