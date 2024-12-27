<?php
/**
 * This class handle Buy X Get Y rule
 *
 * @package YayPricing\Rule\ProductPricing
 */

namespace YAYDP\Core\Rule\Product_Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Buy_X_Get_Y extends \YAYDP\Abstracts\YAYDP_Product_Pricing_Rule {

	/**
	 * Return type of rule
	 *
	 * @override
	 */
	public function get_type() {
		return 'buy_x_get_y';
	}

	/**
	 * Check whether rule will automatically add free item
	 */
	public function is_get_free_item() {
		$item_get_type = ! empty( $this->data['pricing']['item_get_type'] ) ? $this->data['pricing']['item_get_type'] : 'free';
		return 'free' === $item_get_type;
	}

	/**
	 * Retrieves match type of receive filters
	 */
	public function get_match_type_of_receive_filters() {
		return ! empty( $this->data['get_products']['match_type'] ) ? $this->data['get_products']['match_type'] : 'any';
	}

	/**
	 * Retrieves receive filters
	 */
	public function get_receive_filters() {
		return isset( $this->data['get_products']['filters'] ) ? $this->data['get_products']['filters'] : array();
	}

	/**
	 * Check whether discount is repeated
	 */
	public function is_repeat() {
		return isset( $this->data['pricing']['repeat'] ) ? $this->data['pricing']['repeat'] : false;
	}

	/**
	 * Retrieves received item type
	 */
	public function get_received_item_type() {
		return isset( $this->data['pricing']['received_item_type'] ) ? $this->data['pricing']['received_item_type'] : 'default';
	}

	/**
	 * Calculate all possible receive items
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment Adjustment.
	 */
	public function get_list_receive_items( $adjustment ) {
		if ( $this->is_get_free_item() ) {
			return $this->get_list_receive_items_for_free_case( $adjustment );
		}
		return $this->get_list_receive_items_for_discount_case( $adjustment );
	}

	/**
	 * Calculate all possible receive items for get free item
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment Adjustment.
	 */
	private function get_list_receive_items_for_free_case( $adjustment ) {
		$rule_id                   = $this->get_rule_id();
		$free_chosen_products      = yaydp_get_free_chosen_products( $rule_id );
		$all_receive_items         = array();
		$receive_filters           = $this->get_receive_filters();
		$receive_match_type        = $this->get_match_type_of_receive_filters();
		$is_any_receive_match_type = 'any' === $receive_match_type;
		$receive_cases             = $adjustment->get_receive_cases();
		$all_extra_items           = array();
		$all_receive_items         = array();
		$is_repeat                 = $this->is_repeat();
		$total_discount_time       = 0;
		$cart                      = $adjustment->get_cart();
		foreach ( $adjustment->get_bought_cases() as $b_case ) {
			$discount_time_array = array();
			foreach ( $b_case as $case_line_data ) {
				$quantity              = $case_line_data['quantity'];
				$bought_quantity       = $case_line_data['bought_quantity'];
				$discount_time_array[] = floor( floatval( $bought_quantity ) / floatval( $quantity ) );
			}
			$discount_time = min( $discount_time_array );
			if ( empty( $discount_time ) ) {
				continue;
			}

			if ( ! $is_repeat ) {
				$discount_time = 1;
			}

			$total_discount_time += $discount_time;

		}

		if ( ! $is_repeat ) {
			$total_discount_time = 1;
		}
		foreach ( $receive_cases['case'] as $a_case ) {
			$receive_quantity_per_unit = $a_case['quantity'];
			$receive_quantity          = $total_discount_time * $receive_quantity_per_unit;
			$receive_items             = \YAYDP\Core\Discount_Type\YAYDP_Filter_Discount::get_free_receive_items( $cart, $a_case['items'], $receive_quantity, $all_extra_items, $free_chosen_products );
			if ( ! empty( $receive_items ) ) {
				$all_receive_items[] = $receive_items;
			}
			if ( $is_any_receive_match_type && ! empty( $receive_items ) ) {
				break;
			}
		}
		if ( ! $is_any_receive_match_type && count( $all_receive_items ) !== count( $receive_filters ) ) {
			$all_receive_items = array();
		}
		return $all_receive_items;
	}

