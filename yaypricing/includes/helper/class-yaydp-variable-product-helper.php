<?php
/**
 * Responsible for managing variable products in the WooCommerce plugin
 *
 * This class provides methods for handling variable product variations, such as
 * getting variation attributes, and setting default variation attributes.
 *
 * @package YayPricing\Helper
 *
 * @since 2.4
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Variable_Product_Helper {

	/**
	 * Returns the minimum price variation in list variations of product which can be adapted to the rule.
	 *
	 * Returns null of not found any variation that match the rule
	 *
	 * @param \WC_Product $product Given variable product.
	 */
	public static function get_min_price_applicable_product( $product, $rule ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return $product;
		}
		if ( \yaydp_is_buy_x_get_y( $rule ) ) {
			$filters    = $rule->get_receive_filters();
			$match_type = 'any';
		} else {
			$filters    = $rule->get_buy_filters();
			$match_type = $rule->get_match_type_of_buy_filters();
		}
		$children_id = $product->get_children();
		$children    = array_map(
			function( $id ) {
				return \wc_get_product( $id );
			},
			$children_id
		);

		\YAYDP\Helper\YAYDP_Helper::sort_products_by_price( $children );
		foreach ( $children as $child ) {
			if ( $rule->can_apply_adjustment( $product, $filters, $match_type ) ) {
				return $child;
			}
		}
		return null;
	}

	/**
	 * Returns the maximum price variation in list variations of product which can be adapted to the rule.
	 *
	 * Returns null of not found any variation that match the rule
	 *
	 * @param \WC_Product $product Given variable product.
	 */
	public static function get_max_price_applicable_product( $product, $rule ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return $product;
		}
		if ( \yaydp_is_buy_x_get_y( $rule ) ) {
			$filters    = $rule->get_receive_filters();
			$match_type = 'any';
		} else {
			$filters    = $rule->get_buy_filters();
			$match_type = $rule->get_match_type_of_buy_filters();
		}
		$children_id = $product->get_children();
		$children    = array_map(
			function( $id ) {
				return \wc_get_product( $id );
			},
			$children_id
		);

		\YAYDP\Helper\YAYDP_Helper::sort_products_by_price( $children, 'desc' );
		foreach ( $children as $child ) {
			if ( $rule->can_apply_adjustment( $product, $filters, $match_type ) ) {
				return $child;
			}
		}
		return null;
	}

	/**
	 * Return formula for discount value
	 *
	 * Variable x is the variation price.
	 *
	 * @param string $pricing_type Given pricing type.
	 * @param float  $pricing_value Given pricing value.
	 * @param float  $maximum Given maximum discount amount.
	 */
	public static function get_discount_value_formula( $pricing_type, $pricing_value, $maximum ) {
		if ( is_null( $maximum ) ) {
			$maximum = PHP_INT_MAX;
		}
		$maximum = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $maximum );
		if ( \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
			return '';
		}
		$pricing_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $pricing_value );
		if ( \yaydp_is_flat_pricing_type( $pricing_type ) ) {
			return "x - Math.min( $pricing_value, x )";
		}
		return "Math.min( $pricing_value, $maximum )";
	}

	/**
	 * Return formula for discount amount
	 *
	 * Variable x is the variation price.
	 *
	 * @param string $pricing_type Given pricing type.
	 * @param float  $pricing_value Given pricing value.
	 * @param float  $maximum Given maximum discount amount.
	 */
	public static function get_discount_amount_formula( $pricing_type, $pricing_value, $maximum ) {
		if ( is_null( $maximum ) ) {
			$maximum = PHP_INT_MAX;
		}
		$maximum = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $maximum );
		if ( \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
			return ( "Math.min( x * $pricing_value / 100, $maximum  )" );
		}
		$pricing_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $pricing_value );
		if ( \yaydp_is_flat_pricing_type( $pricing_type ) ) {
			return "x - Math.min( $pricing_value, x )";
		}
		return "Math.min( $pricing_value, $maximum )";
	}

	/**
	 * Return formula for discounted price.
	 *
	 * Variable x is the variation price.
	 *
	 * @param string $pricing_type Given pricing type.
	 * @param float  $pricing_value Given pricing value.
	 * @param float  $maximum Given maximum discount amount.
	 */
	public static function get_discounted_price_formula( $pricing_type, $pricing_value, $maximum ) {
		$maximum = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $maximum );
		if ( \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
			return ( "x - Math.min( x * $pricing_value / 100, $maximum  )" );
		}
		$pricing_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $pricing_value );
		if ( \yaydp_is_flat_pricing_type( $pricing_type ) ) {
			return "Math.min( $pricing_value, x )";
		}

		return "x - Math.min( $pricing_value, $maximum )";
	}

	/**
	 * Returns variation of variable product that has the attachment with the given id
	 *
	 * Returns null if not found.
	 *
	 * @param \WC_Product $product Given variable product.
	 * @param string      $attachment_id Given variation image id.
	 */
	public static function get_variation_with_attachment_id( $product, $attachment_id ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return null;
		}
		$result = null;
		foreach ( \yaydp_get_product_varations( $product ) as $variation ) {
			$image_id = $variation->get_image_id();
			if ( $attachment_id == $image_id ) {
				$result = $variation;
				break;
			}
		}
		return $result;
	}
}
