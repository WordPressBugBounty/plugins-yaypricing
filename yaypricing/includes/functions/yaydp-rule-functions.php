<?php
/**
 * YayPricing functions for rule
 *
 * Declare global functions
 *
 * @package YayPricing\Functions
 */

if ( ! function_exists( 'yaydp_is_product_pricing' ) ) {

	/**
	 * Check whether rule is Product Pricing.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_product_pricing( $rule ) {
		return $rule instanceof YAYDP\Abstracts\YAYDP_Product_Pricing_Rule;
	}
}

if ( ! function_exists( 'yaydp_is_simple_adjustment' ) ) {

	/**
	 * Check whether rule is Simple Adjustment.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_simple_adjustment( $rule ) {
		return $rule instanceof YAYDP\Core\Rule\Product_Pricing\YAYDP_Simple_Adjustment;
	}
}

if ( ! function_exists( 'yaydp_is_bulk_pricing' ) ) {

	/**
	 * Check whether rule is Bulk Pricing.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_bulk_pricing( $rule ) {
		return $rule instanceof YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Pricing;
	}
}

if ( ! function_exists( 'yaydp_is_product_bundle' ) ) {

	/**
	 * Check whether rule is Product Bundle.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_product_bundle( $rule ) {
		return $rule instanceof YAYDP\Core\Rule\Product_Pricing\YAYDP_Product_Bundle;
	}
}
if ( ! function_exists( 'yaydp_is_tiered_pricing' ) ) {

	/**
	 * Check whether rule is Tiered Pricing.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_tiered_pricing( $rule ) {
		return $rule instanceof YAYDP\Core\Rule\Product_Pricing\YAYDP_Tiered_Pricing;
	}
}
if ( ! function_exists( 'yaydp_is_bogo' ) ) {

	/**
	 * Check whether rule is BOGO.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_bogo( $rule ) {
		return $rule instanceof YAYDP\Core\Rule\Product_Pricing\YAYDP_Bogo;
	}
}

if ( ! function_exists( 'yaydp_is_buy_x_get_y' ) ) {

	/**
	 * Check whether rule is Buy X Get Y.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_buy_x_get_y( $rule ) {
		return $rule instanceof YAYDP\Core\Rule\Product_Pricing\YAYDP_Buy_X_Get_Y;
	}
}

if ( ! function_exists( 'yaydp_is_cart_discount' ) ) {

	/**
	 * Check whether rule is Cart Discount.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_cart_discount( $rule ) {
		return $rule instanceof YAYDP\Abstracts\YAYDP_Cart_Discount_Rule;
	}
}

if ( ! function_exists( 'yaydp_is_checkout_fee' ) ) {

	/**
	 * Check whether rule is Checkout Fee.
	 *
	 * @param object $rule Checking rule.
	 */
	function yaydp_is_checkout_fee( $rule ) {
		return $rule instanceof YAYDP\Abstracts\YAYDP_Checkout_Fee_Rule;
	}
}

if ( ! function_exists( 'yaydp_is_percentage_pricing_type' ) ) {

	/**
	 * Check whether pricing type is percentage.
	 *
	 * @param string $type Checking type.
	 */
	function yaydp_is_percentage_pricing_type( $type = 'fixed_discount' ) {
		return false !== strpos( $type, 'percent' );
	}
}

if ( ! function_exists( 'yaydp_is_flat_pricing_type' ) ) {

	/**
	 * Check whether pricing type is flat.
	 *
	 * @param string $type Checking type.
	 */
	function yaydp_is_flat_pricing_type( $type = 'fixed_discount' ) {
		return 'flat_price' === $type;
	}
}

if ( ! function_exists( 'yaydp_is_fixed_pricing_type' ) ) {

	/**
	 * Check whether pricing type is fixed.
	 *
	 * @param string $type Checking type.
	 */
	function yaydp_is_fixed_pricing_type( $type = 'fixed_discount' ) {
		return false !== strpos( $type, 'fixed' );
	}
}

if ( ! function_exists( 'yaydp_get_formatted_discount_value' ) ) {

	/**
	 * Format pricing value.
	 * If add % if is percentage.
	 * WC format if is fixed.
	 *
	 * @param float  $value Pricing value.
	 * @param string $type Pricing type.
	 */
	function yaydp_get_formatted_pricing_value( $value, $type = 'fixed_discount' ) {
		return \yaydp_is_percentage_pricing_type( $type ) ? "$value%" : \wc_price( $value );
	}
}

