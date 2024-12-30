<?php
/**
 * Handle Count quantity by / discount type : all together
 *
 * @package YayPricing\DiscountType
 *
 * @since 2.4
 */

namespace YAYDP\Core\Discount_Type;

/**
 * Declare class
 */
class YAYDP_Filter_Discount {
	public static function get_matching_cases( \YAYDP\Core\YAYDP_Cart $cart, $filters, $quantity = 1, $match_type = 'any' ) {
		$result                = array();
		$filter_quantity       = $quantity;
		$total_bought_quantity = 0;
		$matching_items        = array();
		foreach ( $cart->get_items() as $item ) {
			$product                    = $item->get_product();
			$item_bought_quantity       = $item->get_quantity();
			$is_product_matching_filter = \YAYDP\Helper\YAYDP_Helper::check_applicability( $filters, $product, $match_type );
			if ( $is_product_matching_filter ) {
				$matching_items[]       = $item;
				$total_bought_quantity += $item_bought_quantity;
			}
		}
		if ( $total_bought_quantity >= $filter_quantity ) {
			$result[] = array(
				'quantity'        => $filter_quantity,
				'bought_quantity' => $total_bought_quantity,
				'items'           => $matching_items,
			);
		}
		return $result;
	}

	public static function get_free_receive_items( $cart, $matching_items, &$receive_quantity, &$all_extra_items, $free_chosen_products = array() ) {
		$result            = array();
		$clone_extra_items = array();

		$matching_items = array_filter( $matching_items, function( $item_id ) {
			$item_product = \wc_get_product( $item_id ); 
			if ( empty( $item_product ) ) {
				return false;
			}
			return $item_product->is_in_stock();
		} );

		if ( ! is_array( $free_chosen_products ) ) {
			$free_chosen_products = array();
		}
		if ( count( $matching_items ) > 1 && apply_filters( 'yaydp_randomize_free_items', true ) ) {
			$current_hour   = gmdate( 'H' );
			$current_date   = gmdate( 'd' );
			$lucky_number   = intval( $current_hour ) + intval( $current_date );
			$target_point   = intval( $lucky_number ) % ( count( $matching_items ) + 1 );
			$target_point   = min( $target_point, count( $matching_items ) );
			$new_items      = array_merge( array_slice( $matching_items, $target_point ), array_slice( $matching_items, 0, $target_point - 1 ) );
			$matching_items = $new_items;
		}
		foreach ( $matching_items as $product_id ) {
			if ( empty( $receive_quantity ) ) {
				break;
			}
			$product = \wc_get_product( $product_id );
			if ( false === $product ) {
				continue;
			}
			if ( count( $free_chosen_products ) > 0 && ! isset( $free_chosen_products[ $product_id ] ) ) {
				if ( ! \yaydp_is_variable_product( $product ) && ! \yaydp_is_grouped_product( $product ) ) {
					continue;
				}
			}
			if ( \yaydp_is_variable_product( $product ) || \yaydp_is_grouped_product( $product ) ) {
				$children_ids = $product->get_children();
				$child_result = self::get_free_receive_items( $cart, $children_ids, $receive_quantity, $all_extra_items, $free_chosen_products );
				if ( ! empty( $child_result ) ) {
					$result = array_merge( $result, $child_result );
				}
				continue;
			}
			$product_remaining_stock  = \YAYDP\Helper\YAYDP_Helper::get_remaining_product_stock( $cart, $product );
			$product_remaining_stock  = \YAYDP\Helper\YAYDP_Helper::get_remaining_product_stock_include_extra_items( $product_id, $product_remaining_stock, $all_extra_items );
			$product_receive_quantity = min( $receive_quantity, $product_remaining_stock );

			if ( count( $free_chosen_products ) > 0 && isset( $free_chosen_products[ $product_id ] ) ) {
				$product_receive_quantity = min( (int) $free_chosen_products[ $product_id ], $product_remaining_stock );
				$product_receive_quantity = min( $receive_quantity, $product_receive_quantity );
			}
			if ( empty( $product_receive_quantity ) ) {
				continue;
			}
			$result[]          = array(
				'quantity' => $product_receive_quantity,
				'item'     => $product,
			);
			$receive_quantity -= $product_receive_quantity;
			\YAYDP\Helper\YAYDP_Helper::push_in_items( $clone_extra_items, $product_id, $product_receive_quantity );
			\YAYDP\Helper\YAYDP_Helper::push_in_items( $all_extra_items, $product_id, $product_receive_quantity );
		}
		// if ( ! empty( $receive_quantity ) ) {
		// 	\YAYDP\Helper\YAYDP_Helper::take_back_items( $all_extra_items, $clone_extra_items );
		// 	return array();
		// }
		return $result;
	}

	public static function get_discount_receive_items( $cart, $matching_items, &$receive_quantity, $receive_quantity_per_unit, $bought_cases ) {
		$result                  = array();
		$total_discount_quantity = 0;
		$matching_pairs          = \YAYDP\Helper\YAYDP_Helper::get_matching_pairs( $bought_cases );

		foreach ( $matching_pairs as $pair ) {
			foreach ( $matching_items as $item ) {
				if ( empty( $receive_quantity ) ) {
					break;
				}

				$min_require_quantity   = \YAYDP\Helper\YAYDP_Helper::get_min_require_quantity( array( $pair ), $item );
				$is_item_in_bought_case = $min_require_quantity > 0;

				if ( $is_item_in_bought_case ) {
					$product                             = $item->get_product();
					$product_quantity_in_cart            = \yaydp_get_current_quantity_in_cart( $cart, $product );
					$total_buy_and_get_quantity_per_unit = $min_require_quantity + $receive_quantity_per_unit;
					$discount_time                       = floor( floatval( $product_quantity_in_cart ) / floatval( $total_buy_and_get_quantity_per_unit ) );
					if ( empty( $discount_time ) ) {
						continue;
					}
					$discount_quantity        = min( $product_quantity_in_cart, $discount_time * $receive_quantity_per_unit );
					$total_discount_quantity += $discount_time * $receive_quantity_per_unit;
					$result[]                 = array(
						'quantity' => $discount_quantity,
						'item'     => $item,
					);
					$receive_quantity        -= $discount_quantity;
				} else {
					$item_quantity            = $item->get_quantity();
					$discount_quantity        = max( 0, min( $item_quantity, $receive_quantity ) );
					$total_discount_quantity += $discount_quantity;
					$result[]                 = array(
						'quantity' => $discount_quantity,
						'item'     => $item,
					);
					$receive_quantity        -= $discount_quantity;
				}
			}
		}
		$excess_quantity = $total_discount_quantity % $receive_quantity_per_unit;
		$index           = count( $result ) - 1;
		while ( ! empty( $excess_quantity ) && $index >= 0 ) {
			$remove_quantity              = min( $excess_quantity, $result[ $index ] );
			$result[ $index ]['quantity'] = max( 0, $result[ $index ]['quantity'] - $remove_quantity );
			$excess_quantity              = max( 0, $excess_quantity - $remove_quantity );
			if ( empty( $result[ $index ]['quantity'] ) ) {
				unset( $result[ $index ] );
			}
			$index--;
		}
		return $result;
	}

}
