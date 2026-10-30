<?php
/**
 * Picks the cart line each offer's gift panel renders under.
 *
 * Rules claim lines in the order of their first matching line, ties going to
 * rule priority. Each rule anchors on its first matching line no other rule
 * claimed; a rule with no free line stacks under its first matching line.
 * Bundle and composite children resolve to their top-level parent.
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Offer_Anchor_Builder class
 */
class YAYDP_Gift_Offer_Anchor_Builder {

	/**
	 * Anchor groups in cart order, orphans last.
	 *
	 * @param array    $rule_keys Rule id => cart keys.
	 * @param string[] $cart_order Cart keys in cart order.
	 * @param array    $parents Child key => parent key.
	 * @param array    $priority Rule id => index (lower wins).
	 * @return array List of array( 'anchor' => string, 'rule_ids' => string[] ).
	 *               Rules with no line on screen share one group with an empty anchor.
	 */
	public static function build( $rule_keys, $cart_order, $parents, $priority ) {
		$positions = array_flip( $cart_order );

		$lines = array();
		foreach ( $rule_keys as $rule_id => $keys ) {
			$roots = array();
			foreach ( $keys as $key ) {
				$root = self::resolve_root( $key, $parents );
				if ( isset( $positions[ $root ] ) ) {
					$roots[ $root ] = $positions[ $root ];
				}
			}
			asort( $roots );
			$lines[ (string) $rule_id ] = array_keys( $roots );
		}

		// Claim order: first matching line, then priority, then original order.
		$order = array();
		$index = 0;
		foreach ( $lines as $rule_id => $keys ) {
			$order[] = array(
				'rule_id'  => (string) $rule_id,
				'position' => empty( $keys ) ? PHP_INT_MAX : $positions[ $keys[0] ],
				'priority' => isset( $priority[ $rule_id ] ) ? (int) $priority[ $rule_id ] : PHP_INT_MAX,
				'index'    => $index++,
			);
		}
		usort( $order, array( __CLASS__, 'compare' ) );

		$claimed = array();
		$anchors = array();
		$orphans = array();
		foreach ( $order as $entry ) {
			$rule_id = $entry['rule_id'];
			$keys    = $lines[ $rule_id ];
			if ( empty( $keys ) ) {
				$orphans[] = $rule_id;
				continue;
			}
			$anchor = $keys[0];
			foreach ( $keys as $key ) {
				if ( ! isset( $claimed[ $key ] ) ) {
					$anchor = $key;
					break;
				}
			}
			$claimed[ $anchor ]   = true;
			$anchors[ $anchor ][] = $rule_id;
		}

		$groups = array();
		foreach ( $cart_order as $key ) {
			if ( isset( $anchors[ $key ] ) ) {
				$groups[] = array(
					'anchor'   => $key,
					'rule_ids' => $anchors[ $key ],
				);
			}
		}
		if ( ! empty( $orphans ) ) {
			$groups[] = array(
				'anchor'   => '',
				'rule_ids' => $orphans,
			);
		}
		return $groups;
	}

	/**
	 * Top-level parent of a cart key (the key itself when it has none).
	 *
	 * @param string $key Cart item key.
	 * @param array  $parents Child key => parent key.
	 * @return string
	 */
	public static function resolve_root( $key, $parents ) {
		$seen = array();
		while ( isset( $parents[ $key ] ) && ! isset( $seen[ $key ] ) ) {
			$seen[ $key ] = true;
			$key          = $parents[ $key ];
		}
		return (string) $key;
	}

	/**
	 * Sort by position, then priority, then original index (usort is not
	 * stable before PHP 8).
	 *
	 * @param array $a Entry.
	 * @param array $b Entry.
	 * @return int
	 */
	private static function compare( $a, $b ) {
		foreach ( array( 'position', 'priority', 'index' ) as $field ) {
			if ( $a[ $field ] !== $b[ $field ] ) {
				return $a[ $field ] < $b[ $field ] ? -1 : 1;
			}
		}
		return 0;
	}
}
