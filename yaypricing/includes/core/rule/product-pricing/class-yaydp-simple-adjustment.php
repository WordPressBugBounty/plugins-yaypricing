<?php
/**
 * This class handle Simple Adjustment rule
 *
 * @package YayPricing\Rule\ProductPricing
 */

namespace YAYDP\Core\Rule\Product_Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Simple_Adjustment extends \YAYDP\Abstracts\YAYDP_Product_Pricing_Rule {

	/**
	 * Return type of rule
	 *
	 * @override
	 */
	public function get_type() {
		return 'simple_adjustment';
	}

	/**
	 * Calculate all possible adjustment created by the rule.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart ) {
		$discountable_items = array();
		foreach ( $cart->get_items() as $item ) {
			$product = $item->get_product();
			if ( parent::can_apply_adjustment( $product, null, 'any', $item->get_key() ) ) {
				$discountable_items[] = $item;
			}
		}
		if ( empty( $discountable_items ) ) {
			return null;
		}

		return array(
			'rule'               => $this,
			'discountable_items' => $discountable_items,
		);
	}

	/**
	 * Calculate the discount and apply modifier to the cart item.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item.
	 */
	public function discount_item( \YAYDP\Core\YAYDP_Cart_Item $item ) {
		$discount_amount  = parent::get_discount_amount_per_item( $item );
		$item_price       = $item->get_price();
		$discounted_price = max( 0, $item_price - $discount_amount );
		$item->set_price( $discounted_price );
		$item_quantity = $item->get_quantity();
		$modifier      = array(
			'rule'              => $this,
			'modify_quantity'   => $item_quantity,
			'discount_per_unit' => $discount_amount,
			'item'              => $item,
		);
		$item->add_modifier( $modifier );
	}

	/**
	 * Get minimim discount information that can apply to the product
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_min_discount( $product ) {
		if ( ! empty( $this->get_conditions() ) ) {
			return array(
				'pricing_value' => 0,
				'pricing_type'  => 'fixed_discount',
				'maximum'       => 0,
			);
		}
		return array(
			'pricing_value' => $this->get_pricing_value(),
			'pricing_type'  => $this->get_pricing_type(),
			'maximum'       => $this->get_maximum_adjustment_amount(),
		);
	}

	/**
	 * Get maximum discount information that can apply to the product
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_max_discount( $product ) {
		return array(
			'pricing_value' => $this->get_pricing_value(),
			'pricing_type'  => $this->get_pricing_type(),
			'maximum'       => $this->get_maximum_adjustment_amount(),
		);
	}

	/**
	 * Calculate all encouragements can be created by rule ( include condition encouragements )
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 * @param null|\WC_Product       $product Product.
	 */
	public function get_encouragements( \YAYDP\Core\YAYDP_Cart $cart, $product = null ) {
		$conditions_encouragements = parent::get_conditions_encouragements( $cart );
		if ( empty( $conditions_encouragements ) ) {
			return null;
		}
		foreach ( $cart->get_items() as $item ) {
			if ( $item->is_extra() ) {
				continue;
			}

			$item_product = $item->get_product();
			if ( ! is_null( $product ) ) {
				if ( \yaydp_is_variable_product( $product ) ) {
					if ( ! in_array( $item_product->get_id(), $product->get_children(), true ) ) {
						continue;
					}
				} else {
					if ( $product->get_id() !== $item_product->get_id() ) {
						continue;
					}
				}
			}

			if ( $this->can_apply_adjustment( $item_product, null, 'any', $item->get_key() ) ) {
				return new \YAYDP\Core\Encouragement\YAYDP_Product_Pricing_Encouragement(
					array(
						'item'                      => $item,
						'rule'                      => $this,
						'conditions_encouragements' => $conditions_encouragements,
					)
				);
			}
		}
		return null;
	}
}
