<?php

/**
 * This class handle Product Bundle rule.
 *
 * @package YayPricing\Rule\ProductPricing
 */

namespace YAYDP\Core\Rule\Product_Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Product_Bundle extends \YAYDP\Abstracts\YAYDP_Product_Pricing_Rule {

	use \YAYDP\Traits\YAYDP_Affected_Items;

	/**
	 * Get the type of the rule.
	 *
	 * @override
	 */
	public function get_type() {
		return 'product_bundle';
	}

	public function find_discount_time( $items ) {

		$items_quantity = 0;
		foreach ( $items as $item ) {
			$items_quantity += $item->get_quantity();
		}

		if ( 0 === $items_quantity ) {
			return 0;
		}

		$purchase_quantity = $this->get_purchase_quantity();

		if ( ! $this->is_using_formula() ) {
			return $items_quantity >= $purchase_quantity ? 1 : 0;
		}

		$result = 0;
		for ( $i = 1; $i <= $items_quantity; $i++ ) {
			$purchase_quantity = $this->exec_formula( $this->get_quantity_formula(), $i, PHP_INT_MAX );
			if ( $purchase_quantity > $items_quantity ) {
				return $result;
			}
			$result = $i;
		}
		return $result;
	}

	/**
	 * Retrieves purchase quantity.
	 *
	 * @return int
	 */
	public function get_purchase_quantity( $items = array() ) {
		$buy_quantity = ! empty( $this->data['pricing']['buy_quantity'] ) ? $this->data['pricing']['buy_quantity'] : 1;
		if ( ! $this->is_using_formula() ) {
			return $buy_quantity;
		}

		try {

			$discount_time = $this->find_discount_time( $items );
			return $this->exec_formula( $this->get_quantity_formula(), $discount_time, PHP_INT_MAX );

		} catch ( \Error $error ) {
			$by_pass = true;
		}
		return PHP_INT_MAX;
	}

	/**
	 * Affected items mode, with the pre-3.4.2 `for_group` flag as fallback.
	 * Overrides the trait default for rules saved before affected_items existed.
	 *
	 * @since 3.4.2
	 */
	public function get_affected_items_type() {
		return isset( $this->data['pricing']['affected_items']['type'] ) ? $this->data['pricing']['affected_items']['type'] : ( empty( $this->data['pricing']['for_group'] ) ? 'single_item' : 'whole_bundle' );
	}

	/**
	 * Formulas are a pro feature; a stored using_formula flag is ignored and the
	 * rule applies its plain pricing value.
	 */
	public function is_using_formula() {
		return false;
	}

	public function get_quantity_formula() {
		return $this->data['pricing']['quantity_formula'] ?? PHP_INT_MAX;

	}

	public function get_pricing_formula() {
		return $this->data['pricing']['pricing_formula'] ?? 0;
	}

