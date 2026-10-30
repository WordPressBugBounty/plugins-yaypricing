<?php
/**
 * YayPricing functions for cart discount
 *
 * Declare global functions
 *
 * @package YayPricing\Functions
 * @since 2.4
 */

if ( ! function_exists( 'yaydp_get_cart_discount_rules' ) ) {
	/**
	 * Get all cart discount rules
	 */
	function yaydp_get_cart_discount_rules() {
		$database_data = get_option( 'yaydp_cart_discount_rules' );
		// Pro-only stored rules resolve to null at the factory; drop them here so no
		// consumer ever sees a null rule. The option itself is never rewritten.
		return array_filter(
			array_map(
				function( $data ) {
					return \YAYDP\Factory\YAYDP_Cart_Discount_Rule_Factory::get_rule( $data );
				},
				empty( $database_data ) ? array() : $database_data
			)
		);
	}
}

if ( ! function_exists( 'yaydp_get_running_cart_discount_rules' ) ) {
	/**
	 * Get all running cart discount rules
	 */
	function yaydp_get_running_cart_discount_rules() {
		$rules = \yaydp_get_cart_discount_rules();
		return array_filter(
			$rules,
			function ( $rule ) {
				return $rule->is_running();
			}
		);
	}
}

if ( ! function_exists( 'yaydp_cart_discount_is_applied_all_rules' ) ) {
	/**
	 * Check whether how to apply settings is "apply all applicable rules"
	 */
	function yaydp_cart_discount_is_applied_all_rules() {
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		return 'all' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_cart_discount_is_applied_first_rules' ) ) {
	/**
	 * Check whether how to apply settings is "apply first applicable rule"
	 */
	function yaydp_cart_discount_is_applied_first_rules() {
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		return 'first' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_cart_discount_is_applied_to_minimum_amount_per_order' ) ) {
	/**
	 * Check whether how to apply settings is "apply minimum"
	 */
	function yaydp_cart_discount_is_applied_to_minimum_amount_per_order() {
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		return 'smallest_amount' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_cart_discount_is_applied_to_maximum_amount_per_order' ) ) {
	/**
	 * Check whether how to apply settings is "apply maximum"
	 */
	function yaydp_cart_discount_is_applied_to_maximum_amount_per_order() {
		$settings = \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance();
		return 'highest_amount' === $settings->get_how_to_apply();
	}
}

if ( ! function_exists( 'yaydp_match_cart_discount_rules' ) ) {
	/**
	 * Rules from the given list whose coupon code matches.
	 *
	 * Internal helper for the resolver; call yaydp_resolve_coupon_rules instead.
	 *
	 * @param array  $rules Cart discount rules.
	 * @param string $code  Coupon code.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	function yaydp_match_cart_discount_rules( $rules, $code ) {
		$matches = array();
		foreach ( $rules as $rule ) {
			if ( $rule->is_match_coupon( $code ) ) {
				$matches[] = $rule;
			}
		}
		return $matches;
	}
}

if ( ! function_exists( 'yaydp_resolve_coupon_rules' ) ) {
	/**
	 * Resolve a coupon code to the cart discount rules that own it.
	 *
	 * The single home for coupon precedence, replacing the block that was
	 * repeated at every call site. The two arguments exist because those call
	 * sites never agreed on precedence in the first place, and this refactor
	 * preserves each one exactly:
	 *
	 * - `combined`: 'first' checks the combined discount before the rules and
	 *   stops there on a match; 'only' checks it and nothing else; 'skip'
	 *   ignores it. `get_shop_coupon_data()` is the only caller that ties this
	 *   to the is_combined setting, and the use time handler is the only one
	 *   that never considers the combined discount at all.
	 * - `include_inactive`: when no rule is running, fall back to matching
	 *   against every rule. `yaydp_is_coupon()` and
	 *   `YAYDP_Cart::get_coupon_rules()` do this; the manager methods do not.
	 *
	 * More than one rule can match a single code, because nothing stops two
	 * rules sharing a name. Callers that act on one rule take the first;
	 * callers that record every applied rule take the whole list.
	 *
	 * @param string $code Coupon code.
	 * @param array  $args {
	 *     Optional.
	 *
	 *     @type string $combined         'first' ( default ), 'only' or 'skip'.
	 *     @type bool   $include_inactive Default false.
	 * }
	 *
	 * @since 3.6.0
	 *
	 * @return array List of matching rules, or array( 'combined' ), or empty when the code is not ours.
	 */
	function yaydp_resolve_coupon_rules( $code, $args = array() ) {
		$args = array_merge(
			array(
				'combined'         => 'first',
				'include_inactive' => false,
			),
			$args
		);

		$matches = \yaydp_match_coupon_by_precedence( $code, $args );
		if ( empty( $matches ) || \yaydp_coupon_code_is_claimed( $code ) ) {
			return array();
		}
		return $matches;
	}
}

if ( ! function_exists( 'yaydp_match_coupon_by_precedence' ) ) {
	/**
	 * The precedence half of the resolver, without the collision guard.
	 *
	 * Internal helper; call yaydp_resolve_coupon_rules instead.
	 *
	 * @param string $code Coupon code.
	 * @param array  $args Resolved arguments, see yaydp_resolve_coupon_rules.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	function yaydp_match_coupon_by_precedence( $code, $args ) {
		if ( 'skip' !== $args['combined'] && \YAYDP\Core\Rule\Cart_Discount\YAYDP_Combined_Discount::is_match_coupon( $code ) ) {
			return array( 'combined' );
		}
		if ( 'only' === $args['combined'] ) {
			return array();
		}

		$running_rules = \yaydp_get_running_cart_discount_rules();
		$matches       = \yaydp_match_cart_discount_rules( $running_rules, $code );
		if ( ! empty( $matches ) ) {
			return $matches;
		}

		if ( $args['include_inactive'] && empty( $running_rules ) ) {
			return \yaydp_match_cart_discount_rules( \yaydp_get_cart_discount_rules(), $code );
		}

		return array();
	}
}

if ( ! function_exists( 'yaydp_coupon_code_is_claimed' ) ) {
	/**
	 * Does a real coupon in the store already own this code?
	 *
	 * WC_Coupon applies woocommerce_get_shop_coupon_data before it looks the code
	 * up, so a virtual coupon answering to a merchant's own code would shadow it
	 * and remove_yaydp_discounts() would strip it from the cart on every
	 * recalculation. The merchant's coupon wins.
	 *
	 * Only when our codes are merchant authored names. With use_id_as_code the
	 * codes are plugin generated tokens, which have never been guarded.
	 *
	 * @param string $code Coupon code.
	 *
	 * @since 3.6.0
	 *
	 * @return bool
	 */
	function yaydp_coupon_code_is_claimed( $code ) {
		if ( \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->use_id_as_code() ) {
			return false;
		}
		return (bool) wc_get_coupon_id_by_code( wc_format_coupon_code( $code ) );
	}
}

if ( ! function_exists( 'yaydp_resolve_coupon_rule' ) ) {
	/**
	 * The single rule a coupon code resolves to, for callers that act on one.
	 *
	 * @param string $code Coupon code.
	 * @param array  $args See yaydp_resolve_coupon_rules.
	 *
	 * @since 3.6.0
	 *
	 * @return string|\YAYDP\Abstracts\YAYDP_Cart_Discount_Rule|null 'combined', the owning rule, or null.
	 */
	function yaydp_resolve_coupon_rule( $code, $args = array() ) {
		$rules = \yaydp_resolve_coupon_rules( $code, $args );
		return empty( $rules ) ? null : reset( $rules );
	}
}

if ( ! function_exists( 'yaydp_is_coupon' ) ) {
	/**
	 * Check whether coupon code is cart discount coupon
	 * 
	 * @since 3.4.2
	 */
	function yaydp_is_coupon( $code ) {
		return null !== \yaydp_resolve_coupon_rule( $code, array( 'include_inactive' => true ) );
	}
}
