<?php
/**
 * Class represents a pricing rule for YAYDP products.
 * It contains methods for setting and getting the rule's properties, as well as applying the rule to a product's price
 *
 * @package YayPricing\Abstract
 */

namespace YAYDP\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
abstract class YAYDP_Product_Pricing_Rule extends YAYDP_Rule {

	/**
	 * Calculate all possible adjustment created by the rule.
	 * Must be implemented by child
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	abstract public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart );

	/**
	 * Calculate the discount and apply modifier to the cart item.
	 * Must be implemented by child
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item.
	 */
	abstract public function discount_item( \YAYDP\Core\YAYDP_Cart_Item $item );

	/**
	 * Get minimim discount information that can apply to the product
	 * Must be implemented by child
	 *
	 * @param \WC_Product $product Product.
	 */
	abstract public function get_min_discount( $product );

	/**
	 * Get maximum discount information that can apply to the product
	 * Must be implemented by child
	 *
	 * @param \WC_Product $product Product.
	 */
	abstract public function get_max_discount( $product );

	/**
	 * Calculate all encouragements can be created by rule ( include condition encouragements )
	 * Must be implemented by child
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	abstract public function get_encouragements( \YAYDP\Core\YAYDP_Cart $cart );

	/**
	 * Retrieve rule randomize id
	 * It is not the normal id. It is the short one
	 */
	public function get_rule_id() {
		return ! empty( $this->data['rule_id'] ) ? $this->data['rule_id'] : '';
	}

	/**
	 * Retrieve match type for buy filters
	 */
	public function get_match_type_of_buy_filters() {
		return ! empty( $this->data['buy_products']['match_type'] ) ? $this->data['buy_products']['match_type'] : 'any';
	}

	/**
	 * Retrieve buy filters
	 */
	public function get_buy_filters() {
		return isset( $this->data['buy_products']['filters'] ) ? $this->data['buy_products']['filters'] : array();
	}

	/**
	 * Check whether rule can apply to given product
	 *
	 * @param \WC_Product $product Checking product.
	 * @param array|null  $filters Checking filters.
	 * @param string      $match_type Match type.
	 */
	public function can_apply_adjustment( $product, $filters = null, $match_type = 'any', $item_key = null ) {
		// Cart calculations pass a concrete $item_key and can depend on per-item
		// context (e.g. matching a specific cart line's variation attributes), so
		// those are always computed live. The display/archive path passes no item
		// key and its result is stable for the request, so memoize it: on an
		// archive this runs products x variations x rules times, each doing
		// exclusion + taxonomy matching. Key by rule + product + the filter set so
		// buy/receive checks of the same rule do not collide. Flushed on
		// yaydp_clear_cache.
		if ( ! is_null( $item_key ) ) {
			return $this->run_can_apply_adjustment( $product, $filters, $match_type, $item_key );
		}

		// Invariant: this key intentionally omits the pricing context (role/currency/
		// tax). Applicability can depend on context only through a product_price
		// filter, but role and currency are fixed for a request, so the result is
		// stable request-wide. If a future change makes applicability vary by context
		// within one request (e.g. an admin "preview as role" that re-renders under a
		// second currency), this key MUST add yaydp_pricing_context_key().
		$cache     = \YAYDP\Core\Caches\YAYDP_Request_Cache::get_instance();
		$cache_key = $this->get_id() . ':' . $product->get_id() . ':' . $match_type . ':' . md5( (string) \wp_json_encode( $filters ) );
		if ( $cache->has( 'applicability', $cache_key ) ) {
			return $cache->get( 'applicability', $cache_key );
		}

		// Conservative pre-index: when the rule's positive category/tag/product
		// filters provably cannot include this product, skip the full exclusion +
		// taxonomy matching. This only short-circuits to false for provable
		// non-matches, so the cached result is identical to the full check.
		if ( $this->cannot_possibly_match( $product, $filters ) ) {
			return $cache->set( 'applicability', $cache_key, false );
		}

		return $cache->set( 'applicability', $cache_key, $this->run_can_apply_adjustment( $product, $filters, $match_type, $item_key ) );
	}

	/**
	 * Conservative pre-index check. Returns true only when the rule's positive
	 * in_list category/tag/product filters PROVABLY cannot include the given
	 * product, so the caller can skip the full applicability computation. Returns
	 * false whenever the rule is not safely indexable or the product might match —
	 * the full check then decides. Never skips a rule that could apply (no false
	 * negatives): the product id-set and filter id-set mirror what the real
	 * category/tag/product checks compare, and any narrowing (price sub-filters,
	 * exclusions) only removes real matches, so a non-empty intersection stays a
	 * superset of the true match set.
	 *
	 * @param \WC_Product $product Product being checked.
	 * @param array|null  $filters Filters as passed to can_apply_adjustment.
	 * @return bool
	 */
	private function cannot_possibly_match( $product, $filters ) {
		if ( empty( $filters ) ) {
			$filters = $this->get_buy_filters();
		}
		$indexed_ids = $this->get_indexable_ids( $filters );
		if ( empty( $indexed_ids ) ) {
			// Not safely indexable (all_product, price/stock/attribute/variation
			// filters, or a negated filter) — cannot cheaply rule it out.
			return false;
		}
		$product_ids   = \YAYDP\Helper\YAYDP_Product_Helper::get_product_cats( $product );
		$product_ids   = array_merge( $product_ids, \YAYDP\Helper\YAYDP_Product_Helper::get_product_tags( $product ) );
		$product_ids[] = $product->get_id();
		$parent_id     = $product->get_parent_id();
		if ( ! empty( $parent_id ) ) {
			$product_ids[] = $parent_id;
		}
		return empty( array_intersect( $indexed_ids, $product_ids ) );
	}

