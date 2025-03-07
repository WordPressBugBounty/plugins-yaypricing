<?php
/**
 * This class handle Tiered Pricing rule
 *
 * @package YayPricing\Rule\ProductPricing
 */

namespace YAYDP\Core\Rule\Product_Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Tiered_Pricing extends \YAYDP\Abstracts\YAYDP_Product_Pricing_Rule {

	/**
	 * Return type of rule
	 *
	 * @override
	 */
	public function get_type() {
		return 'tiered_pricing';
	}

	/**
	 * Retrives pricing ranges data
	 */
	public function get_ranges() {
		return isset( $this->data['pricing_ranges'] ) ? $this->data['pricing_ranges'] : array();
	}

	/**
	 * Return matching range with given quantity
	 *
	 * @param null|float $current_quantity $given quantity.
	 */
	public function get_matching_range( $current_quantity = null ) {
		if ( is_null( $current_quantity ) ) {
			return null;
		}

		foreach ( $this->get_ranges() as $range ) {
			$range_instance = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Tiered_Range( $range );
			if ( $range_instance->is_matching_current_quantity( $current_quantity ) ) {
				return $range_instance;
			}
		}

		return null;
	}

	/**
	 * Retrieves rule pricing type
	 *
	 * @override
	 *
	 * @param float $current_quantity Quantity.
	 */
	public function get_pricing_type( $current_quantity = null ) {

		$matching_range = $this->get_matching_range( $current_quantity );
		if ( is_null( $matching_range ) ) {
			return 'fixed_discount';
		}

		return $matching_range->get_pricing_type();

	}

	/**
	 * Retrieves rule pricing value
	 *
	 * @override
	 *
	 * @param float $current_quantity Quantity.
	 */
	public function get_pricing_value( $current_quantity = null ) {
		$matching_range = $this->get_matching_range( $current_quantity );
		if ( is_null( $matching_range ) ) {
			return 0;
		}

		return $matching_range->get_pricing_value();
	}

	/**
	 * Retrieves rule maximum value
	 *
	 * @override
	 *
	 * @param float $current_quantity Quantity.
	 */
	public function get_maximum_adjustment_amount( $current_quantity = null ) {
		$matching_range = $this->get_matching_range( $current_quantity );
		if ( is_null( $matching_range ) ) {
			return 0;
		}

		return $matching_range->get_maximum_adjustment_amount();
	}

