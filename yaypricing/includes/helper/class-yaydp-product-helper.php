<?php
/**
 * YayPricing product helper
 *
 * @package YayPricing\Helper
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Product_Helper {

	/**
	 * Check if the given product match with the filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The product id filter.
	 *
	 * @return boolean
	 */
	public static function check_product( $product, $filter, $sub_filter = null ) {
		$product_ids       = array();
		$product_parent_id = $product->get_parent_id();
		$product_ids[]     = $product->get_id();
		if ( ! empty( $product_parent_id ) ) {
			$product_ids[] = $product_parent_id;
		}
		$list_id         = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		$list_id         = apply_filters( 'yaydp_translated_list_object_id', $list_id, 'product' );
		if ( $sub_filter ) {
			$list_id = self::filter_products_by_sub_filter( $list_id, $sub_filter );
		}
		$array_intersect = array_intersect( $list_id, $product_ids );
		return 'in_list' === $filter['comparation'] ? ! empty( $array_intersect ) : empty( $array_intersect );
	}

	/**
	 * Check if the given product match with the given variation filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The variation filter.
	 *
	 * @return boolean
	 */
	public static function check_product_variation( $product, $filter, $sub_filter = null ) {
		$list_variation_id = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		$list_variation_id = apply_filters( 'yaydp_translated_list_object_id', $list_variation_id, 'product' );
		$product_ids       = array( $product->get_id() );
		if ( \yaydp_is_variable_product( $product ) || \yaydp_is_grouped_product( $product ) ) {
			$product_ids = array_merge( $product_ids, $product->get_children() );
		}
		if ( $sub_filter ) {
			$list_variation_id = self::filter_products_by_sub_filter( $list_variation_id, $sub_filter );
		}
		$array_intersect = array_intersect( $product_ids, $list_variation_id );
		return 'in_list' === $filter['comparation'] ? ! empty( $array_intersect ) : empty( $array_intersect );
	}

	/**
	 * Check if the given product match with the given category filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The category filter.
	 *
	 * @return boolean
	 */
	public static function check_category( $product, $filter, $sub_filter = null ) {
		$list_category_id = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		$list_category_id = apply_filters( 'yaydp_translated_list_object_id', $list_category_id, 'product_cat' );
		$product_cats     = self::get_product_cats( $product );		

		if ( $sub_filter ) {
			$list_category_id = self::filter_cats_by_sub_filter( $list_category_id, $product, $sub_filter );
		}

		$array_intersect  = array_intersect( $product_cats, $list_category_id );

		return 'in_list' === $filter['comparation'] ? ! empty( $array_intersect ) : empty( $array_intersect );
	}

	/**
	 * Check if the given product match with the given tag filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The tag filter.
	 *
	 * @return boolean
	 */
	public static function check_tag( $product, $filter ) {
		$list_tag_id     = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		$list_tag_id     = apply_filters( 'yaydp_translated_list_object_id', $list_tag_id, 'product_tag' );
		$product_tags    = self::get_product_tags( $product );
		$array_intersect = array_intersect( $product_tags, $list_tag_id );
		return 'in_list' === $filter['comparation'] ? ! empty( $array_intersect ) : empty( $array_intersect );
	}

	/**
	 * Check if the given product match with the given price filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The price filter.
	 *
	 * @return boolean
	 */
	public static function check_price( $product, $filter ) {
		if ( yaydp_is_variable_product( $product ) ) {
			$settings                  = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
			$is_based_on_regular_price = 'regular_price' === $settings->get_discount_base_on();
			$sale_price                = $product->get_variation_sale_price( 'min' );
			$regular_price             = $product->get_variation_regular_price( 'min' );
			if ( $is_based_on_regular_price ) {
				$product_price = $regular_price;
			} else {
				$product_price = ! empty( $sale_price ) ? $sale_price : $regular_price;
			}
		} else {
			$product_price = (float) \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		}
		$check = \yaydp_compare_numeric( $product_price, $filter['value'], $filter['comparation'] );
		return $check;
	}

	/**
	 * Check if the given product match with the given stock filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The stock filter.
	 *
	 * @return boolean
	 */
	public static function check_stock( $product, $filter ) {
		$stock_quantity = \yaydp_get_stock_quantity( $product );
		return \yaydp_compare_numeric( $stock_quantity, $filter['value'], $filter['comparation'] );
	}

	/**
	 * Retrieves all categories associated with a given product, including parent categories
	 *
	 * @since 2.2
	 * @param \WC_Product $product given product.
	 *
	 * @return array
	 */
	public static function get_product_cats( $product ) {
		$result          = array();
		$product_cats    = \get_the_terms( $product->get_id(), 'product_cat' );
		$product_cat_ids = array_map(
			function( $item ) {
				return $item->term_id;
			},
			$product_cats ? $product_cats : array()
		);
		foreach ( $product_cat_ids as $cat_id ) {
			$result[]    = $cat_id;
			$cat_parents = get_ancestors( $cat_id, 'product_cat' );
			$result      = array_merge( $result, $cat_parents );
		}
		$product_parent_id = $product->get_parent_id();
		if ( ! empty( $product_parent_id ) ) {
			$parent_product = \wc_get_product( $product_parent_id );
			$result         = array_merge( $result, self::get_product_cats( $parent_product ) );
		}
		return array_unique( $result );
	}

	/**
	 * Retrieves all tags associated with a given product, including parent tags
	 *
	 * @since 2.2
	 * @param \WC_Product $product given product.
	 *
	 * @return array
	 */
	public static function get_product_tags( $product ) {
		$result          = array();
		$product_cats    = \get_the_terms( $product->get_id(), 'product_tag' );
		$product_cat_ids = array_map(
			function( $item ) {
				return $item->term_id;
			},
			$product_cats ? $product_cats : array()
		);
		foreach ( $product_cat_ids as $cat_id ) {
			$result[]    = $cat_id;
			$cat_parents = get_ancestors( $cat_id, 'product_tag' );
			$result      = array_merge( $result, $cat_parents );
		}
		$product_parent_id = $product->get_parent_id();
		if ( ! empty( $product_parent_id ) ) {
			$parent_product = \wc_get_product( $product_parent_id );
			$result         = array_merge( $result, self::get_product_tags( $parent_product ) );
		}
		return array_unique( $result );
	}

	/**
	 * Check if the given product match with the given attribute filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The attributes filter.
	 *
	 * @return boolean
	 */
	public static function check_attribute( $product, $filter, $item_key = null ) {
		$list_attribute_id = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		$list_attributes   = array();
		foreach ( $list_attribute_id as $attribute_id ) {
			$term = get_term( $attribute_id );
			if ( is_null( $term ) || is_wp_error( $term ) ) {
				continue;
			}
			$list_attributes[] = array(
				'taxonomy'  => $term->taxonomy,
				'attribute' => $term->slug,
			);
		}
		$product_attributes = $product->get_attributes();
		if ( \yaydp_is_variation_product( $product ) ) {
			$parent_id = $product->get_parent_id();
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
		// TODO: process for variable product

		$in_list = false;

		foreach ( $list_attributes as $attribute_information ) {
			foreach ( $product_attributes as $taxonomy => $attribute ) {
				if ( $attribute_information['taxonomy'] === $taxonomy && $attribute instanceof \WC_Product_Attribute ) {
					foreach ( $attribute->get_options() as $term_id ) {
						$term = get_term( $term_id );
						if ( is_null( $term ) || is_wp_error( $term ) ) {
							continue;
						}
						if ( null != $term && ! is_wp_error( $term ) && $term->slug === $attribute_information['attribute'] ) {
							$in_list = true;
							break 3;
						}
					}
				}
				if ( $attribute_information['taxonomy'] === $taxonomy && $attribute_information['attribute'] === $attribute ) {
					$in_list = true;
					break 2;
				}
			}
		}

		if ( ! is_null( $item_key ) ) {
			foreach ( \WC()->cart->get_cart() as $cart_item ) {
				if ( $cart_item['key'] === $item_key && ! empty( $cart_item['variation'] ) ) {
					foreach ( $list_attributes as $attribute_information ) {
						foreach ( $cart_item['variation'] as $taxonomy => $variation ) {
							if ( 'attribute_' . $attribute_information['taxonomy'] === $taxonomy && $attribute_information['attribute'] === $variation ) {
								$in_list = true;
								break 2;
							}
						}
					}
				}
			}
		}

		return 'in_list' === $filter['comparation'] ? $in_list : ! $in_list;
	}

	/**
	 * Check if the given product match with the given attribute taxonomies filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The attribute taxonomies filter.
	 * @since 3.4.1
	 *
	 * @return boolean
	 */
	public static function check_attribute_taxonomies( $product, $filter, $item_key = null ) {
		$list_attribute_id  = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		$product_attributes = $product->get_attributes();
		if ( \yaydp_is_variation_product( $product ) ) {
			$parent_id = $product->get_parent_id();
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

		$array_intersect = array_intersect( $list_attribute_id, array_keys( $product_attributes ) );
		$in_list         = ! empty( $array_intersect );

		return 'in_list' === $filter['comparation'] ? $in_list : ! $in_list;
	}

	/**
	 * Check given product is on sale
	 *
	 * @since 3.4.2
	 */
	public static function check_on_sale_wc( $product, $filter ) {
		$is_on_sale = $product->is_on_sale();

		return 'on_sale' === $filter['comparation'] ? $is_on_sale : ! $is_on_sale;

	}

	/**
	 * Check given product is in the given shipping class
	 *
	 * @since 3.5.2
	 */
	public static function check_shipping_class( $product, $filter ) {
		$shipping_class_id = $product->get_shipping_class_id();
		$list_id         = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		return 'in_list' === $filter['comparation'] ? in_array( $shipping_class_id, $list_id, true ) : ! in_array( $shipping_class_id, $list_id, true );
	}

	/**
	 * Check if the given product match with the given cart price criterion filter
	 *
	 * @param \WC_Product $product Given product.
	 * @param array       $filter The cart price criterion filter.
	 *
	 * @return boolean
	 */
	public static function check_cart_item_price_criterion( $product, $filter ) {

		if ( ! \WC()->cart ) {
			return false;
		}

		$cart_items = \WC()->cart->get_cart();

		if ( empty( $cart_items ) ) {
			return false;
		}

		$sorted_cart_items = array();

		foreach ( $cart_items as $cart_item ) {
			$item_product = $cart_item['data'];
			if ( ! $item_product ) {
				continue;
			}
			if ( ! ( $item_product instanceof \WC_Product ) ) {
				continue;
			}
			$sorted_cart_items[] = [
				'price' => \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $item_product ),
				'product_id' => $item_product->get_id(),
			];
		}

		usort( $sorted_cart_items, function( $a, $b ) {
			return $b['price'] - $a['price'];
		} );

		if ( empty( $sorted_cart_items ) ) {
			return false;
		}

		if ( 'highest_price' === $filter['comparation'] ) {
			return $sorted_cart_items[0]['product_id'] === $product->get_id();
		}
		if ( 'lowest_price' === $filter['comparation'] ) {
			return $sorted_cart_items[ count( $sorted_cart_items ) - 1 ]['product_id'] === $product->get_id();
		}

		if ( isset( $sorted_cart_items[1] ) ) {
			if ( 'second_highest_price' === $filter['comparation'] ) {
				return $sorted_cart_items[1]['product_id'] === $product->get_id();
			}
	
			if ( 'second_lowest_price' === $filter['comparation'] ) {
				return $sorted_cart_items[1]['product_id'] === $product->get_id();
			}
		}

		if ( isset( $sorted_cart_items[2] ) ) {
			if ( 'third_highest_price' === $filter['comparation'] ) {
				return $sorted_cart_items[2]['product_id'] === $product->get_id();
			}

			if ( 'third_lowest_price' === $filter['comparation'] ) {
				return $sorted_cart_items[2]['product_id'] === $product->get_id();
			}
		}

		return false;
	}

	public static function filter_products_by_sub_filter( $ids, $sub_filter ) {
		if ( ! $sub_filter ) {
			return $ids;
		}

		if ( empty( $ids ) ) {
			return $ids;
		}

		if ( 'sub_filter_product_price_criterion' === $sub_filter['type'] ) {
			$products = array_map( 'wc_get_product', $ids );
			usort( $products, function( $a, $b ) {
				return \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $b ) - \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $a );
			} );

			if ( 'highest_price' === $sub_filter['comparation'] ) {
				return [ $products[0]->get_id() ];
			}

			if ( 'lowest_price' === $sub_filter['comparation'] ) {
				return [ $products[ count( $products ) - 1 ]->get_id() ];
			}

			if ( isset( $products[1] ) ) {
				if ( 'second_highest_price' === $sub_filter['comparation'] ) {
					return [ $products[1]->get_id() ];
				}

				if ( 'second_lowest_price' === $sub_filter['comparation'] ) {
					return [ $products[1]->get_id() ];
				}
			}

			if ( isset( $products[2] ) ) {
				if ( 'third_highest_price' === $sub_filter['comparation'] ) {
					return [ $products[2]->get_id() ];
				}

				if ( 'third_lowest_price' === $sub_filter['comparation'] ) {
					return [ $products[2]->get_id() ];
				}
			}
		}

		return $ids;
		
	}

	public static function filter_cats_by_sub_filter( $ids, $product, $sub_filter ) {
		if ( ! $sub_filter ) {
			return $ids;
		}

		if ( empty( $ids ) ) {
			return $ids;
		}

		$category_ids = $ids;

		$args = [
			'post_type' => 'product',
			'posts_per_page' => -1, // Get all products
			'tax_query' => [
				[
					'taxonomy' => 'product_cat',
					'field' => 'term_id',
					'terms' => $category_ids,
					'operator' => 'IN',
				],
			],
		];

		$query = new \WP_Query($args);

		if ($query->have_posts()) {
			$check_product = null;
			$p = $query->get_posts();
			$products = array_map( function( $item ) {
				return \wc_get_product( $item->ID );
			}, $p );

			usort( $products, function( $a, $b ) {
				return \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $b ) - \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $a );
			} );

			if ( 'highest_price' === $sub_filter['comparation'] ) {
				$check_product = $products[0];
			}

			if ( 'lowest_price' === $sub_filter['comparation'] ) {
				$check_product = $products[ count( $products ) - 1 ];
			}

			if ( isset( $products[1] ) ) {
				if ( 'second_highest_price' === $sub_filter['comparation'] ) {
					$check_product = $products[1];
				}

				if ( 'second_lowest_price' === $sub_filter['comparation'] ) {
					$check_product = $products[1];
				}
			}

			if ( isset( $products[2] ) ) {
				if ( 'third_highest_price' === $sub_filter['comparation'] ) {
					$check_product = $products[2];
				}

				if ( 'third_lowest_price' === $sub_filter['comparation'] ) {
					$check_product = $products[2];
				}
			}

			if ( $check_product && $check_product->get_id() === $product->get_id() ) {
				return $ids;
			}
		}
				
		return [];
	}

}
