<?php
/**
 * Handle Count quantity by / discount type : Variations
 *
 * @package YayPricing\DiscountType
 *
 * @since 2.4
 */

namespace YAYDP\Core\Discount_Type;

/**
 * Declare class
 */
class YAYDP_Variations_Discount {
	public static function get_matching_cases( \YAYDP\Core\YAYDP_Cart $cart, $filters, $quantity = 1, $match_type = 'any' ) {
		$result          = array();
		$filter_quantity = $quantity;
		$matching_items  = array();
		foreach ( $cart->get_items() as $item ) {
			$product                    = $item->get_product();
			$product_id                 = $product->get_id();
			$product_parent_id          = $product->get_parent_id();
			$has_parent                 = ! empty( $product_parent_id );
			$item_bought_quantity       = $item->get_quantity();
			$is_product_matching_filter = \YAYDP\Helper\YAYDP_Helper::check_applicability( $filters, $product, $match_type );
			if ( $is_product_matching_filter ) {
				$set_key = $has_parent ? $product_parent_id : $product_id;
				if ( ! isset( $matching_items[ $set_key ] ) ) {
					$initial_value              = array(
						'quantity'        => $filter_quantity,
						'bought_quantity' => 0,
						'items'           => array(),
					);
					$matching_items[ $set_key ] = $initial_value;
				}
				$matching_items[ $set_key ]['bought_quantity'] += $item_bought_quantity;
				$matching_items[ $set_key ]['items'][]          = $product;
			}
		}
		foreach ( $matching_items as $i ) {
			if ( $i['bought_quantity'] >= $filter_quantity ) {
				$result[] = $i;
			}
		}
		return $result;
	}

	public static function get_free_receive_items( $cart, $matching_items, &$receive_quantity, &$all_extra_items ) {
		$result = array();
		foreach ( $matching_items as $product_id ) {
			if ( empty( $receive_quantity ) ) {
				break;
			}
			$product = \wc_get_product( $product_id );
			if ( false === $product ) {
				continue;
			}
			if ( \yaydp_is_variable_product( $product ) ) {
				$clone_receive_quantity = $receive_quantity;
				$children_ids           = $product->get_children();
				$result                 = array_merge( $result, \YAYDP\Core\Discount_Type\YAYDP_Filter_Discount::get_free_receive_items( $cart, $children_ids, $clone_receive_quantity, $all_extra_items ) );
			} else {
				$result = array_merge( $result, \YAYDP\Core\Discount_Type\YAYDP_Individual_Discount::get_free_receive_items( $cart, array( $product_id ), $receive_quantity, $all_extra_items ) );
			}
			if ( ! empty( $result ) ) {
				return $result;
			}
		}
		return $result;
	}

	public static function get_discount_receive_items( $cart, $matching_items, &$receive_quantity, $receive_quantity_per_unit, $bought_cases ) {
		$result                 = array();
		$item                   = current( $matching_items );
		$clone_receive_quantity = $receive_quantity;
		while ( false !== $item && $clone_receive_quantity > 0 ) {
			if ( empty( $receive_quantity ) ) {
				break;
			}
			$product = $item->get_product();
			if ( \yaydp_is_variation_product( $product ) ) {
				$list_variations   = array();
				$product_parent_id = $product->get_parent_id();
				if ( empty( $product_parent_id ) ) {
					continue;
				}
				foreach ( $matching_items as $it_index => $it ) {
					$p = $it->get_product();
					if ( \yaydp_is_variation_product( $p ) ) {
						$p_parent_id = $p->get_parent_id();
						if ( empty( $p_parent_id ) ) {
							continue;
						}
						if ( $p_parent_id !== $product_parent_id ) {
							continue;
						}
						$list_variations[] = $it;
						unset( $matching_items[ $it_index ] );
					}
				}
				if ( ! empty( $list_variations ) ) {
					$sub_result = \YAYDP\Core\Discount_Type\YAYDP_Filter_Discount::get_discount_receive_items( $cart, $list_variations, $clone_receive_quantity, $receive_quantity_per_unit, $bought_cases );
				}
			} else {
				$sub_result = \YAYDP\Core\Discount_Type\YAYDP_Individual_Discount::get_discount_receive_items( $cart, array( $item ), $clone_receive_quantity, $receive_quantity_per_unit, $bought_cases );
			}
			if ( ! empty( $sub_result ) ) {
				$result = array_merge( $result, $sub_result );
			}
			next( $matching_items );
			$item = current( $matching_items );
		}
		return $result;
	}
}
