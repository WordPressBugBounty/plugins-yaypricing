<?php
/**
 * Groups free gift cart rows by the rule that granted them.
 *
 * Free gifts are published as one real cart item per product, but they are
 * presented as a single row per rule. Grouping happens at render time only:
 * cart contents and the resulting order lines are left untouched.
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

use YAYDP\Helper\YAYDP_Gift_Helper as Gift_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Group_Helper class
 */
class YAYDP_Gift_Group_Helper {

	/**
	 * Memoized groups, keyed by a signature of the current gift rows.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Signature the memoized groups were built from.
	 *
	 * @var string
	 */
	private static $cache_signature = '';

	/**
	 * Map of rule id => cart item keys, in cart order.
	 *
	 * @return array
	 */
	public static function get_groups() {
		if ( ! function_exists( 'WC' ) || empty( \WC()->cart ) ) {
			return array();
		}

		$cart      = \WC()->cart->get_cart();
		$signature = md5( implode( '|', array_keys( $cart ) ) );
		if ( null !== self::$cache && $signature === self::$cache_signature ) {
			return self::$cache;
		}

		$groups = array();
		foreach ( $cart as $cart_item_key => $cart_item ) {
			if ( ! Gift_Helper::is_gift_item( $cart_item ) ) {
				continue;
			}
			$rule = Gift_Helper::get_gift_rule( $cart_item );
			if ( is_null( $rule ) ) {
				continue;
			}
			$rule_id = $rule->get_rule_id();
			if ( ! isset( $groups[ $rule_id ] ) ) {
				$groups[ $rule_id ] = array();
			}
			$groups[ $rule_id ][] = $cart_item_key;
		}

		self::$cache           = $groups;
		self::$cache_signature = $signature;

		return $groups;
	}

	/**
	 * Cart item keys belonging to the same group as the given row.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @return string[]
	 */
	public static function get_group_keys( $cart_item_key ) {
		foreach ( self::get_groups() as $keys ) {
			if ( in_array( $cart_item_key, $keys, true ) ) {
				return $keys;
			}
		}
		return array();
	}

	/**
	 * Check whether a gift row is the one that renders its group.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @return bool
	 */
	public static function is_group_leader( $cart_item_key ) {
		$keys = self::get_group_keys( $cart_item_key );
		return ! empty( $keys ) && $keys[0] === $cart_item_key;
	}

	/**
	 * Sum of the quantities of every gift row in a group.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @return int
	 */
	public static function get_group_quantity( $cart_item_key ) {
		$total = 0;
		foreach ( self::get_group_keys( $cart_item_key ) as $key ) {
			$item = \WC()->cart->get_cart_item( $key );
			if ( ! empty( $item['quantity'] ) ) {
				$total += (int) $item['quantity'];
			}
		}
		return $total;
	}

	/**
	 * Gift quantities currently in the cart for a rule, keyed by product id.
	 *
	 * Used as the starting selection when the customer has not chosen anything
	 * yet: the gifts a rule adds by default are already real cart rows.
	 *
	 * @param string $rule_id Rule id.
	 * @return array Product id => quantity.
	 */
	public static function get_rule_quantities( $rule_id ) {
		$groups = self::get_groups();
		if ( empty( $groups[ $rule_id ] ) ) {
			return array();
		}

		$quantities = array();
		foreach ( $groups[ $rule_id ] as $key ) {
			$item = \WC()->cart->get_cart_item( $key );
			if ( empty( $item['data'] ) ) {
				continue;
			}
			$product_id = $item['data']->get_id();
			$quantity   = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
			if ( $quantity > 0 ) {
				$quantities[ $product_id ] = ( isset( $quantities[ $product_id ] ) ? $quantities[ $product_id ] : 0 ) + $quantity;
			}
		}

		return $quantities;
	}

	/**
	 * Products selected for a group, as {product, qty} pairs in cart order.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @return array
	 */
	public static function get_group_selections( $cart_item_key ) {
		$selections = array();
		foreach ( self::get_group_keys( $cart_item_key ) as $key ) {
			$item = \WC()->cart->get_cart_item( $key );
			if ( empty( $item['data'] ) ) {
				continue;
			}
			$selections[] = array(
				'product' => $item['data'],
				'qty'     => isset( $item['quantity'] ) ? (int) $item['quantity'] : 0,
			);
		}
		return $selections;
	}
}