	/**
	 * Calculate all possible adjustment created by the rule.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function create_possible_adjustment_from_cart( \YAYDP\Core\YAYDP_Cart $cart ) {
		$discountable_items  = array();
		$products_quantities = array();

		foreach ( $cart->get_items() as $item ) {
			$product                         = $item->get_product();
			$can_apply_adjustment_to_product = parent::can_apply_adjustment( $product, null, 'any', $item->get_key() );
			$item_quantity                   = $item->get_quantity();

			if ( ! $can_apply_adjustment_to_product ) {
				continue;
			}

			$item_key = $item->get_key();
			if ( ! parent::is_variations_discount() ) {
				if ( ! isset( $products_quantities[ $item_key ] ) ) {
					$products_quantities[ $item_key ] = array(
						'quantity' => 0,
						'items'    => array( $item ),
					);
				}
				$products_quantities[ $item_key ]['quantity'] += $item_quantity;
			} else {
				$product_id = $product->get_id();
				if ( \yaydp_is_variation_product( $product ) ) {
					$parent_product_id = $product->get_parent_id();
					$parent_product    = \yaydp_get_product( $parent_product_id );
					if ( parent::can_apply_adjustment( $parent_product, null, 'any', $item->get_key() ) ) {
						if ( ! isset( $products_quantities[ $parent_product_id ] ) ) {
							$products_quantities[ $parent_product_id ] = array(
								'quantity' => 0,
								'items'    => array(),
							);
						}
						$products_quantities[ $parent_product_id ]['quantity'] += $item_quantity;
						$products_quantities[ $parent_product_id ]['items'][]   = $item;
					} else {
						if ( ! isset( $products_quantities[ $product_id ] ) ) {
							$products_quantities[ $product_id ] = array(
								'quantity' => 0,
								'items'    => array(),
							);
						}
						$products_quantities[ $product_id ]['quantity'] += $item_quantity;
						$products_quantities[ $product_id ]['items'][]   = $item;
					}
				} else {
					if ( ! isset( $products_quantities[ $product_id ] ) ) {
						$products_quantities[ $product_id ] = array(
							'quantity' => 0,
							'items'    => array( $item ),
						);
					}
					$products_quantities[ $product_id ]['quantity'] += $item_quantity;
				}
			}
		}

		if ( ! parent::is_all_together_discount() ) {
			foreach ( $products_quantities as $data ) {
				foreach ( $data['items'] as $item ) {
					$item->set_bulk_quantity( $data['quantity'] );
				}
				$discountable_items = array_merge( $discountable_items, $data['items'] );
			}
		} else {
			$current_quantity = 0;
			$has_match_range  = false;
			foreach ( $products_quantities as $data ) {
				$current_quantity += $data['quantity'];
				foreach ( $data['items'] as $item ) {
					$item->set_bulk_quantity( $data['quantity'] );
				}
				$discountable_items = array_merge( $discountable_items, $data['items'] );
			}
			for ( $i = 0; $i <= $current_quantity; $i++ ) {
				if ( ! is_null( $this->get_matching_range( $i ) ) ) {
					$has_match_range = true;
					break;
				}
			}
			if ( ! $has_match_range ) {
				$discountable_items = array();
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
	 * Calculate the adjustment amount for item.
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_adjustment_amount( $item ) {
		$item_price                = $item->get_price();
		$item_quantity             = $item->get_bulk_quantity();
		$pricing_type              = $this->get_pricing_type( $item_quantity );
		$pricing_value             = $this->get_pricing_value( $item_quantity );
		$maximum_adjustment_amount = $this->get_maximum_adjustment_amount( $item_quantity );
		$adjustment_amount         = \YAYDP\Helper\YAYDP_Pricing_Helper::calculate_adjustment_amount( $item_price, $pricing_type, $pricing_value, $maximum_adjustment_amount );
		return $adjustment_amount;
	}

	/**
	 * Calculate discount amount per item unit
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_discount_amount_per_item( $item ) {
		$item_price        = $item->get_price();
		$adjustment_amount = $this->get_adjustment_amount( $item );
		$item_quantity     = $item->get_bulk_quantity();
		if ( \yaydp_is_flat_pricing_type( $this->get_pricing_type( $item_quantity ) ) ) {
			return max( 0, $item_price - $adjustment_amount );
		}
		return min( $item_price, $adjustment_amount );
	}

	/**
	 * Calculate discount value per item unit
	 *
	 * @override
	 *
	 * @param \YAYDP\Core\YAYDP_Cart_Item $item Item to calculate adjustment amount.
	 */
	public function get_discount_value_per_item( $item ) {
		$item_price    = $item->get_price();
		$item_quantity = $item->get_bulk_quantity();
		$pricing_type  = $this->get_pricing_type( $item_quantity );
		if ( \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
			return $this->get_pricing_value( $item_quantity );
		}
		$adjustment_amount = $this->get_adjustment_amount( $item );
		if ( \yaydp_is_flat_pricing_type( $pricing_type ) ) {
			return max( 0, $item_price - $adjustment_amount );
		}
		return min( $item_price, $adjustment_amount );
	}

	protected function get_item_adjust_value( $pricing_type, $pricing_value, $item_price, $maximum_value = null ) {
		$the_maximum = $maximum_value ?? $item_price;
		if ( yaydp_is_flat_pricing_type( $pricing_type ) ) {
			$the_maximum = $item_price;
		}
		$adj_val = \YAYDP\Helper\YAYDP_Pricing_Helper::calculate_adjustment_amount( $item_price, $pricing_type, $pricing_value, $the_maximum );

		if ( yaydp_is_flat_pricing_type( $pricing_type ) ) {
			$adj_val = max( 0, $item_price - $adj_val );
		}

		return $adj_val;
	}