	/**
	 * Calculate all possible receive items for get discount item
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment Adjustment.
	 */
	private function get_list_receive_items_for_discount_case( $adjustment ) {
		$all_receive_items         = array();
		$receive_filters           = $this->get_receive_filters();
		$receive_match_type        = $this->get_match_type_of_receive_filters();
		$is_any_receive_match_type = 'any' === $receive_match_type;
		$receive_cases             = $adjustment->get_receive_cases();
		$all_receive_items         = array();
		$is_repeat                 = $this->is_repeat();
		$total_discount_time       = 0;
		$bought_cases              = $adjustment->get_bought_cases();
		$cart                      = $adjustment->get_cart();
		foreach ( $bought_cases as $b_case ) {
			$discount_time_array = array();
			foreach ( $b_case as $case_line_data ) {
				$quantity              = $case_line_data['quantity'];
				$bought_quantity       = $case_line_data['bought_quantity'];
				$discount_time_array[] = floor( floatval( $bought_quantity ) / floatval( $quantity ) );
			}
			$discount_time = min( $discount_time_array );
			if ( empty( $discount_time ) ) {
				continue;
			}

			if ( ! $is_repeat ) {
				$discount_time = 1;
			}

			$total_discount_time += $discount_time;

		}

		if ( ! $is_repeat ) {
			$total_discount_time = 1;
		}

		foreach ( $receive_cases['case'] as $a_case ) {
			$receive_quantity_per_unit = $a_case['quantity'];
			$receive_quantity          = $total_discount_time * $receive_quantity_per_unit;
			$receive_items             = \YAYDP\Core\Discount_Type\YAYDP_Filter_Discount::get_discount_receive_items( $cart, $a_case['items'], $receive_quantity, $receive_quantity_per_unit, $bought_cases );
			if ( ! empty( $receive_items ) ) {
				$all_receive_items[] = $receive_items;
			}
			if ( $is_any_receive_match_type && ! empty( $receive_items ) ) {
				break;
			}
		}
		if ( ! $is_any_receive_match_type && count( $all_receive_items ) !== count( $receive_filters ) ) {
			$all_receive_items = array();
		}
		return $all_receive_items;
	}

