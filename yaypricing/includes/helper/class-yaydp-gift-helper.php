<?php
/**
 * Shared helpers for free gift cart items.
 *
 * Gift rows are real cart items flagged `is_extra` by YAYDP_Cart::publish().
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Helper class
 */
class YAYDP_Gift_Helper {

	/**
	 * Check whether a WC cart item is a free gift.
	 *
	 * @param array $cart_item Cart item.
	 * @return bool
	 */
	public static function is_gift_item( $cart_item ) {
		return \yaydp_is_extra_wc_cart_item( $cart_item );
	}

	/**
	 * Resolve the pricing rule behind a gift item.
	 *
	 * @param array $cart_item Cart item.
	 * @return mixed Rule instance or null.
	 */
	public static function get_gift_rule( $cart_item ) {
		if ( empty( $cart_item['modifiers'] ) ) {
			return null;
		}
		$modifiers = maybe_unserialize( $cart_item['modifiers'] );
		if ( ! is_array( $modifiers ) || ! isset( $modifiers[0] ) ) {
			return null;
		}
		if ( ! $modifiers[0] instanceof \YAYDP\Core\Single_Modifier\YAYDP_Product_Pricing_Modifier ) {
			return null;
		}
		return $modifiers[0]->get_rule();
	}

	/**
	 * Check whether the cart currently holds at least one gift.
	 *
	 * @return bool
	 */
	public static function cart_has_gift() {
		if ( ! function_exists( 'WC' ) || empty( \WC()->cart ) ) {
			return false;
		}
		foreach ( \WC()->cart->get_cart() as $cart_item ) {
			if ( self::is_gift_item( $cart_item ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check whether any running rule can hand out a free gift.
	 *
	 * The cart may earn one later in the same page view (add to cart refreshes
	 * the mini cart over AJAX, which cannot enqueue anything), so pages that
	 * carry a gift offer load the assets up front.
	 *
	 * @return bool
	 */
	public static function has_running_gift_rule() {
		foreach ( \yaydp_get_running_product_pricing_rules() as $rule ) {
			if ( \yaydp_is_buy_x_get_y( $rule ) && $rule->is_get_free_item() ) {
				return true;
			}
		}
		return false;
	}

	public static function map_list( $list ) {
		return array_map(
			function( $i ) {
				return array(
					'value' => $i,
				);
			},
			$list
		);
	}

	public static function compare_categories( $product, $category_list = array() ) {
		return self::product_filter_matches( 'product_category', $product, self::map_list( $category_list ) );
	}
	public static function compare_tags( $product, $tag_list = array() ) {
		return self::product_filter_matches( 'product_tag', $product, self::map_list( $tag_list ) );
	}

	/**
	 * Run one in_list product filter against a product.
	 *
	 * @param string      $slug    Product filter slug.
	 * @param \WC_Product $product Product.
	 * @param array       $value   Stored value list.
	 * @return bool
	 */
	private static function product_filter_matches( $slug, $product, array $value ) {
		return \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry::instance()->evaluate(
			array(
				'type'        => $slug,
				'comparation' => 'in_list',
				'value'       => $value,
			),
			$product,
			new \YAYDP\Product_Filter\YAYDP_Product_Filter_Context()
		);
	}

	public static function check_in_changeable_list( $selected_product, $check_product, $category_list = array(), $tag_list = array(), $free_product_type = 'suggested' ) {

		if ( $selected_product->get_id() == $check_product->get_id() ) {
			return true;
		}

		if ( ( \yaydp_is_variation_product( $selected_product ) || \yaydp_is_variable_product( $selected_product ) ) && 'suggested' === $free_product_type ) {
			 $parent_selected_product_id = \yaydp_is_variation_product( $selected_product ) ? $selected_product->get_parent() : $selected_product->get_id();
			 $parent_check_product_id    = \yaydp_is_variation_product( $check_product ) ? $check_product->get_parent() : $check_product->get_id();
			 return $parent_check_product_id == $parent_selected_product_id;
		}

		if ( 'relative' === $free_product_type ) {
			$check_category = self::compare_categories( $check_product, $category_list );
			$check_tag      = self::compare_tags( $check_product, $tag_list );
			return $check_category || $check_tag;
		}

		return true;
	}
}