	public function discount_item( $adjustment ) {
		$adjustment_values = array();
		if ( $this->is_individual_line_item_discount() ) {
			$ranges = $this->get_ranges();
			foreach ( $adjustment->get_discountable_items() as $k => $item ) {
				$item_quantity  = $item->get_quantity();
				$item_price     = $item->get_price();
				$total_discount = 0;

				$adjustment_values = array_fill( 1, $item_quantity, 0 );
				for ( $i = 0; $i < $item_quantity; $i++ ) {
					foreach ( $ranges as $range_k => $range ) {
						$from_quantity    = $range['from_quantity'];
						$to_quantity      = $range['to_quantity'];
						$current_quantity = $i + 1;
						if ( $current_quantity >= $from_quantity && ( null === $to_quantity || $current_quantity <= $to_quantity ) ) {

							$adj_val                     = $this->get_item_adjust_value( $range['pricing']['type'], $range['pricing']['value'], $item_price, $range['pricing']['maximum_value'] );
							$total_discount             += $adj_val;
							$adjustment_values[ $i + 1 ] = $adj_val;
							break;
						}
					}
				}
				$discount_per_unit = $total_discount / $item_quantity;
				$discounted_price  = $item_price - $discount_per_unit;
				$this->modify_item( $item, $discounted_price, $discount_per_unit, $adjustment_values );
			}
		} elseif ( $this->is_all_together_discount() ) {
			global $yaydp_cart;
			if ( is_null( $yaydp_cart ) ) {
				$yaydp_cart = new \YAYDP\Core\YAYDP_Cart();
			}
			$ranges           = $this->get_ranges();
			$current_quantity = 0;
			foreach ( $adjustment->get_discountable_items() as $k => $item ) {
				$item_quantity     = $item->get_quantity();
				$item_price        = $item->get_price();
				$discount_per_unit = 0;

				$total_discount = 0;

				$adjustment_values = array_fill( 1, $item_quantity, 0 );
				for ( $i = 0; $i < $item_quantity; $i++ ) {
					$current_quantity++;
					foreach ( $ranges as $range ) {
						$from_quantity = $range['from_quantity'];
						$to_quantity   = $range['to_quantity'];

						if ( $current_quantity >= $from_quantity && ( null === $to_quantity || $current_quantity <= $to_quantity ) ) {
							$adj_val                     = $this->get_item_adjust_value( $range['pricing']['type'], $range['pricing']['value'], $item_price, $range['pricing']['maximum_value'] );
							$total_discount             += $adj_val;
							$adjustment_values[ $i + 1 ] = $adj_val;
							break;
						}
					}
				}
				if ( $total_discount > 0 ) {
					$discount_per_unit = $total_discount / $item_quantity;
					$discounted_price  = $item_price - $discount_per_unit;
					$this->modify_item( $item, $discounted_price, $discount_per_unit, $adjustment_values );
				}
			}
		} elseif ( $this->is_variations_discount() && count( $adjustment->get_discountable_items() ) > 0 ) {
			$loaded_variation_products = array();

			$ranges           = $this->get_ranges();
			$current_quantity = 0;
			foreach ( $adjustment->get_discountable_items() as $item ) {
				$product       = $item->get_product();
				$item_quantity = $item->get_quantity();
				$item_price    = $item->get_price();

				if ( \yaydp_is_variation_product( $product ) ) {
					$parent_product_id = $product->get_parent_id();
					if ( ! isset( $loaded_variation_products[ $parent_product_id ] ) ) {
						$loaded_variation_products[ $parent_product_id ] = true;
						$current_quantity                                = 0;
					}
				}

				$discount_per_unit = 0;
				$total_discount    = 0;

				$adjustment_values = array_fill( 1, $item_quantity, 0 );
				for ( $i = 0; $i < $item_quantity; $i++ ) {
					$current_quantity++;
					foreach ( $ranges as $range_k => $range ) {
						$from_quantity = $range['from_quantity'];
						$to_quantity   = $range['to_quantity'];

						if ( $current_quantity >= $from_quantity && ( null === $to_quantity || $current_quantity <= $to_quantity ) ) {
							$adjust_val                  = $this->get_item_adjust_value( $range['pricing']['type'], $range['pricing']['value'], $item_price, $range['pricing']['maximum_value'] );
							$total_discount             += $adjust_val;
							$adjustment_values[ $i + 1 ] = $adjust_val;
							break;
						}
					}
				}
				$discount_per_unit = $total_discount / $item_quantity;
				$discounted_price  = $item_price - $discount_per_unit;
				$this->modify_item( $item, $discounted_price, $discount_per_unit, $adjustment_values );
			}
		}
	}
	public function modify_item( \YAYDP\Core\YAYDP_Cart_Item $item, $discounted_price, $discount_per_unit, $adjustment_values = array() ) {
		$item_quantity = $item->get_quantity();
		$item->set_price( max( 0, $discounted_price ) );
		$item->adjustment_values = $adjustment_values;
		$modifier                = array(
			'rule'              => $this,
			'modify_quantity'   => $item_quantity,
			'discount_per_unit' => $discount_per_unit,
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
		$min                    = PHP_INT_MAX;
		$has_range_start_with_1 = false;
		foreach ( $this->get_ranges() as $range ) {
			$range_instance = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Tiered_Range( $range );
			if ( 1 == $range_instance->get_min_quantity() ) {
				$has_range_start_with_1 = true;
			}
			$fake_item       = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, $range_instance->get_min_quantity() );
			$discount_amount = $this->get_discount_amount_per_item( $fake_item );
			if ( $min > $discount_amount ) {
				$min    = $discount_amount;
				$result = array(
					'pricing_value' => $range_instance->get_pricing_value(),
					'pricing_type'  => $range_instance->get_pricing_type(),
					'maximum'       => $range_instance->get_maximum_adjustment_amount(),
				);
			}
		}
		if ( ! $has_range_start_with_1 && ! empty( $range_instance ) ) {
			$result = array(
				'pricing_value' => 0,
				'pricing_type'  => 'fixed_discount',
				'maximum'       => $range_instance->get_maximum_adjustment_amount(),
			);
		}
		return $result;
	}