	/**
	 * Collect the term/product ids a filter set positively requires, or return
	 * null when the set is not safely indexable: any filter that is not a positive
	 * (in_list) product_category / product_tag / product filter makes the whole
	 * set non-indexable. Ids are translated with the same hook the real checks use
	 * so multilingual sites are not mis-indexed.
	 *
	 * @param array $filters Filter set.
	 * @return array|null
	 */
	private function get_indexable_ids( $filters ) {
		if ( empty( $filters ) ) {
			return null;
		}
		$ids = array();
		foreach ( $filters as $filter ) {
			$type        = isset( $filter['type'] ) ? $filter['type'] : '';
			$comparation = isset( $filter['comparation'] ) ? $filter['comparation'] : '';
			if ( 'in_list' !== $comparation ) {
				return null;
			}
			if ( 'product_category' === $type ) {
				$taxonomy = 'product_cat';
			} elseif ( 'product_tag' === $type ) {
				$taxonomy = 'product_tag';
			} elseif ( 'product' === $type ) {
				$taxonomy = 'product';
			} else {
				return null;
			}
			$values = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
			$values = \apply_filters( 'yaydp_translated_list_object_id', $values, $taxonomy );
			$ids    = array_merge( $ids, $values );
		}
		return $ids;
	}

	/**
	 * Compute whether this rule can apply to a product, without caching.
	 *
	 * @param \WC_Product $product Checking product.
	 * @param array|null  $filters Checking filters.
	 * @param string      $match_type Match type.
	 * @param string|null $item_key Cart item key when checking a specific line.
	 */
	private function run_can_apply_adjustment( $product, $filters = null, $match_type = 'any', $item_key = null ) {

		if ( apply_filters( 'yaydp_skip_product_pricing_rule', false, $this, $product ) ) {
			return false;
		}

		if ( \YAYDP\Core\Manager\YAYDP_Exclude_Manager::check_product_exclusions( $this, $product ) ) {
			return false;
		}

		if ( \YAYDP\Core\Manager\YAYDP_Exclude_Manager::check_coupon_exclusions( $this ) ) {
			return false;
		}

		if ( empty( $filters ) ) {
			$filters    = $this->get_buy_filters();
			$match_type = $this->get_match_type_of_buy_filters();
		}
		$check = \YAYDP\Helper\YAYDP_Helper::check_applicability( $filters, $product, $match_type, $item_key );
		return $check;

	}

	/**
	 * Pricing (type, value, maximum) that applies to this cart item.
	 *
	 * The extension point for rules whose pricing is not the rule-level
	 * default: Bulk/Tiered pick a range by quantity, Simple/Bundle may derive
	 * the value from a formula. Everything below reads pricing through here.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item.
	 * @return array{type: string, value: mixed, maximum: mixed}
	 */
	public function get_item_pricing( $item ) {
		return array(
			'type'    => $this->get_pricing_type(),
			'value'   => $this->get_pricing_value(),
			'maximum' => $this->get_maximum_adjustment_amount(),
		);
	}

	/**
	 * Legacy per-item "adjustment amount" (see YAYDP_Pricing_Helper). Kept for
	 * external callers; the rule engine no longer reads it.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_adjustment_amount( $item ) {
		$pricing = $this->get_item_pricing( $item );
		return \YAYDP\Helper\YAYDP_Pricing_Helper::calculate_adjustment_amount( $item->get_price(), $pricing['type'], $pricing['value'], $pricing['maximum'] );
	}

	/**
	 * Discount taken from one unit of the item (negative for fees); see
	 * YAYDP_Pricing_Type_Registry::discount_per_item() for the caps.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_discount_amount_per_item( $item ) {
		$pricing = $this->get_item_pricing( $item );
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::discount_per_item( $pricing['type'], $item->get_price(), $pricing['value'], $pricing['maximum'] );
	}

	/**
	 * Value shown for the discount: the raw percentage for percentage types,
	 * otherwise the per-unit amount.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_discount_value_per_item( $item ) {
		$pricing = $this->get_item_pricing( $item );
		if ( \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_percentage_adjustment( $pricing['type'] ) ) {
			return $pricing['value'];
		}
		return $this->get_discount_amount_per_item( $item );
	}

	public function exec_formula( $formula, $replacement, $fallback_value ) {
		try {
			return eval( 'return ' . str_replace( '{n}', $replacement, $formula ) . ';' );
		} catch ( \Throwable $error ) {
			$by_pass = true;
		}
		return $fallback_value;
	}

	/**
	 * Check whethere count quantity by all together
	 */
	public function is_all_together_discount() {
		$discount_type = isset( $this->data['discount_type'] ) ? $this->data['discount_type'] : 'all_together';
		return 'all_together' === $discount_type;
	}

	/**
	 * Check whethere count quantity by single line item
	 */
	public function is_individual_line_item_discount() {
		$discount_type = isset( $this->data['discount_type'] ) ? $this->data['discount_type'] : 'all_together';
		return 'individual_line_item_discount' === $discount_type;
	}

	/**
	 * Check whethere count quantity by variations
	 */
	public function is_variations_discount() {
		$discount_type = isset( $this->data['discount_type'] ) ? $this->data['discount_type'] : 'all_together';
		return 'variations_discount' === $discount_type;
	}

	/**
	 * Get offer description
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $content_type Type of content, buy or get.
	 */
	public function get_offer_description( $product, $content_type = 'buy_content' ) {
		$offer_description_data = empty( $this->data['offer_description'] ) ? array() : $this->data['offer_description'];
		return new \YAYDP\Core\Offer_Description\YAYDP_Offer_Description(
			array(
				'data'         => $offer_description_data,
				'rule'         => $this,
				'content_type' => $content_type,
				'product'      => $product,
			)
		);
	}

}