	/**
	 * Calculate total discount amount per order
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment Adjustment.
	 */
	public function get_total_discount_amount( $adjustment ) {
		$total             = 0;
		$is_get_free_item  = $this->is_get_free_item();
		$all_receive_items = $this->get_list_receive_items( $adjustment );

		foreach ( $all_receive_items as $list ) {
			foreach ( $list as $receive_data ) {
				$receive_quantity = $receive_data['quantity'];
				if ( $is_get_free_item ) {
					$product        = $receive_data['item'];
					$discount_value = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
				} else {
					$item           = $receive_data['item'];
					$discount_value = parent::get_discount_amount_per_item( $item );
				}
				$total += $discount_value * $receive_quantity;
			}
		}
		return $total;
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
		if ( $this->is_get_free_item() ) {
			$receive_cases = $this->get_auto_receive_cases();
		} else {
			$receive_cases = $this->get_manual_receive_cases( $cart );
		}
		if ( empty( $receive_cases ) ) {
			return null;
		}
		return array(
			'rule'          => $this,
			'bought_cases'  => $bought_cases,
			'receive_cases' => $receive_cases,
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

	}

	/**
	 * Calculate the discount and apply modifier to the receive items.
	 *
	 * @param \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment $adjustment Adjustment.
	 */
	public function discount_items( $adjustment ) {
		$is_get_free_item   = $this->is_get_free_item();
		$list_receive_items = $this->get_list_receive_items( $adjustment );
		$cart               = $adjustment->get_cart();
		foreach ( $list_receive_items as $list ) {
			foreach ( $list as $receive_data ) {
				$receive_quantity = $receive_data['quantity'];
				if ( $is_get_free_item ) {
					$product  = $receive_data['item'];
					$new_item = $cart->add_free_item( $product, $receive_quantity );
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
	}

	/**
	 * Calculate possible bought cases
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	protected function get_bought_cases( $cart ) {
		$result            = array();
		$buy_filters       = $this->get_buy_filters();
		$buy_match_type    = $this->get_match_type_of_buy_filters();
		$is_all_match_type = 'all' === $buy_match_type;

		$bought_cases_by_filters = array();
		foreach ( $buy_filters as $filter ) {
			$line_filter_bought_cases = $this->get_bought_case_by_line_filter( $cart, array( $filter ), $filter['quantity'] );
			if ( empty( $line_filter_bought_cases ) ) {
				if ( $is_all_match_type ) {
					break;
				}
				continue;
			}
			if ( ! $is_all_match_type ) {
				$result = array_merge( $result, \YAYDP\Helper\YAYDP_Helper::map_cases( $line_filter_bought_cases ) );
			} else {
				$bought_cases_by_filters[] = $line_filter_bought_cases;
			}
		}

		if ( $is_all_match_type && count( $bought_cases_by_filters ) !== count( $buy_filters ) ) {
			return array();
		}

		if ( $is_all_match_type ) {
			foreach ( $bought_cases_by_filters as $line_bought_cases ) {
				if ( empty( $result ) ) {
					$result = \YAYDP\Helper\YAYDP_Helper::map_cases( $line_bought_cases );
					continue;
				}
				$tmp = array();
				foreach ( $result as $i ) {
					foreach ( $line_bought_cases as $j ) {
						$tmp[] = array_merge( $i, array( $j ) );
					}
				}
				$result = $tmp;
			}
		}

		return $result;
	}

	/**
	 * Calculate possible bought cases by each filter
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 * @param array                  $filters Filters.
	 * @param float                  $quantity Buy quantity.
	 */
	protected function get_bought_case_by_line_filter( $cart, $filters, $quantity ) {
		if ( parent::is_all_together_discount() ) {
			return \YAYDP\Core\Discount_Type\YAYDP_Filter_Discount::get_matching_cases( $cart, $filters, $quantity, 'all' );
		}
		if ( parent::is_individual_line_item_discount() ) {
			return \YAYDP\Core\Discount_Type\YAYDP_Individual_Discount::get_matching_cases( $cart, $filters, $quantity, 'all' );
		}
		if ( parent::is_variations_discount() ) {
			return \YAYDP\Core\Discount_Type\YAYDP_Variations_Discount::get_matching_cases( $cart, $filters, $quantity, 'all' );
		}
		return array();
	}

	/**
	 * Calculate possible automatic receive cases
	 */
	public function get_auto_receive_cases() {
		$filters                        = $this->get_receive_filters();
		$match_type                     = $this->get_match_type_of_receive_filters();
		$matching_products_with_filters = array();
		foreach ( $filters as $filter_index => $filter ) {
			$matching_products = \YAYDP\Helper\YAYDP_Matching_Products_Helper::get_matching_products( $filter, 'none' );
			$matching_products = array_filter(
				$matching_products,
				function( $product ) {
					return ! \YAYDP\Core\Manager\YAYDP_Exclude_Manager::check_product_exclusions( $this, $product );
				}
			);
			if ( $this->is_receive_cheapest() ) {
				\YAYDP\Helper\YAYDP_Helper::sort_products_by_price( $matching_products );
			}
			if ( $this->is_receive_most_expensive() ) {
				\YAYDP\Helper\YAYDP_Helper::sort_products_by_price( $matching_products, 'desc' );
			}
			$matching_products_with_filters[] = array(
				'quantity' => $filters[ $filter_index ]['quantity'],
				'items'    => array_map(
					function( $product ) {
						return $product->get_id();
					},
					$matching_products
				),
			);
		}
		$result = array(
			'relation' => $match_type,
			'case'     => $matching_products_with_filters,
		);
		return $result;
	}

	/**
	 * Calculate possible manual receive cases
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function get_manual_receive_cases( $cart ) {
		$filters    = $this->get_receive_filters();
		$match_type = $this->get_match_type_of_receive_filters();
		$case       = array();
		foreach ( $filters as $filter_index => $filter ) {
			$matching_items = array();
			$total_quantity = 0;
			foreach ( $cart->get_items() as $item ) {
				$item_product = $item->get_product();
				if ( $this->can_apply_adjustment( $item_product, array( $filter ), $match_type, $item->get_key() ) ) {
					$total_quantity  += $item->get_quantity();
					$matching_items[] = $item;
				}
			}
			if ( $total_quantity < $filter['quantity'] ) {
				continue;
			}
			if ( $this->is_receive_cheapest() ) {
				\YAYDP\Helper\YAYDP_Helper::sort_items_by_price( $matching_items );
			}
			if ( $this->is_receive_most_expensive() ) {
				\YAYDP\Helper\YAYDP_Helper::sort_items_by_price( $matching_items, 'desc' );
			}
			if ( ! empty( $matching_items ) ) {
				$case[] = array(
					'quantity' => $filters[ $filter_index ]['quantity'],
					'items'    => $matching_items,
				);
			}
		}
		if ( empty( $case ) ) {
			return null;
		}
		$result = array(
			'relation' => $match_type,
			'case'     => $case,
		);
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
			$receive_filters = $this->get_receive_filters();
			if ( $this->can_apply_adjustment( $item_product, $receive_filters, 'any', $item->get_key() ) ) {
				$matching_items[] = array(
					'item'             => $item,
					'missing_quantity' => 1,
				);
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

	/**
	 * Check whether rule will add the cheapest item
	 */
	public function is_receive_cheapest() {
		return 'cheapest' === $this->get_received_item_type();
	}

	/**
	 * Check whether rule will add the most expensive item
	 */
	public function is_receive_most_expensive() {
		return 'most_expensive' === $this->get_received_item_type();
	}

}
