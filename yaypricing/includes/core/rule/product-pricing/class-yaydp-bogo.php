<?php
/**
 * This class handle BOGO rule
 *
 * @package YayPricing\Rule\ProductPricing
 */

namespace YAYDP\Core\Rule\Product_Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Bogo extends \YAYDP\Abstracts\YAYDP_Product_Pricing_Rule {

	/**
	 * Return type of rule
	 *
	 * @override
	 */
	public function get_type() {
		return 'bogo';
	}

	/**
	 * Check whether rule will automatically add free item
	 */
	public function is_get_free_item() {
		$item_get_type = ! empty( $this->data['pricing']['item_get_type'] ) ? $this->data['pricing']['item_get_type'] : 'free';
		return 'free' === $item_get_type;
	}

	/**
	 * Check whether discount is repeated
	 */
	public function is_repeat() {
		return isset( $this->data['pricing']['repeat'] ) ? $this->data['pricing']['repeat'] : false;
	}

	/**
	 * Calculate all possible receive items
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment Adjustment.
	 */
	public function get_list_receive_items( $adjustment ) {
		$result                    = array();
		$is_repeat                 = $this->is_repeat();
		$buy_quantity              = $this->get_buy_quantity();
		$receive_quantity_per_unit = $this->get_receive_quantity_per_unit();
		$is_get_free_item          = $this->is_get_free_item();
		$cart                      = $adjustment->get_cart();
		foreach ( $adjustment->get_bought_cases() as $case ) {
			$item          = $case['item'];
			$product       = $item->get_product();
			$item_quantity = $item->get_quantity();

			if ( $is_get_free_item ) {
				$discount_time = floor( floatval( $item_quantity ) / floatval( $buy_quantity ) );
			} else {
				$total_buy_and_get_quantity_per_unit = $buy_quantity + $receive_quantity_per_unit;
				$discount_time                       = floor( floatval( $item_quantity ) / floatval( $total_buy_and_get_quantity_per_unit ) );
			}

			if ( empty( $discount_time ) ) {
				continue;
			}

			if ( ! $is_repeat ) {
				$discount_time = 1;
			}

			$receive_quantity = $discount_time * $receive_quantity_per_unit;

			if ( $is_get_free_item ) {
				$product_remaining_quantity = \YAYDP\Helper\YAYDP_Helper::get_remaining_product_stock( $cart, $product );
				if ( $receive_quantity > $product_remaining_quantity ) {
					continue;
				}
			} else {
				if ( $receive_quantity > $item_quantity ) {
					continue;
				}
			}

			$result[] = array(
				'quantity'  => $receive_quantity,
				'item'      => $is_get_free_item ? $product : $item,
				'cart_item' => $item,
			);

			if ( ! $is_repeat ) {
				break;
			}
		}
		return $result;
	}

	/**
	 * Calculate total discount amount per order
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment Adjustment.
	 */
	public function get_total_discount_amount( $adjustment ) {
		$total              = 0;
		$is_get_free_item   = $this->is_get_free_item();
		$list_receive_items = $this->get_list_receive_items( $adjustment );
		foreach ( $list_receive_items as $data ) {
			$receive_quantity = $data['quantity'];
			if ( $is_get_free_item ) {
				$product                  = $data['item'];
				$discount_amount_per_item = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
			} else {
				$item                     = $data['item'];
				$discount_amount_per_item = parent::get_discount_amount_per_item( $item );
			}
			$total += $discount_amount_per_item * $receive_quantity;
		}
		return $total;
	}

	/**
	 * Retrives buy quantity
	 */
	public function get_buy_quantity() {
		return ! empty( $this->data['pricing']['buy_quantity'] ) ? $this->data['pricing']['buy_quantity'] : 1;
	}

	/**
	 * Retrives receive quantity
	 */
	public function get_receive_quantity_per_unit() {
		return ! empty( $this->data['pricing']['get_quantity'] ) ? $this->data['pricing']['get_quantity'] : 1;
	}

	/**
	 * Calculate all possible adjustment created by the rule.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart ) {
		$bought_cases = $this->get_bought_cases( $cart );
		if ( empty( $bought_cases ) ) {
			return null;
		}
		return array(
			'rule'         => $this,
			'bought_cases' => $bought_cases,
		);
	}

	/**
	 * Calculate the discount and apply modifier to the cart item.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item.
	 */
	public function discount_item( \YAYDP\Core\YAYDP_Cart_Item $item ) {}

