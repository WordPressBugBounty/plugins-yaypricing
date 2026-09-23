<?php
/**
 * Reconstruct per-rule discount amounts for orders placed before the amounts
 * were captured at checkout.
 *
 * Only what the order itself still records can be recovered. Nothing is
 * estimated: when an amount cannot be established it is reported as null, which
 * the report renders as unknown rather than as zero.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Backfill_Resolver {

	/**
	 * Rebuild the per-rule amounts for a historical order.
	 *
	 * @param \WC_Order $order Given order.
	 *
	 * @return array Map of rule id => amount, or null where unknown.
	 */
	public static function resolve( $order ) {
		return self::resolve_cart_discounts( $order )
			+ self::resolve_checkout_fees( $order )
			+ self::resolve_product_pricing( $order );
	}

	/**
	 * Cart discounts were applied through generated coupons, so the coupon line
	 * on the order still carries the exact amount.
	 *
	 * A coupon that maps to more than one rule ( the combined coupon ) holds a
	 * single merged total that cannot be split, so those rules stay unknown.
	 *
	 * @param \WC_Order $order Given order.
	 */
	private static function resolve_cart_discounts( $order ) {
		$rule_ids = self::get_rule_ids( $order, 'yaydp_cart_discount_rules' );
		if ( empty( $rule_ids ) ) {
			return array();
		}

		$amounts = array_fill_keys( $rule_ids, null );
		foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
			$code    = \wc_format_coupon_code( $coupon_item->get_code() );
			$matched = self::match_rules_by_coupon_code( $rule_ids, $code );
			if ( empty( $matched ) ) {
				continue;
			}
			if ( count( $matched ) > 1 ) {
				/**
				 * A merged coupon holds one total for several rules. It is
				 * recorded against the combined placeholder so the amount still
				 * counts towards the reported discount cost, while the rules it
				 * covers stay unknown.
				 */
				$combined             = \YAYDP\Helper\YAYDP_Rule_Discount_Helper::COMBINED_RULE_ID;
				$current              = isset( $amounts[ $combined ] ) ? $amounts[ $combined ] : 0;
				$amounts[ $combined ] = $current + (float) $coupon_item->get_discount();
				continue;
			}
			$amounts[ reset( $matched ) ] = (float) $coupon_item->get_discount();
		}
		return $amounts;
	}

	/**
	 * Rules whose generated coupon code matches the given code.
	 *
	 * The code is built from either the rule id or the rule name depending on a
	 * store setting, and that setting may have changed since the order was
	 * placed, so both forms are considered.
	 *
	 * @param array  $rule_ids Rule ids recorded on the order.
	 * @param string $code     Formatted coupon code from the order.
	 */
	private static function match_rules_by_coupon_code( array $rule_ids, $code ) {
		$matched = array();
		foreach ( $rule_ids as $rule_id ) {
			$rule       = YAYDP_Report_Rule_Map::find( $rule_id );
			$candidates = array( \wc_format_coupon_code( $rule_id ) );
			if ( ! is_null( $rule ) && '' !== $rule['name'] ) {
				$candidates[] = \wc_format_coupon_code( $rule['name'] );
			}
			if ( in_array( $code, $candidates, true ) ) {
				$matched[] = $rule_id;
			}
		}
		return $matched;
	}

	/**
	 * Checkout fees were added as fee lines named after the rule.
	 *
	 * Matching is by name, so rules that were renamed or that share a name
	 * cannot be told apart and stay unknown.
	 *
	 * @param \WC_Order $order Given order.
	 */
	private static function resolve_checkout_fees( $order ) {
		$rule_ids = self::get_rule_ids( $order, 'yaydp_checkout_fee_rules' );
		if ( empty( $rule_ids ) ) {
			return array();
		}

		$amounts = array_fill_keys( $rule_ids, null );
		foreach ( $order->get_items( 'fee' ) as $fee_item ) {
			$matched = array();
			foreach ( $rule_ids as $rule_id ) {
				$rule = YAYDP_Report_Rule_Map::find( $rule_id );
				if ( ! is_null( $rule ) && '' !== $rule['name'] && $rule['name'] === $fee_item->get_name() ) {
					$matched[] = $rule_id;
				}
			}
			if ( 1 !== count( $matched ) ) {
				continue;
			}
			// Fees are money gained rather than given up, so they are negated.
			$amounts[ reset( $matched ) ] = - (float) $fee_item->get_total();
		}
		return $amounts;
	}

	/**
	 * Product pricing changed the item price directly at checkout, and
	 * WooCommerce derived both the line subtotal and total from that reduced
	 * price. The original price is therefore gone and the discount cannot be
	 * recovered. These rules are reported as unknown, never estimated.
	 *
	 * @param \WC_Order $order Given order.
	 */
	private static function resolve_product_pricing( $order ) {
		return array_fill_keys( self::get_rule_ids( $order, 'yaydp_product_pricing_rules' ), null );
	}

	/**
	 * Rule ids recorded on the order under the given meta key.
	 *
	 * @param \WC_Order $order    Given order.
	 * @param string    $meta_key Meta key holding the rule ids.
	 */
	private static function get_rule_ids( $order, $meta_key ) {
		$stored = $order->get_meta( $meta_key );
		return is_array( $stored ) ? array_values( array_filter( $stored ) ) : array();
	}
}
