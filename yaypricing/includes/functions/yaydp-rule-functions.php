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
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry: Registry::unit() / sign(). Kept as a compatibility wrapper.
	 */
	function yaydp_is_percentage_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_percentage_adjustment( $type );
	}
}

if ( ! function_exists( 'yaydp_is_flat_pricing_type' ) ) {

	/**
	 * Check whether pricing type is flat.
	 *
	 * @param string $type Checking type.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry: a 'flat_price' comparison. Kept as a compatibility wrapper.
	 */
	function yaydp_is_flat_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return 'flat_price' === $type;
	}
}

if ( ! function_exists( 'yaydp_is_fixed_pricing_type' ) ) {

	/**
	 * Check whether pricing type is fixed.
	 *
	 * @param string $type Checking type.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry::is_money_amount(). Kept as a compatibility wrapper.
	 */
	function yaydp_is_fixed_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_money_amount( $type );
	}
}

if ( ! function_exists( 'yaydp_is_highest_item_price_pricing_type' ) ) {

	/**
	 * Check whether pricing type re-prices each bundle unit to the bundle's highest item price.
	 *
	 * @param string $type Checking type.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry: Registry::requires_group(). Kept as a compatibility wrapper.
	 */
	function yaydp_is_highest_item_price_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return 'highest_item_price' === $type;
	}
}

if ( ! function_exists( 'yaydp_is_fixed_item_price_pricing_type' ) ) {

	/**
	 * Check whether pricing type re-prices each bundle unit to an admin-set fixed price.
	 *
	 * @param string $type Checking type.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry: a 'fixed_item_price' comparison. Kept as a compatibility wrapper.
	 */
	function yaydp_is_fixed_item_price_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return 'fixed_item_price' === $type;
	}
}

if ( ! function_exists( 'yaydp_is_uniform_item_price_pricing_type' ) ) {

	/**
	 * Check whether pricing type re-prices every matched bundle unit to a uniform target
	 * (highest item price or an admin-set fixed price). Shares the bundle re-price branches.
	 *
	 * @param string $type Checking type.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry: Registry::sign() === 0. Kept as a compatibility wrapper.
	 */
	function yaydp_is_uniform_item_price_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return 0 === \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::sign( $type ) && \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_known( $type );
	}
}

if ( ! function_exists( 'yaydp_is_free_pricing_type' ) ) {

	/**
	 * Check whether pricing type gives the affected bundle units away for free.
	 * Bundle-only type: each freed unit is discounted at its full initial price,
	 * and free rules track consumed units so stacked free rules free distinct units.
	 *
	 * @param string $type Checking type.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry: a 'free' comparison. Kept as a compatibility wrapper.
	 */
	function yaydp_is_free_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return 'free' === $type;
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
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::format_value( $value, $type );
	}
}

if ( ! function_exists( 'yaydp_is_fixed_product_pricing_type' ) ) {

	/**
	 * Check whether pricing type is fixed product.
	 *
	 * @param string $type Checking type.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry: a 'fixed_product' comparison. Kept as a compatibility wrapper.
	 */
	function yaydp_is_fixed_product_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return 'fixed_product' === $type;
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

if ( ! function_exists( 'yaydp_is_product_fee' ) ) {

	/**
	 * Check whether rule is BOGO.
	 *
	 * @param object $rule Checking rule.
	 * @since 3.2
	 */
	function yaydp_is_product_fee( $rule ) {
		return $rule instanceof \YAYDP\Core\Rule\Product_Pricing\YAYDP_Product_Fee;
	}
}

if ( ! function_exists( 'yaydp_is_fee_pricing_type' ) ) {

	/**
	 * Check whether pricing type is fee.
	 *
	 * @param string $type Checking type.
	 *
	 * @since 3.2
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry: Registry::sign() === 1. Kept as a compatibility wrapper.
	 */
	function yaydp_is_fee_pricing_type( $type = 'fixed_discount' ) {
		_deprecated_function( __FUNCTION__, '3.5.8', 'YAYDP\\Pricing_Type\\YAYDP_Pricing_Type_Registry' );
		return 1 === \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::sign( $type );
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

