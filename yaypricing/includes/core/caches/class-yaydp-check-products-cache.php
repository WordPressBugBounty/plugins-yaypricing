<?php

namespace YAYDP\Core\Caches;

class YAYDP_Check_Products_Cache {
	public static function is_cache( $rule, $product ) {
		global $yaydp_check_products_rules;

		$product_id = $product->get_id();
		$rule_id    = $rule->get_id();

		if ( ! isset( $yaydp_check_products_rules[ $rule_id ][ $product_id ] ) ) {
			return false;
		}
		return true;
	}

	public static function set_cache( $rule, $product, $check ) {
		global $yaydp_check_products_rules;
		$product_id = $product->get_id();
		$rule_id    = $rule->get_id();

		if ( ! isset( $yaydp_check_products_rules[ $rule_id ] ) ) {
			$yaydp_check_products_rules[ $rule_id ] = array();
		}
		$yaydp_check_products_rules[ $rule_id ][ $product_id ] = $check;
	}

	public static function get_cache( $rule, $product ) {
		global $yaydp_check_products_rules;
		$product_id = $product->get_id();
		$rule_id    = $rule->get_id();
		return $yaydp_check_products_rules[ $rule_id ][ $product_id ];
	}
}
