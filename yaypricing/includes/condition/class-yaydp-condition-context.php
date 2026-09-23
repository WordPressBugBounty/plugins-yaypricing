<?php
/**
 * Evaluation context handed to condition types.
 *
 * Immutable snapshot of "what is being evaluated": the YayPricing cart, the
 * cart items under consideration (a subset when combined conditions narrow
 * them), the rule owning the condition and its family. Customer/user/session
 * lookups go through here so types never touch globals directly.
 *
 * @package YayPricing\Condition
 * @since 3.5.8
 */

namespace YAYDP\Condition;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Condition_Context {

	/**
	 * YayPricing cart, when evaluating against one.
	 *
	 * @var \YAYDP\Core\YAYDP_Cart|null
	 */
	private $cart;

	/**
	 * Cart items (YAYDP_Cart_Item objects) under evaluation.
	 *
	 * @var array
	 */
	private $cart_items;

	/**
	 * Rule owning the conditions, when known.
	 *
	 * @var \YAYDP\Abstracts\YAYDP_Rule|null
	 */
	private $rule;

	private function __construct( $cart, array $cart_items, $rule ) {
		$this->cart       = $cart;
		$this->cart_items = $cart_items;
		$this->rule       = $rule;
	}

	/**
	 * Context for evaluating a rule against a whole cart.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart      $cart Cart.
	 * @param \YAYDP\Abstracts\YAYDP_Rule $rule Rule owning the conditions.
	 * @return self
	 */
	public static function from_rule( $cart, $rule ) {
		return new self( $cart, $cart->get_items(), $rule );
	}

	/**
	 * Context for an explicit item list (possibly empty), optionally with the
	 * rule and cart that produced it.
	 *
	 * @param array                            $cart_items Cart items.
	 * @param \YAYDP\Abstracts\YAYDP_Rule|null $rule       Rule, if any.
	 * @param \YAYDP\Core\YAYDP_Cart|null      $cart       Cart, if any.
	 * @return self
	 */
	public static function from_items( array $cart_items, $rule = null, $cart = null ) {
		return new self( $cart, $cart_items, $rule );
	}

	/**
	 * Same cart/rule, different item subset (combined conditions).
	 *
	 * @param array $cart_items Narrowed cart items.
	 * @return self
	 */
	public function with_items( array $cart_items ) {
		return new self( $this->cart, $cart_items, $this->rule );
	}

	/**
	 * YayPricing cart, or null.
	 *
	 * @return \YAYDP\Core\YAYDP_Cart|null
	 */
	public function cart() {
		return $this->cart;
	}

	/**
	 * Cart items under evaluation.
	 *
	 * @return array
	 */
	public function cart_items() {
		return $this->cart_items;
	}

	/**
	 * Owning rule, or null.
	 *
	 * @return \YAYDP\Abstracts\YAYDP_Rule|null
	 */
	public function rule() {
		return $this->rule;
	}

	/**
	 * Family of the owning rule: product_pricing | cart_discount | checkout_fee
	 * | exclude, or null when evaluating without a rule.
	 *
	 * @return string|null
	 */
	public function family() {
		if ( is_null( $this->rule ) ) {
			return null;
		}
		if ( \yaydp_is_checkout_fee( $this->rule ) ) {
			return 'checkout_fee';
		}
		if ( \yaydp_is_cart_discount( $this->rule ) ) {
			return 'cart_discount';
		}
		if ( $this->rule instanceof \YAYDP\Abstracts\YAYDP_Exclude_Rule ) {
			return 'exclude';
		}
		return 'product_pricing';
	}

	/**
	 * Whether a user is logged in.
	 *
	 * @return bool
	 */
	public function is_logged_in() {
		return \is_user_logged_in();
	}

	/**
	 * Current WordPress user (id 0 when logged out).
	 *
	 * @return \WP_User
	 */
	public function user() {
		return \wp_get_current_user();
	}

	/**
	 * Current user id (0 when logged out).
	 *
	 * @return int
	 */
	public function user_id() {
		return \get_current_user_id();
	}

	/**
	 * Raw (unslashed, unsanitised) checkout form value posted with the current
	 * request, or null. Callers sanitise for their own use.
	 *
	 * @param string $key Form field name.
	 * @return mixed
	 */
	public function posted( $key ) {
		// Read-only peek at checkout form data; callers sanitise.
		return isset( $_POST[ $key ] ) ? \wp_unslash( $_POST[ $key ] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Billing email of the current order: the session customer's, else the
	 * logged-in user's saved billing email, else their account email.
	 *
	 * @return string Sanitised email or ''.
	 */
	public function billing_email() {
		$customer = $this->customer();
		$email    = $customer ? $customer->get_billing_email() : '';
		if ( empty( $email ) && $this->is_logged_in() ) {
			$email = get_user_meta( $this->user_id(), 'billing_email', true );
			if ( empty( $email ) ) {
				$email = $this->user()->user_email;
			}
		}
		return \sanitize_email( $email );
	}

	/**
	 * Continent code WooCommerce assigns to a country code ('' when unknown).
	 *
	 * @param string $country_code ISO country code.
	 * @return string
	 */
	public function continent_code( $country_code ) {
		return ( function_exists( 'WC' ) && \WC()->countries ) ? (string) \WC()->countries->get_continent_code_for_country( $country_code ) : '';
	}

	/**
	 * Id of the first enabled payment gateway in WooCommerce order, or null.
	 *
	 * @return string|null
	 */
	public function first_enabled_gateway_id() {
		if ( ! function_exists( 'WC' ) || ! \WC()->payment_gateways() ) {
			return null;
		}
		foreach ( \WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				return $gateway->id;
			}
		}
		return null;
	}

	/**
	 * WooCommerce customer for the current session, or null outside a session.
	 *
	 * @return \WC_Customer|null
	 */
	public function customer() {
		return ( function_exists( 'WC' ) && ! empty( \WC()->customer ) ) ? \WC()->customer : null;
	}

	/**
	 * WooCommerce session, or null when none is loaded.
	 *
	 * @return \WC_Session|null
	 */
	public function session() {
		return ( function_exists( 'WC' ) && ! empty( \WC()->session ) ) ? \WC()->session : null;
	}

	/**
	 * Live WooCommerce cart, or null when none is loaded.
	 *
	 * @return \WC_Cart|null
	 */
	public function wc_cart() {
		return ( function_exists( 'WC' ) && ! empty( \WC()->cart ) ) ? \WC()->cart : null;
	}

	/**
	 * Coupon codes applied to the live WooCommerce cart.
	 *
	 * @return string[]
	 */
	public function applied_coupons() {
		$wc_cart = $this->wc_cart();
		return $wc_cart ? $wc_cart->get_applied_coupons() : array();
	}
}
