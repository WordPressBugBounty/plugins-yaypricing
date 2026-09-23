<?php
/**
 * This class represents the model for the YAYDP Report
 *
 * @package YayPricing\Models
 */

namespace YAYDP\API\Models;

/**
 * Declare class
 */
class YAYDP_Report_Model {

	/**
	 * Get Orders by time filter
	 *
	 * @param string $range_type Type of time range.
	 * @param string $from Start.
	 * @param string $to End.
	 * @param string $order_by Order by.
	 */
	public static function get_orders( $range_type, $from, $to, $order_by = 'day' ) {
		$args = array(
			'date_after'  => "{$from['year']}-{$from['month']}-{$from['date']}",
			'date_before' => "{$to['year']}-{$to['month']}-{$to['date']}",
			'limit'       => '-1',
		);
		if ( 'last_7_days' === $range_type ) {
			$start_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
			$date_now   = gmdate( 'Y-m-d' );
			$args       = array(
				'date_after'  => $start_date,
				'date_before' => $date_now,
				'limit'       => '-1',
			);
		}
		if ( 'month' === $range_type ) {
			$days_in_month = cal_days_in_month( CAL_GREGORIAN, $to['month'], $to['year'] );
			$args          = array(
				'date_after'  => "{$from['year']}-{$from['month']}-01",
				'date_before' => "{$to['year']}-{$to['month']}-$days_in_month",
				'limit'       => '-1',
			);
		}
		if ( 'year' === $range_type ) {
			$args = array(
				'date_after'  => "{$from['year']}-01-01",
				'date_before' => "{$to['year']}-12-31",
				'limit'       => '-1',
			);
		}

		$orders = \wc_get_orders( $args );
		$result = self::filter_orders( $orders, $order_by );
		return $result;

	}

	/**
	 * Get all product pricing rules
	 * Includes the removed rules.
	 */
	public static function get_all_product_pricing_rules() {
		$removed_rules = get_option( 'yaydp_removed_product_pricing_rules', array() );
		$removed_rules = empty( $removed_rules ) ? array() : $removed_rules;
		$rules         = get_option( 'yaydp_product_pricing_rules', array() );
		$rules         = empty( $rules ) ? array() : $rules;

		return array_merge( $rules, $removed_rules );
	}

	/**
	 * Get all cart discount rules
	 * Includes the removed rules.
	 */
	public static function get_all_cart_discount_rules() {
		$removed_rules = get_option( 'yaydp_removed_cart_discount_rules', array() );
		$removed_rules = empty( $removed_rules ) ? array() : $removed_rules;
		$rules         = get_option( 'yaydp_cart_discount_rules', array() );
		$rules         = empty( $rules ) ? array() : $rules;

		return array_merge( $rules, $removed_rules );
	}

	/**
	 * Get all checkout fee rules
	 * Includes the removed rules.
	 */
	public static function get_all_checkout_fee_rules() {
		$removed_rules = get_option( 'yaydp_removed_checkout_fee_rules', array() );
		$removed_rules = empty( $removed_rules ) ? array() : $removed_rules;
		$rules         = get_option( 'yaydp_checkout_fee_rules', array() );
		$rules         = empty( $rules ) ? array() : $rules;

		return array_merge( $rules, $removed_rules );
	}

