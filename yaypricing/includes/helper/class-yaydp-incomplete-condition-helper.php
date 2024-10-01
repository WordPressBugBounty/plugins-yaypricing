<?php
/**
 * YayPricing incomplete condition helper
 *
 * @package YayPricing\Helper
 * @since 2.4
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Incomplete_Condition_Helper {

	/**
	 * Get incomplete conditions
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 *
	 * @return bool
	 */
	public static function get_incomplete_conditions( $cart, $rule ) {
		$conditions                    = $rule->get_conditions();
		$cart_items                    = $cart->get_items();
		$result                        = array();
		$remaining_checking_conditions = array();
		foreach ( $conditions as $condition ) {
			switch ( $condition['type'] ) {
				case 'cart_subtotal_price':
					$sub = self::get_incomplete_cart_subtotal_price( $cart_items, $condition );
					if ( ! empty( $sub ) ) {
						$result[] = $sub;
					}
					break;
				case 'cart_quantity':
					$sub = self::get_incomplete_cart_quantity( $cart_items, $condition );
					if ( ! empty( $sub ) ) {
						$result[] = $sub;
					}
					break;
				case 'logged_customer':
					$check = \YAYDP\Helper\YAYDP_Condition_Helper::check_logged_customer( $condition );
					if ( ! $check ) {
						$result[] = array(
							'type'          => 'logged_customer',
							'missing_value' => false,
						);
					}
					break;
				case 'shipping_total':
					$sub = self::get_incomplete_shipping_total( $condition );
					if ( ! empty( $sub ) ) {
						$result[] = $sub;
					}
					break;
				case 'cart_total_weight':
					$sub = self::get_incomplete_cart_total_weight( $condition );
					if ( ! empty( $sub ) ) {
						$result[] = $sub;
					}
					break;
				default:
					$remaining_checking_conditions[] = $condition;
					break;
			}
		}
		if ( ! YAYDP_Condition_Helper::check_list_conditions( $remaining_checking_conditions, $rule->get_condition_match_type(), $cart_items ) ) {
			return array();
		}
		self::sort_incomplete_conditions( $result );
		return $result;
	}

	/**
	 * Get the incomplete subtotal
	 *
	 * @param array $cart_items Cart.
	 * @param array $condition Condition.
	 */
	public static function get_incomplete_cart_subtotal_price( $cart_items, $condition ) {
		$subtotal = 0;
		foreach ( $cart_items as $cart_item ) {

			$cart_item_quantity = $cart_item->get_quantity();
			$cart_item_price    = $cart_item->get_price();
			$subtotal          += $cart_item_quantity * $cart_item_price;

		}

		$check = \yaydp_compare_numeric( $subtotal, $condition['value'], $condition['comparation'] );

		if ( $check ) {
			return null;
		}

		$is_greater_than = \yaydp_is_greater_than_comparison( $condition['comparation'] );
		$is_gte          = \yaydp_is_gte_comparison( $condition['comparation'] );

		if ( ! $is_greater_than && ! $is_gte ) {
			return null;
		}

		$sub = $condition['value'] - $subtotal;

		return array(
			'type'          => 'cart_subtotal',
			'missing_value' => $sub + 1,
		);
	}

	/**
	 * Get the incomplete cart quantity
	 *
	 * @param array $cart_items Cart.
	 * @param array $condition Condition.
	 */
	public static function get_incomplete_cart_quantity( $cart_items, $condition ) {
		$cart_item_quantity = 0;
		foreach ( $cart_items as $cart_item ) {
			$cart_item_quantity += $cart_item->get_quantity();
		}
		$check = \yaydp_compare_numeric( $cart_item_quantity, $condition['value'], $condition['comparation'] );

		if ( $check ) {
			return null;
		}

		$is_greater_than = \yaydp_is_greater_than_comparison( $condition['comparation'] );
		$is_gte          = \yaydp_is_gte_comparison( $condition['comparation'] );

		if ( ! $is_greater_than && ! $is_gte ) {
			return null;
		}

		$sub = $condition['value'] - $cart_item_quantity;

		return array(
			'type'          => 'cart_quantity',
			'missing_value' => $sub + 1,
		);
	}

	/**
	 * Get the incomplete customer order count
	 *
	 * @param array $condition Condition.
	 */
	public static function get_incomplete_customer_order_count( $condition ) {
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id();
			$order_count     = \wc_get_customer_order_count( $current_user_id );
			$check           = \yaydp_compare_numeric( $order_count, $condition['value'], $condition['comparation'] );
			if ( $check ) {
				return null;
			}

			$is_greater_than = \yaydp_is_greater_than_comparison( $condition['comparation'] );
			$is_gte          = \yaydp_is_gte_comparison( $condition['comparation'] );

			if ( ! $is_greater_than && ! $is_gte ) {
				return null;
			}

			$sub = $condition['value'] - $order_count;

			return array(
				'type'          => 'customer_order_count',
				'missing_value' => $sub + 1,
			);
		}
		return null;
	}

	/**
	 * Get the incomplete shipping total
	 *
	 * @param array $condition Condition.
	 */
	public static function get_incomplete_shipping_total( $condition ) {
		$total_shipping_fee = \yaydp_get_shipping_fee();
		$check              = \yaydp_compare_numeric( $total_shipping_fee, $condition['value'], $condition['comparation'] );
		if ( $check ) {
			return null;
		}

		$is_greater_than = \yaydp_is_greater_than_comparison( $condition['comparation'] );
		$is_gte          = \yaydp_is_gte_comparison( $condition['comparation'] );

		if ( ! $is_greater_than && ! $is_gte ) {
			return null;
		}

		$sub = $condition['value'] - $total_shipping_fee;

		return array(
			'type'          => 'cart_shipping_total',
			'missing_value' => $sub + 1,
		);
	}

	/**
	 * Get the incomplete total weight
	 *
	 * @param array $condition Condition.
	 */
	public static function get_incomplete_cart_total_weight( $condition ) {
		$total_weight = \yaydp_get_cart_total_weight();
		$check        = \yaydp_compare_numeric( $total_weight, $condition['value'], $condition['comparation'] );
		if ( $check ) {
			return null;
		}

		$is_greater_than = \yaydp_is_greater_than_comparison( $condition['comparation'] );
		$is_gte          = \yaydp_is_gte_comparison( $condition['comparation'] );

		if ( ! $is_greater_than && ! $is_gte ) {
			return null;
		}

		$sub = $condition['value'] - $total_weight;

		return array(
			'type'          => 'cart_total_weight',
			'missing_value' => $sub + 1,
		);
	}

	/**
	 * Get the priority of given type
	 *
	 * @param string $type Given type.
	 */
	private static function get_priority( $type = 'cart_subtotal' ) {
		switch ( $type ) {
			case 'cart_subtotal':
				return 0;
			case 'cart_quantity':
				return 1;
			case 'customer_order_count':
				return 2;
			case 'cart_shipping_total':
				return 3;
			case 'cart_total_weight':
				return 4;
			default:
				return 999;
		}
	}

	/**
	 * Sort incomplete conditions
	 *
	 * @param array $array Array to be sorted.
	 */
	private static function sort_incomplete_conditions( &$array ) {
		usort(
			$array,
			function( $a, $b ) {
				return self::get_priority( $a['type'] ) <=> self::get_priority( $b['type'] );
			}
		);
	}
}
