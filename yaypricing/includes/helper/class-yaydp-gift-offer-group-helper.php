<?php
/**
 * Groups the cart lines that earn a free gift with the offer that grants it.
 *
 * Every rule that owns gift rows is mapped to the cart lines it bought on and
 * given an anchor line
 * its panel renders under (see YAYDP_Gift_Offer_Anchor_Builder). Anchors are
 * a render-time view only: the cart itself is left untouched.
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

use YAYDP\Helper\YAYDP_Gift_Helper as Gift_Helper;
use YAYDP\Helper\YAYDP_Gift_Group_Helper as Gift_Group;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Offer_Group_Helper class
 */
class YAYDP_Gift_Offer_Group_Helper {

	/**
	 * Memoized anchors, keyed by the cart signature.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Signature the memoized data was built from.
	 *
	 * @var string
	 */
	private static $cache_signature = '';

	/**
	 * Offer panels grouped by the line they render under, in cart order.
	 *
	 * @return array List of array( 'anchor' => string, 'rule_ids' => string[] ).
	 *               Rules none of whose lines resolve share a last group whose
	 *               anchor is ''.
	 */
	public static function get_anchors() {
		$data = self::get_data();
		return $data['anchors'];
	}

	/**
	 * Parent row of a bundle or composite child, if any.
	 *
	 * @param array $cart_item Cart item.
	 * @return string
	 */
	public static function get_parent_key( $cart_item ) {
		foreach ( array( 'bundled_by', 'composite_parent', 'woosb_parent_key' ) as $field ) {
			if ( ! empty( $cart_item[ $field ] ) && is_string( $cart_item[ $field ] ) ) {
				return $cart_item[ $field ];
			}
		}
		return '';
	}

	/**
	 * Build (or reuse) the anchors for the current cart.
	 *
	 * @return array
	 */
	private static function get_data() {
		$empty = array( 'anchors' => array() );
		if ( ! function_exists( 'WC' ) || empty( \WC()->cart ) ) {
			return $empty;
		}

		$signature = self::get_signature();
		if ( null !== self::$cache && $signature === self::$cache_signature ) {
			return self::$cache;
		}

		$data      = $empty;
		$rule_keys = array();
		foreach ( self::get_offer_rules() as $rule_id => $rule ) {
			$rule_keys[ $rule_id ] = self::get_qualifying_keys( Gift_Helper::build_adjustment( $rule ) );
		}
		$cart    = \WC()->cart->get_cart();
		$parents = array();
		foreach ( $cart as $key => $cart_item ) {
			$parent = self::get_parent_key( $cart_item );
			if ( '' !== $parent && isset( $cart[ $parent ] ) ) {
				$parents[ $key ] = $parent;
			}
		}
		$data['anchors'] = YAYDP_Gift_Offer_Anchor_Builder::build( $rule_keys, array_keys( $cart ), $parents, self::get_priority() );

		self::$cache           = $data;
		self::$cache_signature = $signature;

		return $data;
	}

	/**
	 * Cart keys, in cart order, and quantities (quantities change what each
	 * rule bought; order changes which line anchors a panel).
	 *
	 * @return string
	 */
	private static function get_signature() {
		$quantities = array();
		foreach ( \WC()->cart->get_cart() as $key => $cart_item ) {
			$quantities[ $key ] = isset( $cart_item['quantity'] ) ? (float) $cart_item['quantity'] : 0;
		}
		return md5( wp_json_encode( $quantities ) );
	}

	/**
	 * Rule id => position among the running product pricing rules.
	 *
	 * @return array
	 */
	private static function get_priority() {
		$priority = array();
		foreach ( array_values( \yaydp_get_running_product_pricing_rules() ) as $index => $rule ) {
			$priority[ (string) $rule->get_rule_id() ] = $index;
		}
		return $priority;
	}

	/**
	 * Rules that own gift rows in the cart.
	 *
	 * @return array Rule id => rule.
	 */
	private static function get_offer_rules() {
		$rules = array();
		foreach ( array_keys( Gift_Group::get_groups() ) as $rule_id ) {
			$rule = \yaydp_get_rule( $rule_id );
			if ( ! is_null( $rule ) ) {
				$rules[ (string) $rule_id ] = $rule;
			}
		}
		return $rules;
	}

	/**
	 * Cart keys of every line a rule bought on, surplus included, gifts excluded.
	 *
	 * @param mixed $adjustment Adjustment or null.
	 * @return string[]
	 */
	private static function get_qualifying_keys( $adjustment ) {
		if ( is_null( $adjustment ) ) {
			return array();
		}
		$keys = array();
		foreach ( (array) $adjustment->get_bought_cases() as $bought_case ) {
			foreach ( self::get_case_items( $bought_case ) as $item ) {
				if ( ! $item instanceof \YAYDP\Core\YAYDP_Cart_Item || $item->is_extra() ) {
					continue;
				}
				$key = $item->get_key();
				if ( ! empty( $key ) ) {
					$keys[ (string) $key ] = true;
				}
			}
		}
		return array_keys( $keys );
	}

	/**
	 * Cart items of one bought case, whatever the rule type's case shape:
	 * BOGO returns array( 'quantity', 'item' ); Buy X Get Y returns a list of
	 * case lines, each carrying `items` (see YAYDP_Helper::map_cases()).
	 *
	 * @param mixed $bought_case Bought case.
	 * @return array
	 */
	private static function get_case_items( $bought_case ) {
		if ( ! is_array( $bought_case ) ) {
			return array();
		}
		if ( isset( $bought_case['item'] ) ) {
			return array( $bought_case['item'] );
		}
		$items = array();
		foreach ( $bought_case as $case_line ) {
			if ( is_array( $case_line ) && ! empty( $case_line['items'] ) && is_array( $case_line['items'] ) ) {
				$items = array_merge( $items, array_values( $case_line['items'] ) );
			}
		}
		return $items;
	}
}
