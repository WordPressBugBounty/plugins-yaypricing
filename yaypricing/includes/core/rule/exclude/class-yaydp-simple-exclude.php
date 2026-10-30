<?php
/**
 * Represents a class for managing product exclusions in YAYDP
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\Core\Rule\Exclude;

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

		if ( ! $this->excludes_product( $product ) ) {
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

	/**
	 * Whether the exclusion filters match a product, memoised per request.
	 *
	 * A variable product is excluded only when all of its published variations
	 * are. The product_variation filter matches a parent when any one child is
	 * listed, so checking the parent directly would exclude every variation
	 * (e.g. hide the pricing table) for a single excluded variation.
	 *
	 * @param \WC_Product $product Checking product.
	 *
	 * @return bool
	 */
	private function excludes_product( $product ) {
		return \YAYDP\Core\Caches\YAYDP_Request_Cache::get_instance()->remember(
			'applicability',
			'excl:' . $this->get_id() . ':' . $product->get_id(),
			function () use ( $product ) {
				if ( ! \yaydp_is_variable_product( $product ) ) {
					return $this->can_apply_adjustment( $product );
				}
				$has_variation = false;
				foreach ( $product->get_children() as $variation_id ) {
					$variation = \wc_get_product( $variation_id );
					if ( empty( $variation ) || 'publish' !== $variation->get_status() ) {
						continue;
					}
					if ( ! $this->excludes_product( $variation ) ) {
						return false;
					}
					$has_variation = true;
				}
				return $has_variation ? true : $this->can_apply_adjustment( $product );
			}
		);
	}
}
