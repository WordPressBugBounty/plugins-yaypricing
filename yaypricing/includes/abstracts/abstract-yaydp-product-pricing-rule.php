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
	 * Calculate the adjustment amount for item.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_adjustment_amount( $item ) {
		$item_price                = $item->get_price();
		$pricing_type              = $this->get_pricing_type();
		$pricing_value             = $this->get_pricing_value();
		$maximum_adjustment_amount = $this->get_maximum_adjustment_amount();
		$adjustment_amount         = \YAYDP\Helper\YAYDP_Pricing_Helper::calculate_adjustment_amount( $item_price, $pricing_type, $pricing_value, $maximum_adjustment_amount );
		return $adjustment_amount;
	}

	/**
	 * Calculate the discount amount per item unit
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_discount_amount_per_item( $item ) {
		$item_price        = $item->get_price();
		$adjustment_amount = $this->get_adjustment_amount( $item );
		if ( \yaydp_is_flat_pricing_type( $this->get_pricing_type() ) ) {
			return max( 0, $item_price - $adjustment_amount );
		}
		return min( $item_price, $adjustment_amount );
	}

	/**
	 * Calculate discount value per item unit
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_discount_value_per_item( $item ) {
		$item_price   = $item->get_price();
		$pricing_type = $this->get_pricing_type();
		if ( \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
			return $this->get_pricing_value();
		}
		$adjustment_amount = $this->get_adjustment_amount( $item );
		if ( \yaydp_is_flat_pricing_type( $pricing_type ) ) {
			return max( 0, $item_price - $adjustment_amount );
		}
		return min( $item_price, $adjustment_amount );
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

	/**
	 * Check whether given cart match rule conditions
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function check_conditions( $cart ) {
		// Note: Lock in LITE version.
		return true;
	}

}
