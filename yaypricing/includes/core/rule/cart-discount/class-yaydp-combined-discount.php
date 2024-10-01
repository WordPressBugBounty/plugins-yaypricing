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
	 * Define combined coupon name
	 *
	 * @var string
	 */
	public static $coupon_name = 'Combined discount';

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
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		if ( $settings->use_id_as_code() ) {
			$coupon = self::$coupon_code;
		} else {
			$coupon = sprintf( __( '%s', 'yaypricing' ), self::$coupon_name );
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
			$rule    = $adjustment->get_rule();
			$tooltip = $rule->get_tooltip();
			if ( ! $tooltip->is_enabled() ) {
				continue;
			}
			$tooltips[] = $tooltip;
		}
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
	 * Is given coupon code match with this rule.
	 *
	 * @param string $code Coupon code.
	 */
	public static function is_match_coupon( $code ) {
		if ( \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->use_id_as_code() ) {
			return self::$coupon_code === $code;
		}
		return strtolower( self::$coupon_name ) === $code;
	}
}
