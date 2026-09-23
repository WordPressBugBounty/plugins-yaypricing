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

	use \YAYDP\Traits\YAYDP_Affected_Items;

	/**
	 * Return type of rule
	 *
	 * @override
	 */
	public function get_type() {
		return 'simple_adjustment';
	}

	/**
	 * Formulas are a pro feature; a stored using_formula flag is ignored and the
	 * rule applies its plain pricing value.
	 */
	public function is_using_formula() {
		return false;
	}

	public function get_pricing_formula() {
		return $this->data['pricing']['pricing_formula'] ?? 0;
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
	 * Apply the rule to every discountable item of an adjustment.
	 *
	 * "Limited items" discounts at most affected_items.quantity units across the
	 * matched lines, ranked by effect type on live price; anything else (no key,
	 * single_item, or the shared new-rule default whole_bundle) discounts every
	 * matching unit. Product Fee inherits this class but always charges every
	 * unit, so it never takes the limited path.
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment The adjustment.
	 */
	public function discount_items( $adjustment ) {
		$items = $adjustment->get_discountable_items();

		if ( $this->is_limited_items() && ! \yaydp_is_product_fee( $this ) ) {
			$this->sort_items_by_effect_type( $items );
			$this->discount_limited_units(
				$items,
				$this->get_limited_quantity( PHP_INT_MAX ),
				array( $this, 'get_discount_amount_per_item' )
			);
			return;
		}

		foreach ( $items as $item ) {
			$this->discount_item( $item );
		}
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
	 * Rule-level pricing, with the value taken from the formula when enabled.
	 * `{n}` in the formula is the line quantity.
	 *
	 * @override
	 */
	public function get_item_pricing( $item ) {
		$pricing = parent::get_item_pricing( $item );
		if ( $this->is_using_formula() ) {
			$pricing['value'] = $this->exec_formula(
				$this->get_pricing_formula(),
				$item->get_quantity(),
				\YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::formula_neutral_value( $pricing['type'] )
			);
		}
		return $pricing;
	}

	/**
	 * Get the minimum discount information that can be applied to the product.
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_min_discount( $product ) {
		// Limited items: only some matched units get the discount, so nothing is
		// guaranteed per unit — same treatment as a rule gated by conditions.
		if ( ! empty( $this->get_conditions() ) || $this->is_limited_items() ) {
			return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::zero_bounds();
		}
		// Smallest guaranteed discount: the formula evaluated for a single unit.
		return $this->get_display_bounds_for_quantity( 1 );
	}

	/**
	 * Get maximum discount information that can apply to the product
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_max_discount( $product ) {
		// Largest possible discount: the formula evaluated for an unbounded quantity.
		return $this->get_display_bounds_for_quantity( PHP_INT_MAX );
	}

	/**
	 * Sale-display triple for the rule's pricing, with the formula (when enabled)
	 * evaluated at the given quantity.
	 */
	protected function get_display_bounds_for_quantity( $quantity ) {
		$pricing_type  = $this->get_pricing_type();
		$pricing_value = $this->is_using_formula()
			? $this->exec_formula( $this->get_pricing_formula(), $quantity, \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::formula_neutral_value( $pricing_type ) )
			: $this->get_pricing_value();
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::display_bounds( $pricing_type, $pricing_value, $this->get_maximum_adjustment_amount() );
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
			if ( ! empty( $product ) ) {
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