	/**
	 * Calculate the discount and apply modifier to the receive items.
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment Adjustment.
	 */
	public function discount_items( $adjustment ) {
		$is_get_free_item   = $this->is_get_free_item();
		$list_receive_items = $this->get_list_receive_items( $adjustment );
		$cart               = $adjustment->get_cart();
		foreach ( $list_receive_items as $receive_data ) {
			$receive_quantity = $receive_data['quantity'];
			if ( $is_get_free_item ) {
				$product  = $receive_data['item'];
				$new_item = $cart->add_free_item(
					$product,
					$receive_quantity,
					array(
						'variation' => isset( $receive_data['cart_item'] ) ? $receive_data['cart_item']->get_variation() : array(),
					)
				);
				if ( null == $new_item ) {
					continue;
				}
				$modifier = array(
					'rule'              => $this,
					'modify_quantity'   => $receive_quantity,
					'item'              => $product,
					'discount_per_unit' => \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product ),
				);
				$new_item->add_modifier( $modifier );
			} else {
				$item                  = $receive_data['item'];
				$item_quantity         = $item->get_quantity();
				$discount_per_item     = parent::get_discount_amount_per_item( $item );
				$total_discount_amount = $discount_per_item * $receive_quantity;
				$item_price            = $item->get_price();
				$item_total_price      = $item_price * $item_quantity;
				$discounted_price      = max( 0, $item_total_price - $total_discount_amount );
				$item->set_price( $discounted_price / $item_quantity );
				$modifier = array(
					'rule'              => $this,
					'modify_quantity'   => $receive_quantity,
					'discount_per_unit' => $discount_per_item,
					'item'              => $item,
				);
				$item->add_modifier( $modifier );
				$item_product = $item->get_product();
				if ( \yaydp_product_pricing_is_applied_to_non_discount_product() && $item_product ) {
					\YAYDP\Core\Discounted_Products\YAYDP_Discounted_Products::get_instance()->add_product( $item_product );
				}
			}
		}
	}

	/**
	 * Calculate possible bought cases
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_bought_cases( \YAYDP\Core\YAYDP_Cart $cart ) {
		$result       = array();
		$buy_quantity = $this->get_buy_quantity();
		foreach ( $cart->get_items() as $item ) {
			$product       = $item->get_product();
			$item_quantity = $item->get_quantity();
			if ( parent::can_apply_adjustment( $product, null, 'any', $item->get_key() ) ) {
				if ( $item->get_quantity() >= $buy_quantity ) {
					$result[] = array(
						'quantity' => $item_quantity,
						'item'     => $item,
					);
				}
			}
		}
		return $result;
	}

	/**
	 * Get minimim discount information that can apply to the product
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_min_discount( $product ) {
		if ( $this->is_get_free_item() ) {
			return array(
				'pricing_value' => 0,
				'pricing_type'  => 'percentage_discount',
				'maximum'       => $this->get_maximum_adjustment_amount(),
			);
		} else {
			return array(
				'pricing_value' => $this->get_pricing_value(),
				'pricing_type'  => $this->get_pricing_type(),
				'maximum'       => $this->get_maximum_adjustment_amount(),
			);
		}

	}

	/**
	 * Get maximum discount information that can apply to the product
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_max_discount( $product ) {
		if ( $this->is_get_free_item() ) {
			return array(
				'pricing_value' => 100,
				'pricing_type'  => 'percentage_discount',
				'maximum'       => $this->get_maximum_adjustment_amount(),
			);
		} else {
			return array(
				'pricing_value' => $this->get_pricing_value(),
				'pricing_type'  => $this->get_pricing_type(),
				'maximum'       => $this->get_maximum_adjustment_amount(),
			);
		}
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
		$matching_items            = array();
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
			$item_quantity = $item->get_quantity();
			if ( $this->can_apply_adjustment( $item_product, null, 'any', $item->get_key() ) ) {
				$buy_quantity = $this->get_buy_quantity();
				if ( empty( $item_quantity < $buy_quantity ) ) {
					$matching_items[] = array(
						'item'             => $item,
						'missing_quantity' => $buy_quantity - $item_quantity,
					);
				}
			}
		}

		if ( empty( $matching_items ) ) {
			return null;
		}

		usort(
			$matching_items,
			function( $a, $b ) {
				return $a['missing_quantity'] <=> $b['missing_quantity'];
			}
		);

		return new \YAYDP\Core\Encouragement\YAYDP_Product_Pricing_Encouragement(
			array(
				'item'                      => $matching_items[0]['item'],
				'rule'                      => $this,
				'conditions_encouragements' => $conditions_encouragements,
				'missing_quantity'          => $matching_items[0]['missing_quantity'],
			)
		);
	}

}
