<?php
/**
 * Handle Count quantity by / discount type : single line item
 *
 * @package YayPricing\DiscountType
 *
 * @since 2.4
 */

namespace YAYDP\Core\Discount_Type;

/**
 * Declare class
 */
class YAYDP_Individual_Discount {
	public static function get_matching_cases( \YAYDP\Core\YAYDP_Cart $cart, $filters, $quantity = 1, $match_type = 'any' ) {
		$result          = array();
		$filter_quantity = $quantity;
		foreach ( $cart->get_items() as $item ) {
			$product                    = $item->get_product();
			$bought_quantity            = $item->get_quantity();
			$is_product_matching_filter = \YAYDP\Helper\YAYDP_Helper::check_applicability( $filters, $product, $match_type );
			$is_product_fit_quantity    = $bought_quantity >= $filter_quantity;
			if ( $is_product_matching_filter && $is_product_fit_quantity ) {
				$result[] = array(
					'quantity'        => $filter_quantity,
					'bought_quantity' => $bought_quantity,
					'items'           => array(
						$item,
					),
				);
			}
		}
		return $result;
	}

	public static function get_free_receive_items( $cart, $matching_items, &$receive_quantity, &$all_extra_items ) {
		$result            = array();
		$clone_extra_items = array();
		foreach ( $matching_items as $product_id ) {
			if ( empty( $receive_quantity ) ) {
				break;
			}
			$product = \wc_get_product( $product_id );
			if ( false === $product ) {
				continue;
			}
			if ( \yaydp_is_variable_product( $product ) || \yaydp_is_grouped_product( $product ) ) {
				$children_ids = $product->get_children();
				$child_result = self::get_free_receive_items( $cart, $children_ids, $receive_quantity, $all_extra_items );
				if ( ! empty( $child_result ) ) {
					$result = array_merge( $result, $child_result );
				}
				continue;
			}
			$product_remaining_stock = \YAYDP\Helper\YAYDP_Helper::get_remaining_product_stock( $cart, $product );
			$product_remaining_stock = \YAYDP\Helper\YAYDP_Helper::get_remaining_product_stock_include_extra_items( $product_id, $product_remaining_stock, $all_extra_items );
			if ( $product_remaining_stock >= $receive_quantity ) {
				$result[] = array(
					'quantity' => $receive_quantity,
					'item'     => $product,
				);
				\YAYDP\Helper\YAYDP_Helper::push_in_items( $clone_extra_items, $product_id, $receive_quantity );
				\YAYDP\Helper\YAYDP_Helper::push_in_items( $all_extra_items, $product_id, $receive_quantity );
				$receive_quantity = 0;
			}
		}
		return $result;
	}

	public static function get_discount_receive_items( $cart, $matching_items, &$receive_quantity, $receive_quantity_per_unit, $bought_cases ) {
		$result = array();
		foreach ( $matching_items as $item ) {
			if ( empty( $receive_quantity ) ) {
				break;
			}
			$min_require_quantity   = \YAYDP\Helper\YAYDP_Helper::get_min_require_quantity( $bought_cases, $item );
			$is_item_in_bought_case = $min_require_quantity > 0;

			if ( $is_item_in_bought_case ) {
				$product                             = $item->get_product();
				$product_quantity_in_cart            = \yaydp_get_current_quantity_in_cart( $cart, $product );
				$total_buy_and_get_quantity_per_unit = $min_require_quantity + $receive_quantity_per_unit;
				$discount_time                       = floor( floatval( $product_quantity_in_cart ) / floatval( $total_buy_and_get_quantity_per_unit ) );
				$discount_quantity                   = min( $product_quantity_in_cart, $discount_time * $receive_quantity_per_unit );
				$excess_quantity                     = $discount_quantity % $receive_quantity_per_unit;
				if ( ! empty( $excess_quantity ) ) {
					$discount_quantity -= $excess_quantity;
				}
				if ( empty( $discount_quantity ) ) {
					continue;
				}
				$result[]          = array(
					'quantity' => $discount_quantity,
					'item'     => $item,
				);
				$receive_quantity -= $discount_quantity;
			} else {
				$item_quantity     = $item->get_quantity();
				$discount_quantity = min( $item_quantity, $receive_quantity );
				$excess_quantity   = $discount_quantity % $receive_quantity_per_unit;
				if ( ! empty( $excess_quantity ) ) {
					$discount_quantity -= $excess_quantity;
				}
				if ( empty( $discount_quantity ) ) {
					continue;
				}
				$result[]          = array(
					'quantity' => $discount_quantity,
					'item'     => $item,
				);
				$receive_quantity -= $discount_quantity;
			}
		}
		return $result;
	}
}
