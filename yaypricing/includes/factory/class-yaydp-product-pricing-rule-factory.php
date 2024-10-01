<?php
/**
 * This class is responsible for creating instances of pricing rules for products
 *
 * @package YAYDP\Factory
 */

namespace YAYDP\Factory;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Rule_Factory extends \YAYDP\Abstracts\YAYDP_Rule_Factory {

	/**
	 * This function takes a rule data as input with the given type in that data and returns an instance of the corresponding
	 *
	 * @param array $rule_data Rule data.
	 */
	public static function get_rule( $rule_data ) {
		$path       = '\YAYDP\Core\Rule\Product_Pricing';
		$type       = isset( $rule_data['type'] ) ? $rule_data['type'] : null;
		$rule_class = "{$path}\YAYDP_Simple_Adjustment";

		if ( 'bulk_pricing' === $type ) {
			$rule_class = "{$path}\YAYDP_Bulk_Pricing";
		}
		if ( 'tiered_pricing' === $type ) {
			$rule_class = "{$path}\YAYDP_Tiered_Pricing";
		}
		if ( 'product_bundle' === $type ) {
			$rule_class = "{$path}\YAYDP_Product_Bundle";
		}
		if ( 'bogo' === $type ) {
			$rule_class = "{$path}\YAYDP_BOGO";
		}
		if ( 'buy_x_get_y' === $type ) {
			$rule_class = "{$path}\YAYDP_Buy_X_Get_Y";
		}

		return new $rule_class( $rule_data );

	}

}
