<?php
/**
 * YayPricing helpers
 *
 * @package YayPricing\Helper
 * @version 1.0.0
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Helper {

	/**
	 * Checks the nonce value to ensure that the request is coming from a trusted source
	 *
	 * @throws \Exception Throw error when nonce is invalid.
	 */
	public static function check_nonce() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'yaydp_nonce' ) ) {
			throw new \Exception( __( 'Nonce is invalid', 'yaypricing' ) );
		}
	}

	/**
	 * Sanitize an array. Avoid take XSS from client
	 *
	 * @param array $value The input array.
	 */
	public static function sanitize_array( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'self::sanitize_array', $value );
		} else {
			return sanitize_text_field( $value );
		}
	}

	/**
	 * Verify nonce from Rest request
	 *
	 * @since 2.3
	 * @param \WP_REST_Request $request Comming request.
	 */
	public static function verify_rest_nonce( \WP_REST_Request $request ) {
		$nonce = $request->get_header( 'x_wp_nonce' );
		return wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Return rest response failure for verifying nonce
	 *
	 * @since 2.3
	 */
	public static function get_verify_rest_nonce_failure_response() {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => 'Verify nonce failed',
			)
		);
	}

	/**
	 * Get list value from filter.
	 *
	 * @param array $filter Given filter.
	 *
	 * @return array
	 */
	public static function map_filter_value( $filter ) {
		return array_map(
			function( $f ) {
				return $f['value'];
			},
			$filter['value']
		);
	}

	/**
	 * Get list value from filter.
	 *
	 * @param array $filter Given filter.
	 *
	 * @return array
	 */
	public static function map_filter_title( $filter ) {
		return array_map(
			function( $f ) {
				return $f['title'] ?? '';
			},
			$filter['title']
		);
	}

	/**
	 * Whether a rule's product filters apply to a product: rule-level gates
	 * (third-party veto, exclusions, publish status, discount settings) first,
	 * then the filter list through the product filter registry.
	 *
	 * @param array       $filters    Given filters.
	 * @param \WC_Product $product    Given product.
	 * @param string      $match_type Match type.
	 * @param string|null $item_key   Cart item key when evaluating a cart line.
	 * @param object|null $rule       Rule, for exclusion checks.
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	public static function check_applicability( $filters, $product, $match_type = 'any', $item_key = null, $rule = null ) {

		$third_party_check = apply_filters( 'yaydp_product_applicability_check', true, $product, $filters, $match_type, $item_key, $rule );

		if ( ! $third_party_check ) {
			return false;
		}

		if ( $rule && \YAYDP\Core\Manager\YAYDP_Exclude_Manager::check_product_exclusions( $rule, $product ) ) {
			return false;
		}

		if ( 'publish' !== $product->get_status() ) { // Only show published products
			return false;
		}

		if ( \yaydp_product_pricing_is_applied_to_non_discount_product() && \YAYDP\Core\Discounted_Products\YAYDP_Discounted_Products::get_instance()->is_discounted( $product ) ) {
			return false;
		}

		$disable_applying_when_on_sale = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->disable_when_on_sale();

 		if ( $disable_applying_when_on_sale && $product->is_on_sale() ) {
			return false;
		}

		return \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry::instance()->evaluate_list(
			$filters,
			$product,
			$match_type,
			new \YAYDP\Product_Filter\YAYDP_Product_Filter_Context( $item_key )
		);
	}

	/**
	 * Get product stock include item in cart.
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 * @param \WC_Product            $product Given product.
	 *
	 * @since 2.4
	 */
	public static function get_remaining_product_stock( \YAYDP\Core\YAYDP_Cart $cart, $product ) {
		$product_stock                    = \yaydp_get_stock_quantity( $product );
		$product_current_quantity_in_cart = \yaydp_get_current_quantity_in_cart( $cart, $product );
		return max( 0, $product_stock - $product_current_quantity_in_cart );
	}

	/**
	 * Get product stock include future extra item.
	 *
	 * @param float $product_id Product id.
	 * @param float $stock Product stock include item in cart.
	 * @param array $extra_items Future extra items.
	 *
	 * @since 2.4
	 */
	public static function get_remaining_product_stock_include_extra_items( $product_id, $stock, $extra_items ) {
		if ( isset( $extra_items[ $product_id ] ) ) {
			return max( 0, $stock - $extra_items[ $product_id ] );
		}
		return $stock;
	}

	/**
	 * Remove items from list future extra items.
	 *
	 * @param array $extra_items Future extra items.
	 * @param array $current_items Removing items.
	 *
	 * @since 2.4
	 */
	public static function take_back_items( &$extra_items, $current_items ) {
		foreach ( array_keys( $extra_items ) as $product_id ) {
			if ( isset( $current_items[ $product_id ] ) ) {
				$extra_items[ $product_id ] -= max( 0, $current_items[ $product_id ] );
			}
		}
	}

	/**
	 * Add items from list future extra items.
	 *
	 * @param array $extra_items Future extra items.
	 * @param float $product_id Given product id.
	 * @param float $quantity Adding quantity.
	 *
	 * @since 2.4
	 */
	public static function push_in_items( &$extra_items, $product_id, $quantity ) {
		if ( isset( $extra_items[ $product_id ] ) ) {
			$extra_items[ $product_id ] += $quantity;
		} else {
			$extra_items[ $product_id ] = $quantity;
		}
	}

	/**
	 * Wrap child with array
	 *
	 * @param array $cases Given array.
	 *
	 * @since 2.4
	 */
	public static function map_cases( $cases ) {
		return array_map(
			function( $case ) {
				return array(
					$case,
				);
			},
			$cases
		);
	}

	/**
	 * Create a custom cart item
	 *
	 * @param \WC_Product $product Given product.
	 * @param float       $quantity quantity of cart item.
	 *
	 * @since 2.4
	 */
	public static function initialize_custom_cart_item( $product, $quantity = 1, $price = null ) {
		$data = array(
			'key'      => null,
			'data'     => $product,
			'quantity' => $quantity,
		);
		if ( ! empty( $price ) ) {
			$data['custom_price'] = $price;
		}
		return new \YAYDP\Core\YAYDP_Cart_Item( $data );
	}

	/**
	 * Get min require quantity from bought cases
	 *
	 * @param array        $bought_cases Bought case.
	 * @param array|object $item Cart item.
	 *
	 * @since 2.4
	 */
	public static function get_min_require_quantity( $bought_cases, $item ) {
		return 0;
		// $result = 0;
		// foreach ( $bought_cases as $bought_case ) {
		// 	foreach ( $bought_case as $data ) {
		// 		foreach ( $data['items'] as $b_item ) {
		// 			if ( $b_item instanceof \WC_Product ) {
		// 				if ( $item->get_product()->get_id() === $b_item->get_id() ) {
		// 					$result += $data['quantity'];
		// 					break;
		// 				}
		// 			} else {
		// 				if ( $item->get_key() === $b_item->get_key() ) {
		// 					$result += $data['quantity'];
		// 					break;
		// 				}
		// 			}
		// 		}
		// 	}
		// 	if ( $result > 0 ) {
		// 		break;
		// 	}
		// }
		// return $result;
	}

	/**
	 * Replace hex color value of the rgb found on given string.
	 *
	 * @param string $string String to replace.
	 */
	public static function replace_rgb_to_hex( $string ) {
		$result = preg_replace_callback(
			'/rgb\([^\)]+\)/',
			function( $matches ) {
				$match_result = preg_match( '/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/', $matches[0], $rgb );
				if ( 1 !== $match_result ) {
					return '';
				}
				$r_hex_code = substr( self::str_to_hex( $rgb[1] ), -2 );
				$g_hex_code = substr( self::str_to_hex( $rgb[2] ), -2 );
				$b_hex_code = substr( self::str_to_hex( $rgb[3] ), -2 );
				return '#' .
				( strlen( $r_hex_code ) > 1 ? $r_hex_code : "0$r_hex_code" ) .
				( strlen( $g_hex_code ) > 1 ? $g_hex_code : "0$g_hex_code" ) .
				( strlen( $b_hex_code ) > 1 ? $b_hex_code : "0$b_hex_code" );
			},
			$string
		);
		return $result;
	}

	/**
	 * Returns hex value of the given string
	 *
	 * @param string $string String to parse to hex.
	 */
	public static function str_to_hex( $string ) {
		$hexstr = base_convert( intval( $string, 10 ), 10, 16 );
		return $hexstr;
	}

	/**
	 * Sorts an array of products by price in given order
	 *
	 * @param array  $products List products to be sorted.
	 * @param string $order Given order.
	 */
	public static function sort_products_by_price( &$products, $order = 'asc' ) {
		$product_ids = array_map(
			function( $product ) {
				return $product->get_id();
			},
			$products
		);
		foreach ( $products as $index => $product ) {
			if ( \yaydp_is_variable_product( $product ) ) {
				$children_id             = $product->get_children();
				$not_in_list_children_id = array_diff( $children_id, $product_ids );
				$children                = array_map(
					function( $id ) {
						return \wc_get_product( $id );
					},
					$not_in_list_children_id
				);
				unset( $products[ $index ] );
				$products = array_merge( $products, $children );
			}
		}
		usort(
			$products,
			function( $a, $b ) use ( $order ) {
				$a_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $a );
				$a_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_fixed_price( $a_price, $a );
				$b_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $b );
				$b_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_fixed_price( $b_price, $b );
				return 'asc' === $order ? $a_price <=> $b_price : $b_price <=> $a_price;
			}
		);
	}

	/**
	 * Sorts an array of cart items by price in given order
	 *
	 * Cart item is an instance of \YAYDP\Core\YAYDP_Cart_Item.
	 *
	 * @param array  $items List items to be sorted.
	 * @param string $order Given order.
	 */
	public static function sort_items_by_price( &$items, $order = 'asc' ) {
		usort(
			$items,
			function( $item_a, $item_b ) use ( $order ) {
				// Live cart-item price, not the static product price, so items already
				// discounted by another rule are compared fairly.
				$a_price = $item_a->get_price();
				$b_price = $item_b->get_price();
				return 'asc' === $order ? $a_price <=> $b_price : $b_price <=> $a_price;
			}
		);
	}

	/**
	 * Get matching bought cases pair
	 *
	 * @param array $bought_cases Bought cases.
	 *
	 * @since 2.4.1
	 */
	public static function get_matching_pairs( $bought_cases ) {
		$matching_pairs = array();
		foreach ( $bought_cases as $b_case ) {
			$sub_matching_pairs = array();
			foreach ( $b_case as $case ) {
				$tmp           = array();
				$quantity      = $case['quantity'];
				$splited_items = array_map(
					function( $item ) use ( $quantity ) {
						return array(
							'quantity'        => $quantity,
							'bought_quantity' => $item->get_quantity(),
							'items'           => array( $item ),
						);
					},
					$case['items']
				);
				if ( empty( $sub_matching_pairs ) ) {
					$tmp = array_map(
						function( $item ) {
							return array( $item );
						},
						$splited_items
					);
				} else {
					foreach ( $sub_matching_pairs as $pair ) {
						foreach ( $splited_items as $item ) {
							$tmp[] = array_merge( $pair, array( $item ) );
						}
					}
				}
				$sub_matching_pairs = $tmp;
			}
			$matching_pairs = array_merge( $matching_pairs, array( $sub_matching_pairs ) );
		}
		return $matching_pairs;
	}

	/**
	 * Separate attribute and option from array of attribute_option format strings
	 * 
	 * @param array $filters Array containing attribute_option strings.
	 * @return array Array containing separated attributes and options.
	 */
	public static function separate_attribute_option( $filters ) {
		$result = array();
		$values = self::map_filter_title( $filters );

		foreach ( $values as $attribute_option ) {
			$parts = explode( ':', $attribute_option );
			if ( count( $parts ) !== 2 ) {
				continue;
			}
			
			$result[] = array(
				'attribute' => $parts[0],
				'option'    => trim($parts[1]),
			);
		}

		return $result;
	}

	/**
	 * Translates an admin-entered string using WPML or Polylang when available,
	 * otherwise looks it up in the plugin text domain so translations added
	 * with Loco Translate (or any .po/.mo file) are applied.
	 * Falls back to the original text when no translation exists.
	 *
	 * @param string $text    The original admin-entered text.
	 * @param string $context WPML string context.
	 * @param string $name    WPML string name.
	 */
	public static function translate_user_string( $text, $context, $name ) {
		if ( empty( $text ) ) {
			return $text;
		}

		// WPML String Translation.
		if ( ! empty( $name ) && has_filter( 'wpml_translate_single_string' ) ) {
			return apply_filters( 'wpml_translate_single_string', $text, $context, $name );
		}

		// Polylang String Translation.
		if ( function_exists( 'pll__' ) ) {
			return pll__( $text );
		}

		// Loco Translate: the source string can be added manually to the yaypricing text domain.
		if ( function_exists( 'translate' ) ) {
			return \translate( $text, 'yaypricing' );
		}

		return $text;
	}
}