	/**
	 * Filter orders by order type
	 *
	 * @param array  $orders Given orders.
	 * @param string $order_by Order by.
	 */
	public static function filter_orders( $orders, $order_by = 'day' ) {
		$result = array_reduce(
			$orders,
			function( $res, $order ) use ( $order_by ) {
				$created_date = $order->get_date_created()->date( 'Y/m/d' );
				if ( 'month' === $order_by ) {
					$created_date = gmdate( 'Y/m', strtotime( $created_date ) );
				}
				if ( 'year' === $order_by ) {
					$created_date = gmdate( 'Y', strtotime( $created_date ) );
				}
				if ( ! isset( $res[ $created_date ] ) ) {
					$res[ $created_date ] = array(
						'Orders' => 0,
					);
				}

				$product_pricing_rules = $order->get_meta( 'yaydp_product_pricing_rules' );
				$cart_discount_rules   = $order->get_meta( 'yaydp_cart_discount_rules' );
				$checkout_fee_rules    = $order->get_meta( 'yaydp_checkout_fee_rules' );

				/**
				 * Only orders that had at least one rule applied are counted.
				 * Orders without any YayPricing discount still create the date
				 * bucket, so a day with sales but no discounts shows zero
				 * instead of dropping out of the chart.
				 */
				if ( ! empty( $product_pricing_rules ) || ! empty( $cart_discount_rules ) || ! empty( $checkout_fee_rules ) ) {
					$res[ $created_date ]['Orders'] ++;
				}

				$all_product_pricing_rules = self::get_all_product_pricing_rules();
				if ( ! empty( $product_pricing_rules ) ) {
					foreach ( $product_pricing_rules as $rule_id ) {
						$rule = null;
						foreach ( $all_product_pricing_rules as $r ) {
							if ( $r['id'] === $rule_id ) {
								$rule = $r;
								break;
							}
						}
						if ( is_null( $rule ) ) {
							continue;
						}
						if ( isset( $res[ $created_date ][ $rule_id ] ) ) {
							$res[ $created_date ][ $rule_id ]['orders']++;
						} else {
							$res[ $created_date ][ $rule_id ] = array(
								'type'   => 'product_pricing',
								'name'   => $rule['name'],
								'orders' => 1,
							);
						}
					}
				}

				$all_cart_discount_rules = self::get_all_cart_discount_rules();

				if ( ! empty( $cart_discount_rules ) ) {
					foreach ( $cart_discount_rules as $rule_id ) {
						$rule = null;
						foreach ( $all_cart_discount_rules as $r ) {
							if ( $r['id'] === $rule_id ) {
								$rule = $r;
								break;
							}
						}
						if ( is_null( $rule ) ) {
							continue;
						}
						if ( isset( $res[ $created_date ][ $rule_id ] ) ) {
							$res[ $created_date ][ $rule_id ]['orders']++;
						} else {
							$res[ $created_date ][ $rule_id ] = array(
								'type'   => 'cart_discount',
								'name'   => $rule['name'],
								'orders' => 1,
							);
						}
					}
				}

				$all_checkout_fee_rules = self::get_all_checkout_fee_rules();

				if ( ! empty( $checkout_fee_rules ) ) {
					foreach ( $checkout_fee_rules as $rule_id ) {
						$rule = null;
						foreach ( $all_checkout_fee_rules as $r ) {
							if ( $r['id'] === $rule_id ) {
								$rule = $r;
								break;
							}
						}
						if ( is_null( $rule ) ) {
							continue;
						}
						if ( isset( $res[ $created_date ][ $rule_id ] ) ) {
							$res[ $created_date ][ $rule_id ]['orders']++;
						} else {
							$res[ $created_date ][ $rule_id ] = array(
								'type'   => 'checkout_fee',
								'name'   => $rule['name'],
								'orders' => 1,
							);
						}
					}
				}

				return $res;
			},
			array()
		);
		/**
		 * The chart plots the periods in the order they arrive and
		 * wc_get_orders returns the newest orders first, so without this the x
		 * axis runs backwards. Every period key is zero padded, so sorting them
		 * as text is chronological.
		 */
		ksort( $result, SORT_STRING );
		return $result;
	}

	/**
	 * Get rule by id
	 *
	 * @param array $id Given rule id.
	 */
	public static function get_rule_by_id( $id ) {
		$product_pricing_rules = self::get_all_product_pricing_rules();
		$cart_discount_rules   = self::get_all_cart_discount_rules();
		$checkout_fee_rules    = self::get_all_checkout_fee_rules();
		$find_rule             = current(
			array_filter(
				$product_pricing_rules,
				function( $rule ) use ( $id ) {
					return $rule['id'] === $id;
				}
			)
		);
		if ( ! empty( $find_rule ) ) {
			$find_rule['rule_type'] = 'product_pricing';
			return $find_rule;
		}
		$find_rule = current(
			array_filter(
				$cart_discount_rules,
				function( $rule ) use ( $id ) {
					return $rule['id'] === $id;
				}
			)
		);
		if ( ! empty( $find_rule ) ) {
			$find_rule['rule_type'] = 'cart_discount';
			return $find_rule;
		}
		$find_rule = current(
			array_filter(
				$checkout_fee_rules,
				function( $rule ) use ( $id ) {
					return $rule['id'] === $id;
				}
			)
		);
		if ( ! empty( $find_rule ) ) {
			$find_rule['rule_type'] = 'checkout_fee';
			return $find_rule;
		}
		return null;

	}

}
