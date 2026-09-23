<?php
/**
 * Track what checkout fee rules changed the shipping cost by.
 *
 * Rules with "Apply to shipping" enabled adjust the shipping rates instead of
 * adding a cart fee, so there is no fee line to read their amount from later.
 * The amount is therefore recorded as the rates are adjusted, which is the only
 * moment the cost before and after are both known.
 *
 * Rates are adjusted for every shipping method offered, not only the one the
 * customer ends up picking, so the amounts are kept per package and rate and
 * are resolved against the chosen methods when the order is placed.
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Shipping_Adjustment_Tracker {

	/**
	 * Amounts as [ rule_id ][ package_index ][ rate_id ] => amount.
	 *
	 * Positive is money given away, matching the sign used for discounts.
	 *
	 * @var array
	 */
	private static $amounts = array();

	/**
	 * Record what one rule changed one rate by.
	 *
	 * Recording the same rate again overwrites rather than adds up, because
	 * shipping is recalculated several times during a checkout and each pass
	 * describes the same adjustment.
	 *
	 * @param string $rule_id       Given rule id.
	 * @param int    $package_index Index of the shipping package.
	 * @param string $rate_id       Id of the shipping rate.
	 * @param float  $amount        Cost before the rule, less the cost after it.
	 */
	public static function record( $rule_id, $package_index, $rate_id, $amount ) {
		self::$amounts[ $rule_id ][ $package_index ][ $rate_id ] = (float) $amount;
	}

	/**
	 * What a rule changed the shipping the customer actually chose by.
	 *
	 * Rates the customer did not choose cost the store nothing, so they are not
	 * counted. A rule with no recorded rate at all returns null: nothing was
	 * observed, which is not the same as an adjustment of zero.
	 *
	 * @param string $rule_id Given rule id.
	 *
	 * @return null|float Amount, or null when the rule adjusted no rate.
	 */
	public static function get_chosen_amount( $rule_id ) {
		if ( empty( self::$amounts[ $rule_id ] ) ) {
			return null;
		}

		$total = 0.0;
		foreach ( self::get_chosen_rates() as $package_index => $rate_id ) {
			if ( isset( self::$amounts[ $rule_id ][ $package_index ][ $rate_id ] ) ) {
				$total += self::$amounts[ $rule_id ][ $package_index ][ $rate_id ];
			}
		}
		return $total;
	}

	/**
	 * The shipping rate chosen for each package, keyed by package index.
	 */
	private static function get_chosen_rates() {
		if ( ! function_exists( 'WC' ) || empty( \WC()->session ) ) {
			return array();
		}
		$chosen = \WC()->session->get( 'chosen_shipping_methods' );
		return is_array( $chosen ) ? $chosen : array();
	}
}
