<?php
/**
 * YayPricing pricing helper
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Pricing_Helper {

	/**
	 * This function calculates the discount amount based on the given parameters
	 *
	 * @param float  $price    Price that discount based on.
	 * @param string $adjustment_type   Adjustment type.
	 * @param float  $adjustment_value  Value of the adjustment.
	 * @param float  $the_maximum       The maximum of the discount amount.
	 */
	public static function calculate_adjustment_amount( $price, $adjustment_type, $adjustment_value, $the_maximum = PHP_INT_MAX ) {
		if ( \is_null( $the_maximum ) ) {
			$the_maximum = PHP_INT_MAX;
		}
		switch ( $adjustment_type ) {
			case 'fixed_discount':
				$adjustment_amount = $adjustment_value;
				break;
			case 'fixed_fee':
				$adjustment_amount = $adjustment_value;
				break;
			case 'fixed_product':
					$adjustment_amount = $adjustment_value;
				break;
			case 'percentage_discount':
			case 'percentage_fee':
				$adjustment_amount = $price * $adjustment_value / 100;
				break;
			case 'flat_price':
				$adjustment_amount = min( $price, $adjustment_value );
				break;
			default:
				$adjustment_amount = $adjustment_value;
				break;
		}
		return min( $the_maximum, $adjustment_amount );

	}

	/**
	 * Return product price without converted
	 *
	 * @param \WC_Product $product Given product.
	 *
	 * @since 2.4
	 */
	public static function get_product_price( $product ) {
		if ( ! $product instanceof \WC_Product ) {
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
		// $product_price 			   = \wc_get_price_to_display($product, ['price' => $product_price]);
		return apply_filters( 'yaydp_initial_product_price', floatval( $product_price ), $product );
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
