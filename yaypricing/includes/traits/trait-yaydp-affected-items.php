<?php
/**
 * Affected Items Trait
 *
 * Shared by product pricing rules that let the admin choose which of the
 * matched cart units actually receive the discount, driven by
 * `pricing.affected_items = { type, quantity, effect_type }`:
 *
 *   type        whole_bundle | single_item | filter ("Limited items")
 *   quantity    unit cap used by the filter type
 *   effect_type lowest_price | highest_price | default (cart order)
 *
 * The trait owns the accessors, the effect-type ranking and the per-unit cap
 * allocator. Each using class supplies get_pricing_type(), the discount amount
 * per unit, and decides which of its own branches the cap applies to.
 *
 * @package YayPricing\Traits
 */

namespace YAYDP\Traits;

defined( 'ABSPATH' ) || exit;

trait YAYDP_Affected_Items {

	/**
	 * Affected items mode. Rules without the key behave as "each matching item".
	 *
	 * @since 3.4.2
	 */
	public function get_affected_items_type() {
		return $this->data['pricing']['affected_items']['type'] ?? 'single_item';
	}

	/**
	 * Get affected items data
	 *
	 * @since 3.4.2
	 */
	public function get_affected_items_data() {
		return isset( $this->data['pricing']['affected_items'] ) ? $this->data['pricing']['affected_items'] : array(
			'type'        => 'single_item',
			'quantity'    => 1,
			'effect_type' => 'lowest_price',
		);
	}

	/**
	 * Whether only a capped number of matched units receive the discount.
	 */
	public function is_limited_items() {
		return 'filter' === $this->get_affected_items_type();
	}

	/**
	 * Unit cap for the limited-items mode, never above $cap.
	 *
	 * @param int $cap Upper bound (e.g. the bundle purchase quantity).
	 */
	public function get_limited_quantity( $cap ) {
		return min( $cap, $this->get_affected_items_data()['quantity'] ?? 1 );
	}

	/**
	 * Resolve the affected-items effect type into a sort direction.
	 *
	 * @return string|null 'asc' for lowest_price, 'desc' for highest_price,
	 *                     null for cart order (items keep their cart position).
	 */
	public function get_effect_sort_order() {
		$effect_type = $this->get_affected_items_data()['effect_type'] ?? 'lowest_price';
		if ( 'highest_price' === $effect_type ) {
			return 'desc';
		}
		if ( 'lowest_price' === $effect_type ) {
			return 'asc';
		}
		return null;
	}

	/**
	 * Rank candidate items by the affected-items effect type so the units consumed
	 * downstream are the cheapest (or costliest) ones rather than cart order.
	 * Free type ranks by initial price (see sort_items_by_initial_price).
	 * Cart order (effect_type 'default') leaves the list untouched.
	 */
	protected function sort_items_by_effect_type( &$items ) {
		$sort_order = $this->get_effect_sort_order();
		if ( null === $sort_order ) {
			return;
		}
		if ( 'free' === $this->get_pricing_type() ) {
			$this->sort_items_by_initial_price( $items, $sort_order );
		} else {
			$this->sort_items_by_price( $items, $sort_order );
		}
	}

	/**
	 * Sort list by live unit price.
	 */
	public function sort_items_by_price( &$items, $order = 'asc' ) {
		usort(
			$items,
			function( $a, $b ) use ( $order ) {
				// Live cart-item price, not the static product price, so items already
				// discounted by another rule are compared fairly.
				$check = (float) $a->get_price() < (float) $b->get_price() ? -1 : 1;
				return 'asc' === $order ? $check : ( $check * -1 );
			}
		);
	}

	/**
	 * Sort items by their initial (original) unit price.
	 *
	 * Used by the free pricing type: units freed by an earlier rule have a live
	 * price near 0, so ranking by live price would keep re-selecting them; the
	 * initial price is the true cost of giving a unit away.
	 */
	public function sort_items_by_initial_price( &$items, $order = 'asc' ) {
		usort(
			$items,
			function( $a, $b ) use ( $order ) {
				$check = (float) $a->get_initial_price() < (float) $b->get_initial_price() ? -1 : 1;
				return 'asc' === $order ? $check : ( $check * -1 );
			}
		);
	}

	/**
	 * Discount at most $limit units, walking $items in their current order.
	 *
	 * A line that is only partly covered keeps one blended unit price so the
	 * WooCommerce line total is right, and records a modifier whose
	 * modify_quantity is the covered units.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item[] $items             Already ranked (see sort_items_by_effect_type).
	 * @param int                           $limit             Maximum units to discount.
	 * @param callable                      $discount_per_unit fn( $item ) => discount amount for one unit.
	 */
	protected function discount_limited_units( array $items, $limit, callable $discount_per_unit ) {
		foreach ( $items as $item ) {
			if ( $limit < 1 ) {
				continue;
			}
			$discount_amount       = $discount_per_unit( $item );
			$item_price            = $item->get_price();
			$item_quantity         = $item->get_quantity();
			$limit                -= $item_quantity;
			$discountable_quantity = $limit >= 0 ? $item_quantity : $item_quantity + $limit;

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
	}
}
