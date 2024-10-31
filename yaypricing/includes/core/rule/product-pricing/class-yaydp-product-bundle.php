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

	/**
	 * Get the type of the rule.
	 *
	 * @override
	 */
	public function get_type() {
		return 'product_bundle';
	}

	/**
	 * Retrieves purchase quantity.
	 *
	 * @return int
	 */
	public function get_purchase_quantity() {
		return ! empty( $this->data['pricing']['buy_quantity'] ) ? $this->data['pricing']['buy_quantity'] : 1;
	}

	/**
	 * Check whether discount is for group of products.
	 *
	 * @return bool
	 */
	public function for_group() {
		return isset( $this->data['pricing']['for_group'] ) ? $this->data['pricing']['for_group'] : false;
	}

	/**
	 * Calculate all possible adjustments created by the rule.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart The current cart.
	 */
	public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart ) {
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
		$accumulator    = 0;
		$filtered_items = array();

		foreach ( $cart_items as $item ) {
			if ( $accumulator >= $purchase_quantity ) {
				break;
			}

			$product = $item->get_product();
			if ( parent::can_apply_adjustment( $product, null, 'any', $item->get_key() ) ) {
				$accumulator     += (int) $item->get_quantity();
				$filtered_items[] = $item;
			}
		}
		return $filtered_items;
	}

	/**
	 * Calculate the discount and apply the modifier to the cart item.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item The current cart item.
	 */
	public function discount_item( \YAYDP\Core\YAYDP_Cart_Item $item ) {}

	/**
	 * Calculate the adjustment amount for item.
	 *
	 * @param float $total_items_price Discount items total price.
	 */
	public function get_bundled_products_adjustment_amount( $total_items_price ) {
		$pricing_type              = $this->get_pricing_type();
		$pricing_value             = $this->get_pricing_value();
		$maximum_adjustment_amount = $this->get_maximum_adjustment_amount();
		$adjustment_amount         = \YAYDP\Helper\YAYDP_Pricing_Helper::calculate_adjustment_amount( $total_items_price, $pricing_type, $pricing_value, $maximum_adjustment_amount );
		return $adjustment_amount;
	}

	/**
	 * Calculate the discount and apply the modifier to the cart item.
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment The adjustment.
	 */
	public function discount_for_product_bundle_item( $adjustment ) {
		$discountable_items = $adjustment->get_discountable_items();
		$purchase_quantity  = $this->get_purchase_quantity();
		$for_group          = $this->for_group();

		if ( $for_group ) {
			$total_discountable_items_price = $this->calculate_total_discountable_items_price( $discountable_items, $purchase_quantity );
			$total_discount_amount          = $this->get_bundled_products_adjustment_amount( $total_discountable_items_price );
			$remaining_discount             = ! \yaydp_is_flat_pricing_type( $this->get_pricing_type() ) ? $total_discount_amount : $total_discountable_items_price - $total_discount_amount;

			foreach ( $discountable_items as $item ) {
				$item_price                    = $item->get_price();
				$item_quantity                 = $item->get_quantity();
				$purchase_quantity            -= $item_quantity;
				$discountable_quantity         = $purchase_quantity >= 0 ? $item_quantity : $item_quantity + $purchase_quantity;
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

				$modifier = array(
					'rule'              => $this,
					'modify_quantity'   => $discountable_quantity,
					'discount_per_unit' => $discount_per_unit,
					'item'              => $item,
				);

				$item->set_price( $price );
				$item->add_modifier( $modifier );
			}
		} else {
			foreach ( $discountable_items as $item ) {
				$discount_amount       = parent::get_discount_amount_per_item( $item );
				$item_price            = $item->get_price();
				$item_quantity         = $item->get_quantity();
				$discounted_price      = max( 0, $item_price - $discount_amount );
				$purchase_quantity    -= $item_quantity;
				$discountable_quantity = $purchase_quantity >= 0 ? $item_quantity : $item_quantity + $purchase_quantity;
				$normal_item_quantity  = $item_quantity - $discountable_quantity;
				$price                 = ( ( $item_price * $normal_item_quantity ) + ( $discounted_price * $discountable_quantity ) ) / $item_quantity;
				$modifier              = array(
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

	/**
	 * Calculate total discountable items price.
	 */
	public function calculate_total_discountable_items_price( $discountable_items, $purchase_quantity ) {
		$discount_items_remain = $purchase_quantity;
		$total                 = 0;

		foreach ( $discountable_items as $item ) {
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
	 * Get information on the maximum discount that can be applied to the product.
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
	 * @param \YAYDP\Core\YAYDP_Cart $cart The current cart.
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