	/**
	 * Calculate all possible adjustments created by the rule.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart The current cart.
	 */
	public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart ) {
		if ( ! $this->is_using_formula() ) {
			$discountable_items       = array();
			$purchase_quantity        = $this->get_purchase_quantity();
			$discountable_items       = $this->discountable_items_filter( $cart->get_items(), $purchase_quantity );
			$discountable_items_count = array_reduce(
				$discountable_items,
				function ( $accumulator, $item ) {
					return $accumulator += $item->get_quantity();
				}
			);

			if ( $purchase_quantity > $discountable_items_count ) {
				return null;
			}
		} else {
			$discountable_items = $this->discountable_items_filter_using_formula( $cart->get_items() );
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
	 * Determine which cart item is discountable.
	 *
	 * @param array $cart_items The array of items in the current cart.
	 * @param int $purchase_quantity The quantity of products needed to reach the discount.
	 *
	 * @return array
	 */
	public function discountable_items_filter( $cart_items, $purchase_quantity ) {
		// Free type keeps every eligible item once the purchase threshold is met: the
		// free budget is enforced later in apply_free_items(), and truncating here at
		// purchase_quantity could hand a later free rule the exact lines an earlier
		// free rule already consumed, leaving it nothing to allocate even though
		// fresh units exist elsewhere in the cart.
		$keep_all_candidates = 'free' === $this->get_pricing_type();

		$eligible_items = array();
		foreach ( $cart_items as $item ) {
			// Null filters => the rule's own Buy filters and match type.
			if ( parent::can_apply_adjustment( $item->get_product(), null, 'any', $item->get_key() ) ) {
				$eligible_items[] = $item;
			}
		}

		// Collect the full eligible set first, then rank it, so the items actually
		// consumed to satisfy purchase_quantity follow the configured effect type.
		$this->sort_items_by_effect_type( $eligible_items );

		$accumulator    = 0;
		$filtered_items = array();
		foreach ( $eligible_items as $item ) {
			if ( ! $keep_all_candidates && $accumulator >= $purchase_quantity ) {
				break;
			}
			$accumulator     += (int) $item->get_quantity();
			$filtered_items[] = $item;
		}

		if ( $purchase_quantity <= 0 || $accumulator < $purchase_quantity ) {
			return array();
		}

		return $filtered_items;
	}

	public function discountable_items_filter_using_formula( $cart_items ) {
		$accumulator = 0;
		$check_items = array();
		foreach ( $cart_items as $item ) {
			// Null filters => the rule's own Buy filters and match type.
			if ( parent::can_apply_adjustment( $item->get_product(), null, 'any', $item->get_key() ) ) {
				$accumulator  += (int) $item->get_quantity();
				$check_items[] = $item;
			}
		}

		$purchase_quantity = $this->get_purchase_quantity( $check_items );

		if ( $purchase_quantity <= 0 || $accumulator < $purchase_quantity ) {
			return array();
		}

		$this->sort_items_by_effect_type( $check_items );
		return $check_items;
	}

	/**
	 * Calculate the discount and apply the modifier to the cart item.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item.
	 */
	public function discount_item( \YAYDP\Core\YAYDP_Cart_Item $item ) {}

	/**
	 * Pricing value, taken from the formula when enabled. `{n}` in the formula
	 * is the number of complete bundles.
	 */
	private function get_bundle_pricing_value( $discount_time = 0 ) {
		if ( ! $this->is_using_formula() ) {
			return $this->get_pricing_value();
		}
		return $this->exec_formula( $this->get_pricing_formula(), $discount_time, \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::formula_neutral_value( $this->get_pricing_type() ) );
	}

	/**
	 * Discount to spread across a whole bundle, computed on the pooled price of
	 * its discountable units (a percentage of the pool, a fixed amount, or the
	 * drop to a flat pool price).
	 *
	 * @param float $total_items_price Pooled price of the discountable units.
	 * @param int   $discount_time     Number of complete bundles (formula input).
	 */
	public function get_bundled_products_discount( $total_items_price, $discount_time = 0 ) {
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::adjustment_per_unit( $this->get_pricing_type(), $total_items_price, $this->get_bundle_pricing_value( $discount_time ), $this->get_maximum_adjustment_amount() );
	}

	/**
	 * Calculate discount amount per item unit
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_discount_amount_per_item( $item, $discount_time = 0 ) {
		// Discount is computed off the item's original unit price, not the live
		// (possibly already-discounted-by-another-rule) price, so stacked bundle
		// rules each discount from the true unit price instead of compounding on an
		// already-reduced average.
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::discount_per_item( $this->get_pricing_type(), $item->get_initial_price(), $this->get_bundle_pricing_value( $discount_time ), $this->get_maximum_adjustment_amount() );
	}

	/**
	 * Legacy per-item adjustment amount (see YAYDP_Pricing_Helper). Not used by the
	 * engine any more; kept so external callers keep the same figures.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Pricing_Type_Registry::adjustment_per_unit().
	 */
	public function get_adjustment_amount( $item, $discount_time = 0 ) {
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::legacy_adjustment_amount( $this->get_pricing_type(), $item->get_initial_price(), $this->get_bundle_pricing_value( $discount_time ), $this->get_maximum_adjustment_amount() );
	}

	/**
	 * Legacy pooled adjustment amount. Not used by the engine any more.
	 *
	 * @deprecated 3.5.8 Use get_bundled_products_discount().
	 */
	public function get_bundled_products_adjustment_amount( $total_items_price, $discount_time = 0 ) {
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::legacy_adjustment_amount( $this->get_pricing_type(), $total_items_price, $this->get_bundle_pricing_value( $discount_time ), $this->get_maximum_adjustment_amount() );
	}

	/**
	 * Returns the highest unit price among the given bundle items.
	 *
	 * Used by the `highest_item_price` pricing type to derive the re-price target.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item[] $items Bundle items.
	 *
	 * @return float
	 */
	private function get_max_unit_price_in_bundle( $items ) {
		$max = 0;
		foreach ( $items as $item ) {
			$price = (float) $item->get_price();
			if ( $price > $max ) {
				$max = $price;
			}
		}
		return $max;
	}

	/**
	 * Per-unit target price for the set-price bundle types.
	 * - fixed_item_price: the admin-set price (uncapped; 0 => free).
	 * - highest_item_price: highest unit price in the set x value/100. An empty
	 *   value reads as 0 (free) — get_pricing_value() normalises empty to 0 — while
	 *   a non-numeric value falls back to 100 % (match the highest).
	 *
	 * Formula-based rules are not supported for these types: the saved value is read directly.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item[] $items Bundle items (whole bundle or a single filter group).
	 *
	 * @return float
	 */
	private function get_bundle_reprice_target( $items ) {
		$pricing_type = $this->get_pricing_type();
		$raw_value    = $this->get_pricing_value();
		$value        = ( 'highest_item_price' === $pricing_type && ! is_numeric( $raw_value ) ) ? 100 : $raw_value;
		$context      = array( 'group_max_price' => $this->get_max_unit_price_in_bundle( $items ) );
		return (float) \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::resolve( $pricing_type, 0, $value, null, $context );
	}

	/**
	 * Re-price every discountable unit in the set to the given target price.
	 *
	 * Shared by the whole_bundle and filter branches of the uniform-item-price types
	 * (`highest_item_price` and `fixed_item_price`).
	 * Bypasses the proportional `remaining_discount` distribution. The per-unit
	 * adjustment is `item_price - target`, kept signed (NO `max(0, ...)` clamp) so a
	 * target above the item price raises the price via the negative-adjustment plumbing.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item[] $items            Items to re-price.
	 * @param int                           $purchase_quantity Quantity budget to re-price.
	 * @param float                         $target           Target unit price.
	 */
	private function reprice_items_to_target( $items, $purchase_quantity, $target ) {
		foreach ( $items as $item ) {
			$item_price            = $item->get_price();
			$item_quantity         = $item->get_quantity();
			$purchase_quantity    -= $item_quantity;
			$discountable_quantity = $purchase_quantity >= 0 ? $item_quantity : $item_quantity + $purchase_quantity;

			if ( $discountable_quantity < 1 ) {
				continue;
			}

			$discount_per_unit    = $item_price - $target; // Negative => price rises. Not clamped.
			$normal_item_quantity = $item_quantity - $discountable_quantity;
			$price                = ( ( $item_price * $normal_item_quantity ) + ( $target * $discountable_quantity ) ) / $item_quantity;

			// Target equals the current price (e.g. the highest item itself at value=100): nothing to change.
			if ( empty( $discount_per_unit ) ) {
				continue;
			}

			$modifier = array(
				'rule'              => $this,
				'modify_quantity'   => $discountable_quantity,
				'discount_per_unit' => $discount_per_unit,
				'item'              => $item,
			);

			$item->set_price( $price );
			$item->add_modifier( $modifier );
		}
	}

	/**
	 * Give away up to $free_quantity units from the given items (free pricing type).
	 *
	 * Each freed unit is discounted at its full INITIAL price (pricing value,
	 * maximum amount and pricing formula are ignored for this type), and units
	 * already freed by an earlier free rule are skipped so stacked free rules
	 * always free distinct units.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item[] $items         Candidate items, pre-sorted by initial price.
	 * @param int                           $free_quantity Number of units to free.
	 */
	private function apply_free_items( $items, $free_quantity ) {
		foreach ( $items as $item ) {
			if ( $free_quantity < 1 ) {
				break;
			}

			$item_quantity = $item->get_quantity();
			if ( $item_quantity < 1 ) {
				continue;
			}

			// Units consumed by earlier free rules cannot be freed twice.
			$available_quantity = $item_quantity - $item->get_freed_quantity();
			if ( $available_quantity < 1 ) {
				continue;
			}

			$freeable_quantity = min( $available_quantity, $free_quantity );
			// Full original unit price: a freed unit costs the customer nothing even
			// if another (non-free) rule already discounted the line. Mixed stacking
			// (e.g. 50% rule + free rule) stays deterministic because this basis never
			// depends on the live blended price.
			$discount_per_unit = $item->get_initial_price();

			// A genuinely zero-priced product has nothing to give away; skip without
			// consuming the free budget.
			if ( empty( $discount_per_unit ) ) {
				continue;
			}

			// Same total-based recombination as the percentage branches: reduce the
			// line's current (already blended) total by this rule's contribution.
			$total_before = $item->get_price() * $item_quantity;
			$total_after  = max( 0, $total_before - ( $discount_per_unit * $freeable_quantity ) );
			$price        = $total_after / $item_quantity;

			$modifier = array(
				'rule'              => $this,
				'modify_quantity'   => $freeable_quantity,
				'discount_per_unit' => $discount_per_unit,
				'item'              => $item,
			);

			$item->set_price( $price );
			$item->add_modifier( $modifier );

			$free_quantity -= $freeable_quantity;
		}
	}

	/**
	 * Calculate the discount and apply the modifier to the cart item.
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment The adjustment.
	 */
	public function discount_for_product_bundle_item( $adjustment ) {
		$discountable_items = $adjustment->get_discountable_items();
		$discount_time      = $this->find_discount_time( $discountable_items );

		if ( 0 == $discount_time ) {
			return;
		}

		$purchase_quantity   = $this->get_purchase_quantity( $discountable_items );
		$affected_items_type = $this->get_affected_items_type();

		$affect_to_whole_bundle = 'whole_bundle' === $affected_items_type;

		if ( 'free' === $this->get_pricing_type() ) {
			if ( $affect_to_whole_bundle ) {
				// Entire matched quantity becomes free. Routed through the same
				// tracking allocator (not reprice_items_to_target) so stacked free
				// rules see these units as consumed.
				$this->sort_items_by_effect_type( $discountable_items );
				$this->apply_free_items( $discountable_items, $purchase_quantity );
			} elseif ( 'single_item' === $affected_items_type ) {
				$free_quantity = $this->get_limited_quantity( $purchase_quantity );
				$this->sort_items_by_effect_type( $discountable_items );
				$this->apply_free_items( $discountable_items, $free_quantity );
			} else {
				$free_quantity = $this->get_limited_quantity( $purchase_quantity );
				$this->sort_items_by_effect_type( $discountable_items );
				$this->apply_free_items( $discountable_items, $free_quantity );
			}
			return;
		}

		if ( $affect_to_whole_bundle ) {
			if ( 0 === \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::sign( $this->get_pricing_type() ) ) {
				$target = $this->get_bundle_reprice_target( $discountable_items );
				$this->reprice_items_to_target( $discountable_items, $purchase_quantity, $target );
				return;
			}

			$total_discountable_items_price = $this->calculate_total_discountable_items_price( $discountable_items, $purchase_quantity );
			$remaining_discount             = $this->get_bundled_products_discount( $total_discountable_items_price, $discount_time );

			foreach ( $discountable_items as $item ) {
				$item_price            = $item->get_price();
				$item_quantity         = $item->get_quantity();
				$purchase_quantity    -= $item_quantity;
				$discountable_quantity = $purchase_quantity >= 0 ? $item_quantity : $item_quantity + $purchase_quantity;

				if ( $discountable_quantity < 1 ) {
					continue;
				}

				$total_discountable_item_price = $item_price * $discountable_quantity;

				if ( $remaining_discount > $total_discountable_item_price ) {
					$discounted_price    = 0;
					$remaining_discount -= $total_discountable_item_price;
				} else {
					$discounted_price   = $total_discountable_item_price - $remaining_discount;
					$remaining_discount = 0;
				}

				$discount_per_item    = $item_price - ( $discounted_price / $discountable_quantity );
				$discount_per_unit    = $discount_per_item;
				$normal_item_quantity = $item_quantity - $discountable_quantity;
				$price                = ( ( $item_price * $normal_item_quantity ) + $discounted_price ) / $item_quantity;

				if ( empty( $discountable_quantity ) || empty( $discount_per_unit ) ) {
					continue;
				}

				$modifier = array(
					'rule'              => $this,
					'modify_quantity'   => $discountable_quantity,
					'discount_per_unit' => $discount_per_unit,
					'item'              => $item,
				);

				$item->set_price( $price );
				$item->add_modifier( $modifier );
			}
		} elseif ( 'single_item' === $affected_items_type ) {
			// Uniform-item-price types (highest_item_price / fixed_item_price) are unsupported for single_item => no-op. See plans.
			if ( 0 === \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::sign( $this->get_pricing_type() ) ) {
				return;
			}
			foreach ( $discountable_items as $item ) {
				if ( $purchase_quantity < 1 ) {
					continue;
				}
				$discount_amount       = $this->get_discount_amount_per_item( $item, $discount_time );
				$item_price            = $item->get_price();
				$item_quantity         = $item->get_quantity();
				$purchase_quantity    -= $item_quantity;
				$discountable_quantity = $purchase_quantity >= 0 ? $item_quantity : $item_quantity + $purchase_quantity;

				if ( empty( $discountable_quantity ) || empty( $discount_amount ) ) {
					continue;
				}

				// Reduce the line's current (already blended) total by this rule's
				// contribution, then re-derive the average — rather than recombining
				// via `$item_price * $normal_item_quantity`, which would silently
				// re-dilute a prior rule's discount across the "untouched" units.
				$total_before = $item_price * $item_quantity;
				$total_after  = max( 0, $total_before - ( $discount_amount * $discountable_quantity ) );
				$price        = $total_after / $item_quantity;

				$modifier = array(
					'rule'              => $this,
					'modify_quantity'   => $discountable_quantity,
					'discount_per_unit' => $discount_amount,
					'item'              => $item,
				);

				$item->set_price( $price );
				$item->add_modifier( $modifier );
			}
		} else {
			$this->sort_items_by_effect_type( $discountable_items );

			$limited_quantity = $this->get_limited_quantity( $purchase_quantity );

			if ( 0 === \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::sign( $this->get_pricing_type() ) ) {
				$target = $this->get_bundle_reprice_target( $discountable_items );
				$this->reprice_items_to_target( $discountable_items, $limited_quantity, $target );
				return;
			}

			$this->discount_limited_units(
				$discountable_items,
				$limited_quantity,
				function ( $item ) use ( $discount_time ) {
					return $this->get_discount_amount_per_item( $item, $discount_time );
				}
			);
		}
	}

	/**
	 * Calculate total discountable items price.
	 */
	public function calculate_total_discountable_items_price( $discountable_items, $purchase_quantity ) {
		$discount_items_remain = $purchase_quantity;
		$total                 = 0;

		foreach ( $discountable_items as $item ) {
			if ( $discount_items_remain < 1 ) {
				continue;
			}
			$item_price             = $item->get_price();
			$item_quantity          = $item->get_quantity();
			$discount_items_remain -= $item_quantity;

			if ( $discount_items_remain >= 0 ) {
				$total += $item_price * $item_quantity;
			} else {
				$total += $item_price * ( $item_quantity + $discount_items_remain );
			}
		}

		return $total;
	}

	/**
	 * Get information on the minimum discount that can be applied to the product.
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_min_discount( $product ) {
		if ( ! empty( $this->get_conditions() ) ) {
			return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::zero_bounds();
		}
		// Smallest guaranteed discount: the formula evaluated for one bundle.
		return $this->get_display_bounds_for_bundles( 1 );
	}
	/**
	 * Get information on the maximum discount that can be applied to the product.
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_max_discount( $product ) {
		// Largest possible discount: the formula evaluated for an unbounded number of bundles.
		return $this->get_display_bounds_for_bundles( PHP_INT_MAX );
	}

	/**
	 * Sale-display triple for the rule's pricing at the given bundle count.
	 * Free is presented as an uncapped 100 % discount so prediction surfaces
	 * need no dedicated handling.
	 */
	protected function get_display_bounds_for_bundles( $discount_time ) {
		$pricing_type = $this->get_pricing_type();
		if ( 'free' === $pricing_type ) {
			return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::display_bounds( 'percentage_discount', 100, PHP_INT_MAX );
		}
		return \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::display_bounds( $pricing_type, $this->get_bundle_pricing_value( $discount_time ), $this->get_maximum_adjustment_amount() );
	}

	/**
	 * Calculate all encouragements can be created by rule ( include condition encouragements )
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 * @param null|\WC_Product       $product Product.
	 */
	public function get_encouragements( $cart, $product = null ) {
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
