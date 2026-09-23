<?php
/**
 * Managing product pricing adjustments
 *
 * @package YayPricing\Abstracts
 *
 * @since 2.4
 */

namespace YAYDP\Core\Adjustments;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Adjustments extends \YAYDP\Abstracts\YAYDP_Adjustments {

	/**
	 * Contains current cart
	 */
	protected $cart = null;

	/**
	 * Constructor
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function __construct( $cart ) {
		$this->cart = $cart;
	}

	/**
	 * Collect adjustment
	 *
	 * @override
	 */
	public function collect() {
		$running_rules = \yaydp_get_running_product_pricing_rules();
		foreach ( $running_rules as $rule ) {
			$adjustment = $rule->create_possible_adjustment_from_cart( $this->cart );
			if ( ! empty( $adjustment ) ) {
				$adjustment_instance = new \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment( $adjustment, $this->cart );
				if ( ! $adjustment_instance->check_conditions() ) {
					continue;
				}

				$this->marks_discounted_products( $adjustment );
				parent::add_adjustment( $adjustment_instance );
			}
		}

		if ( empty( $this->adjustments ) ) {
			return;
		}

		if ( \yaydp_product_pricing_is_applied_to_maximum_amount_per_order() || \yaydp_product_pricing_is_applied_to_maximum_amount_per_item() ) {
			parent::sort_by_desc_amount();
		}

		if ( \yaydp_product_pricing_is_applied_to_minimum_amount_per_order() || \yaydp_product_pricing_is_applied_to_minimum_amount_per_item() ) {
			parent::sort_by_asc_amount();
		}

	}

	/**
	 * Apply adjustments
	 *
	 * @override
	 */
	public function apply() {
		if ( \yaydp_product_pricing_is_applied_to_maximum_amount_per_item() ) {
			$this->apply_per_item_mode( 'max' );
			return;
		}

		if ( \yaydp_product_pricing_is_applied_to_minimum_amount_per_item() ) {
			$this->apply_per_item_mode( 'min' );
			return;
		}

		foreach ( $this->adjustments as $adjustment ) {
			if ( ! $adjustment->check_conditions() ) {
				continue;
			}

			$adjustment->apply_to_cart();

			if ( \yaydp_product_pricing_is_applied_first_rules() ) {
				break;
			}

			if ( \yaydp_product_pricing_is_applied_to_maximum_amount_per_order() ) {
				break;
			}

			if ( \yaydp_product_pricing_is_applied_to_minimum_amount_per_order() ) {
				break;
			}
		}
	}

	/**
	 * Apply adjustments in per-item mode (max or min discount per item)
	 * For each cart item, apply the rule that gives the best discount for that specific item.
	 *
	 * @param string $mode Either 'max' or 'min'.
	 * @since 3.5.4
	 */
	protected function apply_per_item_mode( $mode ) {
		$item_to_adjustment_map = array();

		foreach ( $this->adjustments as $adjustment ) {
			if ( ! $adjustment->check_conditions() ) {
				continue;
			}

			$discountable_items = $adjustment->get_discountable_items();
			foreach ( $discountable_items as $item ) {
				$item_key            = $item->get_key();
				$discount_per_item   = $adjustment->get_rule()->get_discount_amount_per_item( $item );

				if ( ! isset( $item_to_adjustment_map[ $item_key ] ) ) {
					$item_to_adjustment_map[ $item_key ] = array(
						'adjustment'        => $adjustment,
						'discount_per_item' => $discount_per_item,
						'item'              => $item,
					);
				} else {
					$current_discount = $item_to_adjustment_map[ $item_key ]['discount_per_item'];
					$is_better        = ( 'max' === $mode && $discount_per_item > $current_discount )
										|| ( 'min' === $mode && $discount_per_item < $current_discount && $discount_per_item > 0 );

					if ( $is_better ) {
						$item_to_adjustment_map[ $item_key ] = array(
							'adjustment'        => $adjustment,
							'discount_per_item' => $discount_per_item,
							'item'              => $item,
						);
					}
				}
			}
		}

		$adjustment_to_items_map = array();
		foreach ( $item_to_adjustment_map as $item_key => $data ) {
			$adjustment_id = spl_object_hash( $data['adjustment'] );
			if ( ! isset( $adjustment_to_items_map[ $adjustment_id ] ) ) {
				$adjustment_to_items_map[ $adjustment_id ] = array(
					'adjustment' => $data['adjustment'],
					'items'      => array(),
				);
			}
			$adjustment_to_items_map[ $adjustment_id ]['items'][] = $data['item'];
		}

		foreach ( $adjustment_to_items_map as $data ) {
			$adjustment = $data['adjustment'];
			$items      = $data['items'];

			$adjustment->set_discountable_items_for_per_item_mode( $items );
			$adjustment->apply_to_cart();
		}
	}

	/**
	 * Get cart
	 */
	public function get_cart() {
		return $this->cart;
	}

	public function marks_discounted_products( $adjustment ) {

		if ( ! \yaydp_product_pricing_is_applied_to_non_discount_product() ) {
			return;
		}

		$discountable_items = $adjustment['discountable_items'] ?? array();

		foreach ( $discountable_items as $item ) {
			$product = $item->get_product();
			\YAYDP\Core\Discounted_Products\YAYDP_Discounted_Products::get_instance()->add_product( $product );
		}
	}

}