	/**
	 * Get maximum discount information that can apply to the product
	 *
	 * @override
	 *
	 * @param \WC_Product $product Product.
	 */
	public function get_max_discount( $product ) {
		$result = array(
			'pricing_value' => 0,
			'pricing_type'  => 'fixed_discount',
			'maximum'       => 0,
		);
		$max    = 0;
		foreach ( $this->get_ranges() as $range ) {
			$range_instance  = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Tiered_Range( $range );
			$fake_item       = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, $range_instance->get_min_quantity() );
			$discount_amount = $this->get_discount_amount_per_item( $fake_item );
			if ( $max < $discount_amount ) {
				$max    = $discount_amount;
				$result = array(
					'pricing_value' => $range_instance->get_pricing_value(),
					'pricing_type'  => $range_instance->get_pricing_type(),
					'maximum'       => $range_instance->get_maximum_adjustment_amount(),
				);
			}
		}
		return $result;
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
			$item_quantity = $item->get_bulk_quantity();
			if ( $this->can_apply_adjustment( $item_product, null, 'any', $item->get_key() ) ) {
				$next_range = $this->get_next_range( $item_quantity );
				if ( empty( $next_range ) ) {
					continue;
				}
				$matching_items[] = array(
					'item'             => $item,
					'missing_quantity' => $next_range->get_min_quantity() - $item_quantity,
				);
			}
		}

		if ( empty( $matching_items ) ) {
			return null;
		}

		usort(
			$matching_items,
			function( $a, $b ) {
				$res = $a['missing_quantity'] < $b['missing_quantity'] ? -1 : 1;
				return $res;
			}
		);

		return null;
	}

	/**
	 * Calculate the next maching range based on current quantity
	 *
	 * @param float $current_quantity Quantity.
	 */
	public function get_next_range( $current_quantity ) {
		if ( is_null( $current_quantity ) ) {
			return null;
		}
		foreach ( $this->get_ranges() as $range ) {
			$range_instance = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Tiered_Range( $range );
			if ( $current_quantity < $range_instance->get_min_quantity() ) {
				return $range_instance;
			}
		}
		return null;
	}
}
