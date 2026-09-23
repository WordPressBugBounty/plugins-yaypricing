<?php
/**
 * Resolve rule ids to their name and type for reporting.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Rule_Map {

	/**
	 * Cached map of rule id => [ type, name ].
	 *
	 * @var null|array
	 */
	private static $map = null;

	/**
	 * Map of every known rule id to its name and type, deleted rules included.
	 *
	 * Built once per request, so writing many orders does not repeat the option
	 * reads that made the previous report implementation slow.
	 */
	public static function get() {
		if ( ! is_null( self::$map ) ) {
			return self::$map;
		}
		$sources = array(
			'product_pricing' => \YAYDP\API\Models\YAYDP_Report_Model::get_all_product_pricing_rules(),
			'cart_discount'   => \YAYDP\API\Models\YAYDP_Report_Model::get_all_cart_discount_rules(),
			'checkout_fee'    => \YAYDP\API\Models\YAYDP_Report_Model::get_all_checkout_fee_rules(),
		);
		self::$map = array();
		foreach ( $sources as $type => $rules ) {
			if ( ! is_array( $rules ) ) {
				continue;
			}
			foreach ( $rules as $rule ) {
				if ( empty( $rule['id'] ) ) {
					continue;
				}
				self::$map[ $rule['id'] ] = array(
					'type' => $type,
					'name' => isset( $rule['name'] ) ? $rule['name'] : '',
				);
			}
		}
		return self::$map;
	}

	/**
	 * Look up a single rule, or null when it is not known.
	 *
	 * @param string $rule_id Given rule id.
	 */
	public static function find( $rule_id ) {
		if ( \YAYDP\Helper\YAYDP_Rule_Discount_Helper::COMBINED_RULE_ID === $rule_id ) {
			return array(
				'type' => 'cart_discount',
				'name' => __( 'Combined discount', 'yaypricing' ),
			);
		}
		$map = self::get();
		return isset( $map[ $rule_id ] ) ? $map[ $rule_id ] : null;
	}

	/**
	 * Drop the cache. Used by long running batches that may outlive a rule edit.
	 */
	public static function flush() {
		self::$map = null;
	}
}
