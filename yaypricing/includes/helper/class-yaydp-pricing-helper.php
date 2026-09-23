<?php
/**
 * YayPricing pricing helper
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

use YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry as Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Pricing_Helper {

	/**
	 * Legacy adjustment-amount API, kept for callers not yet on the registry.
	 *
	 * Returns the amount one unit moves by (sign preserved), except for
	 * flat_price where — as it always has — it returns the target unit price and
	 * the caller subtracts. Set-price / unknown types echo the raw value, like the
	 * old default branch. New code should call
	 * YAYDP_Pricing_Type_Registry::resolve() / adjustment_per_unit().
	 *
	 * @param float  $price            Price that the adjustment is based on.
	 * @param string $adjustment_type  Pricing type.
	 * @param float  $adjustment_value Pricing value.
	 * @param float  $the_maximum      Per-unit cap; honoured only for types that allow one.
	 */
	public static function calculate_adjustment_amount( $price, $adjustment_type, $adjustment_value, $the_maximum = PHP_INT_MAX ) {
		return Registry::legacy_adjustment_amount( $adjustment_type, $price, $adjustment_value, $the_maximum );
	}

	/**
	 * Return product price without converted
	 *
	 * @param \WC_Product $product Given product.
	 *
	 * @since 2.4
	 */
	public static function get_product_price( $product ) {
		if ( empty( $product ) ) {
			return 0;
		}
		$settings                  = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		$is_based_on_regular_price = 'regular_price' === $settings->get_discount_base_on();
		$price_context             = 'original';
		$is_product_on_sale        = $product->is_on_sale( $price_context );
		$product_sale_price        = $product->get_sale_price( $price_context );
		$product_regular_price     = $product->get_regular_price( $price_context );
		$sale_price                = $is_product_on_sale ? $product_sale_price : $product_regular_price;
		$product_price             = $is_based_on_regular_price ? $product_regular_price : $sale_price;
		$product_price             = self::get_product_fixed_price( $product_price, $product );
		// $product_price = \wc_get_price_to_display($product, ['price' => $product_price]);
		$product_price = apply_filters( 'yaydp_other_source_product_base_price', floatval( $product_price ), $product );
		return apply_filters( 'yaydp_initial_product_price', floatval( $product_price ), $product );
	}

	/**
	 * Get product price not affected by any YayPricing settings
	 *
	 * @since 3.4
	 */
	public static function get_store_product_price( $product ) {
		if ( empty( $product ) ) {
			return 0;
		}
		$price_context = 'original';
		$product_price = $product->get_price( $price_context );
		$product_price = self::get_product_fixed_price( $product_price, $product );
		// $product_price = \wc_get_price_to_display($product, ['price' => $product_price]);
		return apply_filters( 'yaydp_initial_product_price', floatval( $product_price ), $product );
	}

	/**
	 * Get product price not affected by any YayPricing settings
	 *
	 * @since 3.4
	 */
	public static function get_product_specific_price( $product, $type = 'regular' ) {
		if ( empty( $product ) ) {
			return 0;
		}
		$type === 'regular';
		$price_context = 'original';

		if ( 'regular' === $type ) {
			$product_price = $product->get_regular_price( $price_context );
		} else {
			$product_price = $product->get_sale_price( $price_context );
		}
		if ( ! empty( $product_price ) ) {
			$product_price = self::get_product_fixed_price( $product_price, $product );
		} else {
			$product_price = null;
		}

		return $product_price;
	}

	/**
	 * Returns price after converted to target currency by other apps
	 *
	 * @param float $price Given price.
	 *
	 * @since 2.4
	 *
	 * @return float
	 */
	public static function convert_price( $price ) {
		return apply_filters( 'yaydp_converted_price', $price );
	}

	/**
	 * Returns fee after converted to target currency by other apps
	 *
	 * @param float $price Given price.
	 *
	 * @since 2.4
	 *
	 * @return float
	 */
	public static function convert_fee( $price ) {
		return apply_filters( 'yaydp_converted_fee', $price );
	}

	/**
	 * Returns price after reverted to base currency
	 *
	 * @param float $price Given price.
	 *
	 * @since 2.4
	 *
	 * @return float
	 */
	public static function reverse_price( $price ) {
		return apply_filters( 'yaydp_reversed_price', $price );
	}

	/**
	 * Returns product price with the fix option
	 *
	 * @param float       $price Given price.
	 * @param \WC_Product $product Given product.
	 *
	 * @since 2.4
	 *
	 * @return float
	 */
	public static function get_product_fixed_price( $price, $product ) {
		return apply_filters( 'yaydp_product_fixed_price', $price, $product );
	}

}
