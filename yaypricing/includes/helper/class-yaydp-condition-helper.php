<?php
/**
 * YayPricing condition helpers
 *
 * @package YayPricing\Helper
 * @version 1.0.0
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Condition_Helper {

	/**
	 * Check if cart match conditions of rule.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 * @param object                 $rule Checking rule.
	 *
	 * @return bool
	 */
	public static function check_conditions( $cart, $rule ) {
		$match_type = $rule->get_condition_match_type();
		$conditions = $rule->get_conditions();
		$cart_items = $cart->get_items();
		return self::check_list_conditions( $conditions, $match_type, $cart_items, $rule );
	}

	public static function check_list_conditions( $conditions, $match_type, $cart_items, $rule = null ) {
		$check = true;
		foreach ( $conditions as $condition ) {
			switch ( $condition['type'] ) {
				case 'cart_subtotal_price':
					$check = self::check_cart_subtotal_price( $cart_items, $condition );
					break;
				case 'cart_quantity':
					$check = self::check_cart_quantity( $cart_items, $condition );
					break;
				/**
				 * Check total weight
				 *
				 * @since 2.1
				 */
				case 'cart_total_weight':
					$check = self::check_cart_total_weight( $condition );
					break;
				case 'cart_item':
					$check = self::check_cart_item( $cart_items, $condition );
					break;
				case 'cart_item_regular_price':
					$check = self::check_cart_item_regular_price( $cart_items, $condition );
					break;
				/**
				 * @since 2.4.4
				 */
				case 'product_variation':
					$check = self::check_product_variation( $cart_items, $condition );
					break;
				/**
				 * Check cart item match list category
				 *
				 * @since 2.2
				 */
				case 'cart_item_category':
					$check = self::check_cart_item_category( $cart_items, $condition );
					break;
				/**
				 * Check cart item match list tag
				 *
				 * @since 2.2
				 */
				case 'cart_item_tag':
					$check = self::check_cart_item_tag( $cart_items, $condition );
					break;
				case 'logged_customer':
					$check = self::check_logged_customer( $condition );
					break;
				case 'customer_role':
					$check = self::check_customer_role( $condition );
					break;
				case 'specific_customer':
					$check = self::check_specific_customer( $condition );
					break;
				case 'customer_order_count':
					$check = self::check_customer_order_count( $condition );
					break;
					/**
				 * @since 2.4.5
				 */
				case 'customer_order_count_from_last_discount':
					$check = self::check_customer_order_count_from_last_discount( $condition, $rule );
					break;
				case 'shipping_region':
					$check = self::check_shipping_region( $condition );
					break;
				/**
				 * @since 3.4.2
				 */
				case 'billing_region':
					$check = self::check_billing_region( $condition );
					break;
				case 'payment_method':
					$check = self::check_payment_method( $condition );
					break;
				case 'shipping_method':
					$check = self::check_shipping_method( $condition );
					break;
				case 'shipping_class':
					$check = self::check_shipping_class( $condition );
					break;
				case 'applied_coupons':
					$check = self::check_applied_coupons( $condition );
					break;
				case 'shipping_total':
					$check = self::check_shipping_total( $condition );
					break;
				case 'bought_products':
					$check = self::check_bought_products( $condition );
					break;
				/**
				 * Check whether has orders purchased on date
				 *
				 * @since 2.2
				 */
				case 'orders_purchased_date':
					$check = self::check_orders_purchased_date( $condition );
					break;
				/**
				 * Check whether history order match condition
				 *
				 * @since 2.2
				 */
				case 'order_history_product':
					$check = self::check_order_history_product( $condition );
					break;
				/**
				 * Check whether history order match condition
				 *
				 * @since 2.2
				 */
				case 'order_history_category':
					$check = self::check_order_history_category( $condition );
					break;
				/**
				 * Check combined item condition
				 *
				 * @since 2.2
				 */
				case 'combined_items_condition':
					$check = true;
					break;
				/**
				 * Check combined item condition
				 *
				 * @since 3.1.1
				 */
				case 'previous_order_in':
					$check = self::check_previous_order_in( $condition );
					break;
				/**
				 * Check account created time
				 *
				 * @since 3.4.2
				 */
				case 'account_create_time':
					$check = self::check_account_create_time( $condition );
					break;
				/**
				 * @since 3.4.1
				 */
				case 'product_attribute_taxonomies':
					$check = self::check_product_attribute_taxonomies( $cart_items, $condition );
					break;
				/**
				 * @since 3.5.2
				 */
				case 'user_creation_date':
					$check = self::check_user_creation_date($condition);
					break;	
				default:
					$check = apply_filters( 'yaydp_check_' . $condition['type'] . '_condition', false, $condition );
					break;
			}
			if ( 'any' === $match_type ) {
				if ( $check ) {
					break;
				}
			} else {
				if ( ! $check ) {
					break;
				}
			}
		}
		return $check;
	}

	/**
	 * Check if cart match subtotal price condition.
	 *
	 * @param array $cart_items Cart items.
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_cart_subtotal_price( $cart_items, $condition ) {
		$subtotal = 0;
		foreach ( $cart_items as $cart_item ) {

			$cart_item_quantity = $cart_item->get_quantity();
			$cart_item_price    = $cart_item->get_store_price(); // TODO: split settings to overlappsed rules
			$subtotal          += $cart_item_quantity * $cart_item_price;

		}

		$check = \yaydp_compare_numeric( $subtotal, $condition['value'], $condition['comparation'] );

		return $check;
	}

	/**
	 * Check if cart match quantity condition.
	 *
	 * @param array $cart_items Cart items.
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_cart_quantity( $cart_items, $condition ) {
		$cart_item_quantity = 0;
		foreach ( $cart_items as $cart_item ) {
			$cart_item_quantity += $cart_item->get_quantity();
		}
		$check = \yaydp_compare_numeric( $cart_item_quantity, $condition['value'], $condition['comparation'] );

		return $check;
	}


	/**
	 * Check if cart match item condition.
	 *
	 * @param array $cart_items Cart items.
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_cart_item( $cart_items, $condition ) {

		$matching_items = self::get_cart_items_match_product( $cart_items, $condition );
		if ( ! empty( $matching_items ) ) {
			return true;
		}
		return false;

	}

	/**
	 * Check if customer is logged in.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_logged_customer( $condition ) {
		return $condition['comparation'] ? \is_user_logged_in() : ! \is_user_logged_in();
	}

	/**
	 * Check if customer match role.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_customer_role( $condition ) {
		$current_user       = \wp_get_current_user();
		$condition_values   = array_map(
			function( $item ) {
				return $item['value'];
			},
			$condition['value']
		);
		$intersection_roles = array_intersect( $condition_values, $current_user->roles );
		return 'in_list' === $condition['comparation'] ? ! empty( $intersection_roles ) : empty( $intersection_roles );
	}

	/**
	 * Check if match specific customer.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_specific_customer( $condition ) {
		$current_user = \wp_get_current_user();
		$list_id      = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$in_list      = in_array( $current_user->ID, $list_id );
		return 'in_list' === $condition['comparation'] ? $in_list : ! $in_list;
	}

	/**
	 * Check customer order count.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_customer_order_count( $condition ) {
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id();
			$order_count     = \wc_get_customer_order_count( $current_user_id );
			return \yaydp_compare_numeric( $order_count, $condition['value'], $condition['comparation'] );
		}
		return false;
	}

	/**
	 * Check shipping region.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_shipping_region( $condition ) {
		$country_code   = strtoupper( \wc_clean( \WC()->customer->get_shipping_country() ) );
		$state_code     = strtoupper( \wc_clean( \WC()->customer->get_shipping_state() ) );
		$continent_code = strtoupper( \wc_clean( \WC()->countries->get_continent_code_for_country( $country_code ) ) );

		$country_name   = ! empty( $country_code ) ? 'country:' . esc_attr( $country_code ) : '';
		$state_name     = ! empty( $state_code ) ? 'state:' . esc_attr( $country_code . ':' . $state_code ) : '';
		$continent_name = ! empty( $continent_code ) ? 'continent:' . esc_attr( $continent_code ) : '';

		$in_list = false;

		foreach ( $condition['value'] as $value ) {
			if ( str_contains( $value, 'continent' ) ) {
				if ( $value === $continent_name ) {
					$in_list = true;
				}
			}
			if ( str_contains( $value, 'country' ) ) {
				if ( $value === $country_name ) {
					$in_list = true;
				}
			}
			if ( str_contains( $value, 'state' ) ) {
				if ( $value === $state_name ) {
					$in_list = true;
				}
			}
		}

		return 'in_list' === $condition['comparation'] ? $in_list : ! $in_list;
	}

	/**
	 * Check payment method.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_payment_method( $condition ) {
		$session = isset( \WC()->session ) ? \WC()->session : false;
		if ( ! $session ) {
			return false;
		}
		
		if ( class_exists( 'Woocommerce_German_Market' ) ) {
			if ( ! empty( WC()->session->get( 'german_market_wc_blocks_active_payment_method' ) ) ) {
				$chosen_payment_method = WC()->session->get( 'german_market_wc_blocks_active_payment_method' );
			} else {
				$payment_gateways = WC()->payment_gateways()->payment_gateways();
				$payment_gateways = array_filter( $payment_gateways, function( $gateway ) {
					return $gateway->enabled === 'yes';
				} );
				if (!empty($payment_gateways)) {
					$first_payment_method = reset($payment_gateways);
					$chosen_payment_method = $first_payment_method->id;
				}
			}
		} else {
			if ( has_block( 'woocommerce/checkout' ) ) {
				$payment_gateways = WC()->payment_gateways()->payment_gateways();
				$payment_gateways = array_filter( $payment_gateways, function( $gateway ) {
					return $gateway->enabled === 'yes';
				} );
				if (!empty($payment_gateways)) {
					$first_payment_method = reset($payment_gateways);
					$chosen_payment_method = $first_payment_method->id;
				}
			} else {
				$chosen_payment_method = is_string( $session->get( 'chosen_payment_method' ) ) && ! empty( $session->get( 'chosen_payment_method' ) ) ? $session->get( 'chosen_payment_method' ) : null;
			}
		}

		if ( empty( $chosen_payment_method ) ) {
			return false;
		}

		$list_id = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$in_list = in_array( $chosen_payment_method, $list_id, true );

		return 'in_list' === $condition['comparation'] ? $in_list : ! $in_list;
	}

	/**
	 * Check shipping total.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_shipping_total( $condition ) {
		$total_shipping_fee = \yaydp_get_shipping_fee();
		return \yaydp_compare_numeric( $total_shipping_fee, $condition['value'], $condition['comparation'] );
	}

	/**
	 * Check cart total weight.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_cart_total_weight( $condition ) {
		$total_weight = \yaydp_get_cart_total_weight();
		return \yaydp_compare_numeric( $total_weight, $condition['value'], $condition['comparation'] );
	}

	/**
	 * Check cart regular price.
	 *
	 * @param array $cart_items Cart items.
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_cart_item_regular_price( $cart_items, $condition ) {
		$regular_price_product_count = 0;
		foreach ( $cart_items as $cart_item ) {
			$is_sale_product = $cart_item->is_sale_product();
			if ( ! $is_sale_product ) {
				$regular_price_product_count += $cart_item->get_quantity();
			}
		}

		$check = \yaydp_compare_numeric( $regular_price_product_count, $condition['value'], $condition['comparation'] );

		return $check;
	}

	/**
	 * Check match purchased date condition.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_orders_purchased_date( $condition ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$current_user_id = get_current_user_id();
		$args            = array(
			'customer_id' => $current_user_id,
			'limit'       => '1',
		);
		switch ( $condition['comparation'] ) {
			case 'before':
				$args['date_before'] = $condition['value'];
				break;
			case 'after':
				$args['date_after'] = $condition['value'];
				break;
			case 'in_range':
				$args['date_after']  = isset( $condition['value'][0] ) ? $condition['value'][0] : '';
				$args['date_before'] = isset( $condition['value'][1] ) ? $condition['value'][1] : '';
				break;
			default:
				$args['date_created'] = $condition['value'];
				break;

		}
		$orders = \wc_get_orders( $args );
		return ! empty( $orders );
	}

	private static function get_order_history_available_statuses() {
		return array_filter(
			array_keys( \wc_get_order_statuses() ),
			function( $status ) {
				if ( 'wc-refunded' === $status ) {
					return false;
				}
				return true;
			}
		);
	}

	/**
	 * Check match order history include product condition.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_order_history_product( $condition ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$current_user_id = get_current_user_id();
		$args            = array(
			'customer_id' => $current_user_id,
			'limit'       => '-1',
			'status'      => self::get_order_history_available_statuses(),
		);
		$orders          = \wc_get_orders( $args );
		foreach ( $orders as $order ) {
			$products_in_order = array();
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				if ( empty( $product ) ) {
					continue;
				}
				$product_parent_id   = $product->get_parent_id();
				$products_in_order[] = $product->get_id();
				if ( ! empty( $product_parent_id ) ) {
					$products_in_order[] = $product_parent_id;
				}
			}
			$products_in_order = array_unique( $products_in_order );
			$list_id           = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
			$array_intersect   = array_intersect( $list_id, $products_in_order );
			if ( 'contain' === $condition['comparation'] ) {
				if ( ! empty( $array_intersect ) ) {
					return true;
				}
			}

			if ( 'not_contain' === $condition['comparation'] ) {
				if ( empty( $array_intersect ) ) {
					return true;
				}
			}

			if ( 'contain_all' === $condition['comparation'] ) {
				if ( count( $array_intersect ) === count( $list_id ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check match order history include category condition.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_order_history_category( $condition ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$current_user_id = get_current_user_id();
		$args            = array(
			'customer_id' => $current_user_id,
			'limit'       => '-1',
			'status'      => self::get_order_history_available_statuses(),
		);
		$orders          = \wc_get_orders( $args );
		foreach ( $orders as $order ) {
			$order_cat_ids = array();
			foreach ( $order->get_items() as $item ) {
				$product       = $item->get_product();
				$product_cats  = \YAYDP\Helper\YAYDP_Product_Helper::get_product_cats( $product );
				$order_cat_ids = array_merge( $product_cats, $order_cat_ids );
			}
			$order_cat_ids    = array_unique( $order_cat_ids );
			$list_category_id = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
			$array_intersect  = array_intersect( $list_category_id, $order_cat_ids );
			if ( 'contain' === $condition['comparation'] ) {
				if ( ! empty( $array_intersect ) ) {
					return true;
				}
			}

			if ( 'not_contain' === $condition['comparation'] ) {
				if ( empty( $array_intersect ) ) {
					return true;
				}
			}

			if ( 'contain_all' === $condition['comparation'] ) {
				if ( count( $array_intersect ) === count( $list_category_id ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check combined condition.
	 *
	 * @param array $cart_items Cart items.
	 * @param array $combined_condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_combined_items( $cart_items, $combined_condition ) {
		$conditions          = $combined_condition['value'];
		$matching_cart_items = $cart_items;
		$check               = false;
		foreach ( $conditions as $condition ) {
			if ( 'items_product' === $condition['type'] ) {
				$matching_cart_items = self::get_matching_cart_items_product( $matching_cart_items, $condition );
			}
			if ( 'items_category' === $condition['type'] ) {
				$matching_cart_items = self::get_matching_cart_items_category( $matching_cart_items, $condition );
			}
			if ( 'items_tag' === $condition['type'] ) {
				$matching_cart_items = self::get_matching_cart_items_tag( $matching_cart_items, $condition );
			}
		}
		if ( count( $matching_cart_items ) > 0 ) {
			$check = true;
		}
		foreach ( $conditions as $condition ) {
			if ( 'items_quantity' === $condition['type'] ) {
				$check = self::check_cart_quantity( $matching_cart_items, $condition );
				if ( ! $check ) {
					return false;
				}
			}
			if ( 'items_subtotal' === $condition['type'] ) {
				$check = self::check_cart_subtotal_price( $matching_cart_items, $condition );
				if ( ! $check ) {
					return false;
				}
			}
		}
		return $check;
	}

	/**
	 * Returns cart items that match product condition.
	 *
	 * @since 2.2
	 * @param array $cart_items Checking cart items.
	 * @param array $condition  Checking product condition.
	 *
	 * @return array
	 */
	public static function get_cart_items_match_product( $cart_items, $condition ) {
		$matching_items     = array();
		$not_matching_items = array();
		$products_in_cart   = array();
		$id_list            = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$translated_id_list = apply_filters( 'yaydp_translated_list_object_id', $id_list, 'product' );
		foreach ( $cart_items as $cart_item ) {
			$product           = $cart_item->get_product();
			$product_id        = $product->get_id();
			$product_parent_id = $product->get_parent_id();
			if ( in_array( $product_id, $translated_id_list ) || in_array( $product_parent_id, $translated_id_list ) ) {
				$matching_items[] = $cart_item;
			} else {
				$not_matching_items[] = $cart_item;
			}
			$products_in_cart[] = $product_id;
			if ( ! empty( $product_parent_id ) ) {
				$products_in_cart[] = $product_parent_id;
			}
		}

		$products_in_cart = array_unique( $products_in_cart );
		$array_intersect  = array_intersect( $products_in_cart, $translated_id_list );

		if ( 'contain' === $condition['comparation'] ) {
			return ! empty( $array_intersect ) ? $matching_items : array();
		}

		if ( 'contain_all' === $condition['comparation'] ) {
			return count( $array_intersect ) === count( $id_list ) ? $matching_items : array();
		}

		return empty( $array_intersect ) ? $not_matching_items : array();
		// return $not_matching_items;
	}

	/**
	 * Returns cart items that match category condition.
	 *
	 * @since 2.2
	 * @param array $cart_items Checking cart items.
	 * @param array $condition  Checking category condition.
	 *
	 * @return array
	 */
	public static function get_cart_items_match_category( $cart_items, $condition ) {
		$matching_items              = array();
		$not_matching_items          = array();
		$cats_in_cart                = array();
		$list_category_id            = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$translated_list_category_id = apply_filters( 'yaydp_translated_list_object_id', $list_category_id, 'product_cat' );
		foreach ( $cart_items as $cart_item ) {
			$product      = $cart_item->get_product();
			$product_cats = \YAYDP\Helper\YAYDP_Product_Helper::get_product_cats( $product );
			$cats_in_cart = array_merge( $cats_in_cart, $product_cats );
			if ( ! empty( array_intersect( $product_cats, $translated_list_category_id ) ) ) {
				$matching_items[] = $cart_item;
			} else {
				$not_matching_items[] = $cart_item;
			}
		}

		$cats_in_cart    = array_unique( $cats_in_cart );
		$array_intersect = array_intersect( $cats_in_cart, $translated_list_category_id );

		if ( 'contain' === $condition['comparation'] ) {
			return ! empty( $array_intersect ) ? $matching_items : array();
		}

		if ( 'contain_all' === $condition['comparation'] ) {
			return count( $array_intersect ) === count( $list_category_id ) ? $matching_items : array();
		}

		return empty( $array_intersect ) ? $not_matching_items : array();
		// return $not_matching_items;
	}

	/**
	 * Returns cart items that match category condition.
	 *
	 * @since 2.2
	 * @param array $cart_items Checking cart items.
	 * @param array $condition  Checking category condition.
	 *
	 * @return array
	 */
	public static function get_cart_items_match_tag( $cart_items, $condition ) {
		$matching_items              = array();
		$not_matching_items          = array();
		$tags_in_cart                = array();
		$list_category_id            = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$translated_list_category_id = apply_filters( 'yaydp_translated_list_object_id', $list_category_id, 'product_tag' );
		foreach ( $cart_items as $cart_item ) {
			$product      = $cart_item->get_product();
			$product_tags = \YAYDP\Helper\YAYDP_Product_Helper::get_product_tags( $product );
			$tags_in_cart = array_merge( $tags_in_cart, $product_tags );
			if ( ! empty( array_intersect( $product_tags, $translated_list_category_id ) ) ) {
				$matching_items[] = $cart_item;
			} else {
				$not_matching_items[] = $cart_item;
			}
		}

		$tags_in_cart    = array_unique( $tags_in_cart );
		$array_intersect = array_intersect( $tags_in_cart, $translated_list_category_id );

		if ( 'contain' === $condition['comparation'] ) {
			return ! empty( $array_intersect ) ? $matching_items : array();
		}

		if ( 'contain_all' === $condition['comparation'] ) {
			return count( $array_intersect ) === count( $list_category_id ) ? $matching_items : array();
		}

		return empty( $array_intersect ) ? $not_matching_items : array();
		// return $not_matching_items;
	}

	/**
	 * Check if cart items match list category.
	 *
	 * @since 2.2
	 * @param array $cart_items Checking cart items.
	 * @param array $condition  Checking category condition.
	 *
	 * @return bool
	 */
	public static function check_cart_item_category( $cart_items, $condition ) {
		$matching_items = self::get_cart_items_match_category( $cart_items, $condition );

		if ( ! in_array( $condition['comparation'], array( 'contain', 'contain_all' ) ) ) {
			return empty( $matching_items );
		}
		if ( ! empty( $matching_items ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if cart items match list tag.
	 *
	 * @since 2.2
	 * @param array $cart_items Checking cart items.
	 * @param array $condition  Checking tag condition.
	 *
	 * @return bool
	 */
	public static function check_cart_item_tag( $cart_items, $condition ) {
		$matching_items = self::get_cart_items_match_tag( $cart_items, $condition );

		if ( ! in_array( $condition['comparation'], array( 'contain', 'contain_all' ) ) ) {
			return empty( $matching_items );
		}

		if ( ! empty( $matching_items ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @since 2.4.4
	 */
	public static function check_product_variation( $cart_items, $condition ) {

		$matching_items = self::get_cart_items_match_product( $cart_items, $condition );
		if ( ! empty( $matching_items ) ) {
			return true;
		}
		return false;

	}

	/**
	 * Check customer order count from last discount.
	 *
	 * @since 2.4.5
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_customer_order_count_from_last_discount( $condition, $rule ) {
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id();
			$args            = array(
				'customer_id' => $current_user_id,
				'limit'       => $condition['value'] + 1,
			);
			$orders          = \wc_get_orders( $args );
			$last_discount   = -1;
			foreach ( $orders as $index => $order ) {
				$meta_name = 'yaydp_product_pricing_rules';
				if ( \yaydp_is_cart_discount( $rule ) ) {
					$meta_name = 'yaydp_cart_discount_rules';
				}

				if ( \yaydp_is_checkout_fee( $rule ) ) {
					$meta_name = 'yaydp_checkout_fee_rules';
				}
				$meta_value = get_post_meta( $order->get_id(), $meta_name, true );
				if ( is_array( $meta_value ) && in_array( $rule->get_id(), $meta_value ) ) {
					$last_discount = $index;
					break;
				}
			}

			if ( -1 === $last_discount ) {
				return true;
			}
			$order_count = $last_discount;

			return \yaydp_compare_numeric( $order_count, $condition['value'], $condition['comparation'] );
		}
		return false;
	}

	/**
	 * Check payment method.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_applied_coupons( $condition ) {
		if ( ! empty( \WC()->cart ) ) {
			$coupons = \WC()->cart->get_applied_coupons();
		} else {
			$coupons = array();
		}

		if ( empty( $coupons ) && 'in_list' !== $condition['comparation'] ) {
			return false;
		}

		$coupons          = array_map(
			function( $code ) {
				return strtolower( $code );
			},
			$coupons
		);
		$list_coupon_id   = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$list_coupon_code = array_map(
			function( $id ) {
				$code = \wc_get_coupon_code_by_id( $id );
				if ( ! is_null( $code ) ) {
					  $code = strtolower( $code );
				}
				return $code;
			},
			$list_coupon_id
		);
		$array_intersect  = array_intersect( $coupons, $list_coupon_code );

		return 'in_list' === $condition['comparation'] ? ! empty( $array_intersect ) : empty( $array_intersect );
	}

	public static function check_bought_products( $condition ) {
		$current_user = \wp_get_current_user();

		if ( empty( $current_user ) ) {
			return false;
		}

		$current_user_id    = $current_user->ID;
		$current_user_email = $current_user->user_email;

		$list_product_id = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );

		$bought_products = array();

		foreach ( $list_product_id as $product_id ) {
			if ( function_exists( 'wc_customer_bought_product' ) && \wc_customer_bought_product( $current_user_email, $current_user_id, $product_id ) ) {
				$bought_products[] = $product_id;
			}
		}

		if ( 'contain' === $condition['comparation'] ) {
			return count( $bought_products ) > 0;
		}

		if ( 'not_contain' === $condition['comparation'] ) {
			return count( $bought_products ) === 0;
		}

		if ( 'contain_all' === $condition['comparation'] ) {
			return ( count( $bought_products ) === count( $list_product_id ) );
		}
	}

	/**
	 * Check match purchased date condition.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @since 3.1.1
	 * @return bool
	 */
	public static function check_previous_order_in( $condition ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$condition_value = floatval( $condition['value'] );
		$condition_value = max( 0, $condition_value );
		$date            = new \DateTime( "- $condition_value day" );
		$check_date      = $date->format( 'Y-m-d H:i:s' );

		$current_user_id = get_current_user_id();
		$args            = array(
			'customer_id' => $current_user_id,
			'limit'       => '1',
			'status'      => array_filter(
				array_keys( \wc_get_order_statuses() ),
				function( $s ) {
					return ! in_array( $s, array( 'wc-checkout-draft', 'wc-failed' ) ); }
			),
		);

		switch ( $condition['comparation'] ) {
			case 'greater_than':
			case 'gte':
				$args['date_before'] = $check_date;
				break;
			case 'less_than':
			case 'lte':
				$args['date_after'] = $check_date;
				break;

		}

		$orders = \wc_get_orders( $args );

		if ( empty( $orders ) ) {
			return false;
		}

		$last_order = $orders[0];

		return ! empty( $orders );
	}

	/**
	 * @since 3.4.1
	 */
	public static function check_product_attribute_taxonomies( $cart_items, $condition ) {

		$list_attribute_id = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );

		$cart_attributes = array();

		foreach ( $cart_items as $item ) {
			$item_product       = $item->get_product();
			$product_attributes = $item_product->get_attributes();
			if ( \yaydp_is_variation_product( $item_product ) ) {
				$parent_id = $item_product->get_parent_id();
				$parent    = \wc_get_product( $parent_id );
				if ( $parent ) {
					$parent_attributes = $parent->get_attributes();
					foreach ( $parent_attributes as $attribute ) {
						if ( $attribute instanceof \WC_Product_Attribute && $attribute['visible'] && ! $attribute['variation'] ) {
							$product_attributes[ $attribute['name'] ] = $attribute;
						}
					}
				}
			}

			$cart_attributes = array_merge( $cart_attributes, array_keys( $product_attributes ) );
		}

		$cart_attributes = array_unique( $cart_attributes );

		$array_intersect = array_intersect( $cart_attributes, $list_attribute_id );

		if ( 'contain_all' === $condition['comparation'] ) {
			return count( $array_intersect ) === count( $list_attribute_id );
		}

		return 'contain' === $condition['comparation'] ? ! empty( $array_intersect ) : empty( $array_intersect );

	}

	/**
	 * Check billing region.
	 *
	 * @param array $condition Checking condition.
	 *
	 * @return bool
	 */
	public static function check_billing_region( $condition ) {
		$country_code   = strtoupper( \wc_clean( \WC()->customer->get_billing_country() ) );
		$state_code     = strtoupper( \wc_clean( \WC()->customer->get_billing_state() ) );
		$continent_code = strtoupper( \wc_clean( \WC()->countries->get_continent_code_for_country( $country_code ) ) );

		$country_name   = ! empty( $country_code ) ? 'country:' . esc_attr( $country_code ) : '';
		$state_name     = ! empty( $state_code ) ? 'state:' . esc_attr( $country_code . ':' . $state_code ) : '';
		$continent_name = ! empty( $continent_code ) ? 'continent:' . esc_attr( $continent_code ) : '';

		$in_list = false;

		foreach ( $condition['value'] as $value ) {
			if ( str_contains( $value, 'continent' ) ) {
				if ( $value === $continent_name ) {
					$in_list = true;
				}
			}
			if ( str_contains( $value, 'country' ) ) {
				if ( $value === $country_name ) {
					$in_list = true;
				}
			}
			if ( str_contains( $value, 'state' ) ) {
				if ( $value === $state_name ) {
					$in_list = true;
				}
			}
		}

		return 'in_list' === $condition['comparation'] ? $in_list : ! $in_list;
	}

	/**
	 * Check account created time
	 *
	 * @param array $condition Checking condition.
	 *
	 * @since 3.4.2
	 * @return bool
	 */
	public static function check_account_create_time( $condition ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$condition_value     = floatval( $condition['value'] );
		$condition_value     = max( 0, $condition_value );
		$check_date          = new \DateTime( "- $condition_value day" );
		$current_user_id     = get_current_user_id();
		$current_user_data   = get_userdata( $current_user_id );
		$account_create_date = new \DateTime( $current_user_data->user_registered );

		if ( 'greater_than' === $condition['comparation'] ) {
			return $account_create_date < $check_date;
		}
		if ( 'less_than' === $condition['comparation'] ) {
			return $account_create_date > $check_date;
		}

		return false;

	}

	/**
	 * Check user creation date
	 * @since 3.5.2
	 */
	public static function check_user_creation_date( $condition ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$current_user         = \wp_get_current_user();
		$user_registered      = $current_user->user_registered;
		$user_registered_date = explode( " ", $current_user->user_registered )[0];

		switch ( $condition['comparation'] ) {
			case 'before':
				return $user_registered_date < $condition['value'];
			case 'after':
				return $user_registered_date > $condition['value'];
			case 'on':
				return $user_registered_date === $condition['value'];
			case 'in_range':
				return $user_registered_date >= $condition['value'][0] && $user_registered <= $condition['value'][1];
			default:
				return false;
		}
	}

	/**
	 * Check shipping method
	 *
	 * @param array $condition Checking condition.
	 *
	 * @since 3.5.2
	 * @return bool
	 */
	public static function check_shipping_method( $condition ) {
		$shipping_methods = \WC()->session->get( 'chosen_shipping_methods' );
		if ( ! empty( $_POST['shipping_method'] ) ) {
			$shipping_methods = $_POST['shipping_method'];
		}
		$list_id         = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$in_list         = false;

		foreach ( $shipping_methods as $method ) {
			$method_id = explode( ':', $method )[0];
			if ( in_array( $method, $list_id ) || in_array( $method_id, $list_id ) ) {
				$in_list = true;
				break;
			}
		}
		return 'in_list' === $condition['comparation'] ? $in_list : ! $in_list;
	}

	/**
	 * Check shipping class
	 *
	 * @param array $condition Checking condition.
	 *
	 * @since 3.5.2
	 * @return bool
	 */
	public static function check_shipping_class( $condition ) {

		$check = false;
		$list_id         = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			$product_shipping_class_id = $product->get_shipping_class_id();
			if ( empty( $product_shipping_class_id ) ) {
				continue;
			}
			if ( 'in_list' === $condition['comparation'] ) {
				if ( in_array( $product_shipping_class_id, $list_id ) ) {
					$check = true;
					break;
				}
			} else {
				if ( ! in_array( $product_shipping_class_id, $list_id ) ) {
					$check = true;
					break;
				}
			}
		}

		return $check;
	}

	/**
	 * Returns cart items that match product condition.
	 *
	 * @since 2.2
	 * @param array $cart_items Checking cart items.
	 * @param array $condition  Checking product condition.
	 *
	 * @return array
	 */
	public static function get_matching_cart_items_product( $cart_items, $condition ) {
		$matching_items     = array();
		$not_matching_items = array();
		$products_in_cart   = array();
		$id_list            = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$translated_id_list = apply_filters( 'yaydp_translated_list_object_id', $id_list, 'product' );
		foreach ( $cart_items as $cart_item ) {
			$product           = $cart_item->get_product();
			$product_id        = $product->get_id();
			$product_parent_id = $product->get_parent_id();
			if ( in_array( $product_id, $translated_id_list ) || in_array( $product_parent_id, $translated_id_list ) ) {
				$matching_items[] = $cart_item;
			} else {
				$not_matching_items[] = $cart_item;
			}
			$products_in_cart[] = $product_id;
			if ( ! empty( $product_parent_id ) ) {
				$products_in_cart[] = $product_parent_id;
			}
		}

		$products_in_cart = array_unique( $products_in_cart );
		$array_intersect  = array_intersect( $products_in_cart, $translated_id_list );

		if ( 'contain' === $condition['comparation'] ) {
			return ! empty( $array_intersect ) ? $matching_items : array();
		}

		if ( 'contain_all' === $condition['comparation'] ) {
			return count( $array_intersect ) === count( $id_list ) ? $matching_items : array();
		}

		// return empty( $array_intersect ) ? $not_matching_items : array();
		return $not_matching_items;
	}

	/**
	 * Returns cart items that match category condition.
	 *
	 * @since 2.2
	 * @param array $cart_items Checking cart items.
	 * @param array $condition  Checking category condition.
	 *
	 * @return array
	 */
	public static function get_matching_cart_items_category( $cart_items, $condition ) {
		$matching_items              = array();
		$not_matching_items          = array();
		$cats_in_cart                = array();
		$list_category_id            = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$translated_list_category_id = apply_filters( 'yaydp_translated_list_object_id', $list_category_id, 'product_cat' );
		foreach ( $cart_items as $cart_item ) {
			$product      = $cart_item->get_product();
			$product_cats = \YAYDP\Helper\YAYDP_Product_Helper::get_product_cats( $product );
			$cats_in_cart = array_merge( $cats_in_cart, $product_cats );
			if ( ! empty( array_intersect( $product_cats, $translated_list_category_id ) ) ) {
				$matching_items[] = $cart_item;
			} else {
				$not_matching_items[] = $cart_item;
			}
		}

		$cats_in_cart    = array_unique( $cats_in_cart );
		$array_intersect = array_intersect( $cats_in_cart, $translated_list_category_id );

		if ( 'contain' === $condition['comparation'] ) {
			return ! empty( $array_intersect ) ? $matching_items : array();
		}

		if ( 'contain_all' === $condition['comparation'] ) {
			return count( $array_intersect ) === count( $list_category_id ) ? $matching_items : array();
		}

		// return empty( $array_intersect ) ? $not_matching_items : array();
		return $not_matching_items;
	}

	/**
	 * Returns cart items that match category condition.
	 *
	 * @since 2.2
	 * @param array $cart_items Checking cart items.
	 * @param array $condition  Checking category condition.
	 *
	 * @return array
	 */
	public static function get_matching_cart_items_tag( $cart_items, $condition ) {
		$matching_items              = array();
		$not_matching_items          = array();
		$tags_in_cart                = array();
		$list_category_id            = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$translated_list_category_id = apply_filters( 'yaydp_translated_list_object_id', $list_category_id, 'product_tag' );
		foreach ( $cart_items as $cart_item ) {
			$product      = $cart_item->get_product();
			$product_tags = \YAYDP\Helper\YAYDP_Product_Helper::get_product_tags( $product );
			$tags_in_cart = array_merge( $tags_in_cart, $product_tags );
			if ( ! empty( array_intersect( $product_tags, $translated_list_category_id ) ) ) {
				$matching_items[] = $cart_item;
			} else {
				$not_matching_items[] = $cart_item;
			}
		}

		$tags_in_cart    = array_unique( $tags_in_cart );
		$array_intersect = array_intersect( $tags_in_cart, $translated_list_category_id );

		if ( 'contain' === $condition['comparation'] ) {
			return ! empty( $array_intersect ) ? $matching_items : array();
		}

		if ( 'contain_all' === $condition['comparation'] ) {
			return count( $array_intersect ) === count( $list_category_id ) ? $matching_items : array();
		}

		// return empty( $array_intersect ) ? $not_matching_items : array();
		return $not_matching_items;
	}

}
