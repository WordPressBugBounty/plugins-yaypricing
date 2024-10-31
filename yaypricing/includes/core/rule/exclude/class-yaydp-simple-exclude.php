<?php
/**
 * Represents a class for managing product exclusions in YAYDP
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\Core\Rule\Exclude;

use YAYDP\Core\Caches\YAYDP_Check_Products_Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Simple_Exclude extends \YAYDP\Abstracts\YAYDP_Exclude_Rule {

	/**
	 * Returns the match type of a product
	 *
	 * @return string
	 */
	public function get_match_type_of_buy_filters() {
		return ! empty( $this->data['buy_products']['match_type'] ) ? $this->data['buy_products']['match_type'] : 'any';
	}

	/**
	 * Returns the product filters
	 *
	 * @return array
	 */
	public function get_buy_filters() {
		return isset( $this->data['buy_products']['filters'] ) ? $this->data['buy_products']['filters'] : array();
	}

	/**
	 * Check whether rule can apply to given product
	 *
	 * @param \WC_Product $product Checking product.
	 */
	public function can_apply_adjustment( $product ) {
		$filters    = $this->get_buy_filters();
		$match_type = $this->get_match_type_of_buy_filters();
		$check      = \YAYDP\Helper\YAYDP_Helper::check_applicability( $filters, $product, $match_type );
		return $check;
	}

	/**
	 * Check whethere product is exclude by rule
	 */
	public function check_exclude( $rule, $product = null ) {

		if ( empty( $product ) ) {
			return false;
		}

		if ( ! YAYDP_Check_Products_Cache::is_cache( $rule, $product ) ) {
			$check = $this->can_apply_adjustment( $product );
			YAYDP_Check_Products_Cache::set_cache( $rule, $product, $check );
		} else {
			$check = YAYDP_Check_Products_Cache::get_cache( $rule, $product );
		}

		if ( ! $check ) {
			return false;
		}

		$excluded_list = parent::get_excluded_list();

		if ( in_array( 'all', $excluded_list, true ) ) {
			return true;
		}

		if ( ! $rule instanceof \YAYDP\Abstracts\YAYDP_Product_Pricing_Rule ) {
			return false;
		}

		return in_array( $rule->get_id(), $excluded_list, true );

	}
}
