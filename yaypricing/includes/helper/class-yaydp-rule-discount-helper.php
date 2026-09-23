<?php
/**
 * Handle per-rule discount amounts stored on the order.
 *
 * These amounts are captured at checkout because it is the only moment the
 * original (pre-discount) prices are still known. WooCommerce derives both the
 * line subtotal and total from the already discounted price, so the original
 * cannot be recovered afterwards.
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Rule_Discount_Helper {

	/**
	 * Order meta key holding [ rule_id => amount ].
	 *
	 * Amounts are stored in shop base currency, excluding tax.
	 * Discounts are positive, checkout fees are negative, and null means the
	 * amount is not knowable for that rule.
	 */
	const META_KEY = '_yaydp_rule_discounts';

	/**
	 * Stands in for the rules behind a merged coupon.
	 *
	 * A coupon covering several rules carries one total that cannot be split
	 * between them. Recording that total here keeps the money visible in the
	 * reported discount cost, while the rules themselves stay unknown so none of
	 * them is credited with an amount it did not necessarily give.
	 */
	const COMBINED_RULE_ID = 'yaydp_combined_discount';

	/**
	 * Merge per-rule amounts into the order meta.
	 *
	 * Existing entries for the same rule are overwritten rather than summed, so
	 * that a hook firing more than once for an order cannot inflate the amounts.
	 *
	 * @param int   $order_id Given order id.
	 * @param array $amounts  Map of rule id => amount (float) or null when unknown.
	 */
	public static function merge( $order_id, array $amounts ) {
		if ( empty( $amounts ) ) {
			return;
		}
		$order = \wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$stored = $order->get_meta( self::META_KEY );
		$stored = is_array( $stored ) ? $stored : array();

		foreach ( $amounts as $rule_id => $amount ) {
			$stored[ $rule_id ] = is_null( $amount ) ? null : (float) \wc_format_decimal( $amount, 4 );
		}

		$order->update_meta_data( self::META_KEY, $stored );
		$order->save();
	}

	/**
	 * Read per-rule amounts from an order.
	 *
	 * @param \WC_Order $order Given order.
	 *
	 * @return array Map of rule id => amount or null. Empty for orders placed
	 *               before this data was captured.
	 */
	public static function get( $order ) {
		if ( ! $order ) {
			return array();
		}
		$stored = $order->get_meta( self::META_KEY );
		return is_array( $stored ) ? $stored : array();
	}
}