if ( ! function_exists( 'yaydp_is_fixed_product_pricing_type' ) ) {

	/**
	 * Check whether pricing type is fixed product.
	 *
	 * @param string $type Checking type.
	 */
	function yaydp_is_fixed_product_pricing_type( $type = 'fixed_discount' ) {
		return false !== strpos( $type, 'fixed_product' );
	}
}
if ( ! function_exists( 'yaydp_get_rule' ) ) {

	/**
	 * Gets rule object from rule id
	 *
	 * @param string $rule_id rule id.
	 * @deprecated 3.4.2
	 */
	function yaydp_get_rule( $rule_id ) {
		$found_rule    = null;
		$database_data = get_option( 'yaydp_product_pricing_rules', array() );
		if ( is_array( $database_data ) ) {
			foreach ( $database_data as $rule ) {
				if ( $rule['rule_id'] == $rule_id ) {
					$found_rule = $rule;
					break;
				}
			}
		}
		return ! is_null( $found_rule ) ? \YAYDP\Factory\YAYDP_Product_Pricing_Rule_Factory::get_rule( $found_rule ) : null;
	}
}

if ( ! function_exists( 'yaydp_get_pricing_rule_by_id' ) ) {

	/**
	 * Gets rule object from rule id
	 *
	 * @param string $rule_id rule id.
	 */
	function yaydp_get_pricing_rule_by_id( $rule_id ) {
		$found_rule    = null;
		$database_data = get_option( 'yaydp_product_pricing_rules', array() );
		if ( is_array( $database_data ) ) {
			foreach ( $database_data as $rule ) {
				if ( $rule['id'] == $rule_id ) {
					$found_rule = $rule;
					break;
				}
			}
		}
		return ! is_null( $found_rule ) ? \YAYDP\Factory\YAYDP_Product_Pricing_Rule_Factory::get_rule( $found_rule ) : null;
	}
}

if ( ! function_exists( 'yaydp_get_cart_discount_rule' ) ) {

	/**
	 * Gets cart rule object from rule id
	 *
	 * @since 3.4.2
	 */
	function yaydp_get_cart_rule( $rule_id ) {
		$found_rule    = null;
		$database_data = get_option( 'yaydp_cart_discount_rules', array() );
		if ( is_array( $database_data ) ) {
			foreach ( $database_data as $rule ) {
				if ( $rule['rule_id'] == $rule_id ) {
					$found_rule = $rule;
					break;
				}
			}
		}
		return ! is_null( $found_rule ) ? \YAYDP\Factory\YAYDP_Cart_Discount_Rule_Factory::get_rule( $found_rule ) : null;
	}
}

if ( ! function_exists( 'yaydp_get_checkout_fee_rule' ) ) {

	/**
	 * Gets checkout fee rule object from rule id
	 *
	 * @since 3.4.2
	 */
	function yaydp_get_checkout_fee_rule( $rule_id ) {
		$found_rule    = null;
		$database_data = get_option( 'yaydp_checkout_fee_rules', array() );
		if ( is_array( $database_data ) ) {
			foreach ( $database_data as $rule ) {
				if ( $rule['rule_id'] == $rule_id ) {
					$found_rule = $rule;
					break;
				}
			}
		}
		return ! is_null( $found_rule ) ? \YAYDP\Factory\YAYDP_Checkout_Fee_Rule_Factory::get_rule( $found_rule ) : null;
	}
}

if ( ! function_exists( 'yaydp_get_product_pricing_rule' ) ) {

	/**
	 * Gets product pricing rule object from rule id
	 *
	 * @param string $rule_id rule id.
	 * @since 3.4.2
	 */
	function yaydp_get_product_pricing_rule( $rule_id ) {
		$found_rule    = null;
		$database_data = get_option( 'yaydp_product_pricing_rules', array() );
		if ( is_array( $database_data ) ) {
			foreach ( $database_data as $rule ) {
				if ( $rule['rule_id'] == $rule_id ) {
					$found_rule = $rule;
					break;
				}
			}
		}
		return ! is_null( $found_rule ) ? \YAYDP\Factory\YAYDP_Product_Pricing_Rule_Factory::get_rule( $found_rule ) : null;
	}
}
