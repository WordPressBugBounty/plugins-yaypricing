<?php
/**
 * YayPricing matching product helper
 *
 * @package YayPricing\Classes
 * @version 1.0.0
 */

namespace YAYDP\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Matching_Products_Helper class
 */
class YAYDP_Matching_Products_Helper {

	/**
	 * Do some stuffs need for class
	 */
	public static function init_hooks() {
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( __CLASS__, 'custom_meta_price_query' ), 10, 2 );
	}

	/**
	 * Callback for woocommerce_product_data_store_cpt_get_products_query
	 *
	 * @param array $query Price query.
	 * @param array $query_vars Variables of the query.
	 */
	public static function custom_meta_price_query( $query, $query_vars ) {
		if ( ! empty( $query_vars['yaydp_product_price_filter'] ) ) {
			$price       = $query_vars['yaydp_product_price_filter']['price'];
			$comparation = $query_vars['yaydp_product_price_filter']['comparation'];
			switch ( $comparation ) {
				case 'greater_than':
					$compare = '>';
					break;
				case 'less_than':
					$compare = '<';
					break;
				case 'gte':
					$compare = '>=';
					break;
				case 'lte':
					$compare = '<=';
					break;
				default:
					$compare = '=';
					break;
			}
			$query['meta_query'] = array(
				array(
					'key'     => '_price',
					'value'   => $price,
					'compare' => $compare,
					'type'    => 'NUMERIC',
				),
			);
		}
		return $query;
	}

	/**
	 * Searching products that match the filter
	 *
	 * @param array $filter The filter for searching products.
	 * @return array
	 */
	public static function get_matching_products( $filter, $order = 'ASC' ) {
		if ( ! in_array( $filter['type'], array( 'product_price', 'product_in_stock', 'all_product' ), true ) ) {
			$filter_values = array_map(
				function( $v ) {
					return $v['value'];
				},
				$filter['value']
			);
		}
		switch ( $filter['type'] ) {
			case 'product':
				return self::get_product_by_ids( $filter_values, $filter['comparation'], $order );
			case 'product_variation':
				return self::get_product_by_variation_ids( $filter_values, $filter['comparation'], $order );
			case 'product_category':
				return self::get_product_by_categories( $filter_values, $filter['comparation'], $order );
			case 'product_tag':
				return self::get_product_by_tags( $filter_values, $filter['comparation'], $order );
			case 'product_price':
				return self::get_product_by_price( $filter['value'], $filter['comparation'], $order );
			case 'product_attribute':
				return self::get_product_by_attributes( $filter['value'], $filter['comparation'], $order );
			case 'product_in_stock':
				return self::get_product_by_stock_quantity( $filter['value'], $filter['comparation'], $order );
			case 'all_product':
				return self::get_product_by_ids( array(), 'not_in_list', $order );
			default:
				return self::get_products_by_custom_filter( $filter['type'], $filter_values, $filter['comparation'] );
		}
	}

	/**
	 * Search products by list id
	 *
	 * @param array  $ids List id.
	 * @param string $comparation Comparation.
	 * @return array
	 */
	public static function get_product_by_ids( $ids, $comparation = 'in_list', $order = 'ASC' ) {
		if ( empty( $ids ) && 'in_list' === $comparation ) {
			return array();
		}
		$include_args = 'in_list' === $comparation ? array(
			'include' => $ids,
		) : array(
			'exclude' => $ids,
		);
		$args         = array(
			'post_status' => 'publish', // Only show published products
			'limit' => -1,
		);
		$args         = array_merge( $args, self::get_order( $order ) );

		$products = \wc_get_products( array_merge( $args, $include_args ) );
		return $products;

	}

	/**
	 * Search products by list variation id
	 *
	 * @param array  $ids List id.
	 * @param string $comparation Comparation.
	 * @return array
	 */
	public static function get_product_by_variation_ids( $ids, $comparation = 'in_list', $order = 'ASC' ) {
		if ( empty( $ids ) ) {
			return array();
		}
		if ( 'in_list' === $comparation ) {
			$args     = array(
				'post_status' => 'publish', // Only show published products
				'limit'   => -1,
				'type'    => 'variation',
				'include' => $ids,
			);
			$args     = array_merge( $args, self::get_order( $order ) );
			$products = \wc_get_products( $args );
		} else {
			$args     = array(
				'post_status' => 'publish', // Only show published products
				'limit' => -1,
			);
			$args     = array_merge( $args, self::get_order( $order ) );
			$products = array_reduce(
				\wc_get_products( $args ),
				function( $carry, $product ) use ( $ids ) {
					if ( ! $product->has_child() ) {
						$carry[] = $product;
						return $carry;
					}
					$product_variation_ids = $product->get_children();
					$exclude_ids           = array_intersect( $ids, $product_variation_ids );
					if ( empty( $exclude_ids ) ) {
						$carry[] = $product;
						return $carry;
					}
					$remaining_ids        = array_diff( $product_variation_ids, $exclude_ids );
					$remaining_variations = array_map(
						function( $id ) {
							return \wc_get_product( $id );
						},
						$remaining_ids
					);
					$carry                = array_merge( $carry, $remaining_variations );
					return $carry;
				},
				array()
			);

		}
		return $products;
	}

	/**
	 * Search products by list category id
	 *
	 * @param array  $ids List id.
	 * @param string $comparation Comparation.
	 * @return array
	 */
	public static function get_product_by_categories( $ids, $comparation = 'in_list', $order = 'ASC' ) {
		if ( empty( $ids ) ) {
			return array();
		}
		$args     = array(
			'post_status' => 'publish', // Only show published products
			'limit'     => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'terms'    => $ids,
					'operator' => 'in_list' === $comparation ? 'IN' : 'NOT IN',
				),
			),
		);
		$args     = array_merge( $args, self::get_order( $order ) );
		$products = \wc_get_products( $args );
		return $products;
	}

	/**
	 * Search products by list tag id
	 *
	 * @param array  $ids List id.
	 * @param string $comparation Comparation.
	 * @return array
	 */
	public static function get_product_by_tags( $ids, $comparation = 'in_list', $order = 'ASC' ) {
		if ( empty( $ids ) ) {
			return array();
		}
		$args     = array(
			'post_status' => 'publish', // Only show published products
			'limit'     => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'product_tag',
					'terms'    => $ids,
					'operator' => 'in_list' === $comparation ? 'IN' : 'NOT IN',
				),
			),
		);
		$args     = array_merge( $args, self::get_order( $order ) );
		$products = \wc_get_products( $args );
		return $products;
	}

	/**
	 * Search products by price filter
	 *
	 * @param float  $value List id.
	 * @param string $comparation Comparation.
	 * @return array
	 */
	public static function get_product_by_price( $value, $comparation = 'greater_than', $order = 'ASC' ) {
		if ( empty( $value ) ) {
			return array();
		}
		$args     = array(
			'post_status' => 'publish', // Only show published products
			'limit'                      => -1,
			'yaydp_product_price_filter' => array(
				'price'       => $value,
				'comparation' => $comparation,
			),
		);
		$args     = array_merge( $args, self::get_order( $order ) );
		$products = \wc_get_products( $args );
		$products = array_reduce(
			$products,
			function( array $carry, $product ) use ( $value, $comparation ) {
				if ( ! $product->has_child() ) {
					$carry[] = $product;
					return $carry;
				}
				foreach ( $product->get_children() as $children_id ) {
					$product_variation = \wc_get_product( $children_id );
					if ( false === $product_variation ) {
						continue;
					}
					$product_variation_price = floatval( $product_variation->get_price() );
					$check                   = \yaydp_compare_numeric( $product_variation_price, $value, $comparation );
					if ( $check ) {
						$carry[] = $product_variation;
					}
				}
				return $carry;
			},
			array()
		);
		return $products;
	}

	public static function get_product_by_attributes( $filters, $comparation = 'in_list', $order = 'ASC' ) {
		if ( empty( $filters ) ) {
			return array();
		}
		$queries           = array();
		$list_attribute_id = \YAYDP\Helper\YAYDP_Helper::map_filter_value( array( 'value' => $filters ) );
		foreach ( $list_attribute_id as $attribute_id ) {
			$term = get_term( $attribute_id );
			if ( is_null( $term ) || is_wp_error( $term ) ) {
				continue;
			}
			$queries[] = array(
				'taxonomy' => $term->taxonomy,
				'field'    => 'slug',
				'terms'    => array( $term->slug ),
				'operator' => 'in_list' === $comparation ? 'IN' : 'NOT IN',
			);
		}
		$args     = array(
			'post_status' => 'publish', // Only show published products
			'limit'     => -1,
			'tax_query' => $queries,
		);
		$args     = array_merge( $args, self::get_order( $order ) );
		$products = \wc_get_products( $args );
		// if ( 'in_list' !== $comparation ) {
		// 	$products = self::get_product_by_ids( array_map( function( $item ) { return $item->get_id();}, $products ), 'not_in_list', $order );
		// }
		return $products;
	}


	/**
	 * Search products by price filter
	 *
	 * @param float  $value List id.
	 * @param string $comparation Comparation.
	 * @return array
	 */
	public static function get_product_by_stock_quantity( $value, $comparation = 'greater_than', $order = 'ASC' ) {
		switch ( $comparation ) {
			case 'greater_than':
				$compare = '>';
				break;
			case 'less_than':
				$compare = '<';
				break;
			case 'gte':
				$compare = '>=';
				break;
			case 'lte':
				$compare = '<=';
				break;
			default:
				$compare = '=';
				break;
		}

		$main_query = "";
		if ( ! is_numeric( $value ) ) {
			$main_query = "( wp_postmeta.meta_key = '_stock_status' AND wp_postmeta.meta_value != 'instock' )";
		}

		if ( in_array( $compare, ['>', '>='] ) ) {
			$main_query = "
				( 
					( wp_postmeta.meta_key = '_stock_status' AND wp_postmeta.meta_value = 'instock' ) 
					AND 
					( mt1.meta_key = '_stock' AND CAST( mt1.meta_value AS SIGNED) $compare '$value' )
				) 
				OR 
				( 
					( mt2.meta_key = '_stock_status' AND mt2.meta_value = 'instock' )
					AND 
					( mt3.meta_key = '_stock' AND mt3.meta_value IS NULL )
				)
			";
		}

		if ( in_array( $compare, ['<', '<='] ) ) {
			$main_query = "
				( 
					( wp_postmeta.meta_key = '_stock_status' AND wp_postmeta.meta_value = 'instock' ) 
					AND 
					( mt1.meta_key = '_stock' AND CAST( mt1.meta_value AS SIGNED) $compare '$value' )
				) 
			";
		}

		$sql_query = 
		"SELECT wp_posts.*
		FROM wp_posts  
		LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id) 
		INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id )  
		INNER JOIN wp_postmeta AS mt1 ON ( wp_posts.ID = mt1.post_id )  
		INNER JOIN wp_postmeta AS mt2 ON ( wp_posts.ID = mt2.post_id )  
		INNER JOIN wp_postmeta AS mt3 ON ( wp_posts.ID = mt3.post_id )
		WHERE 1=1 
		AND ( $main_query ) AND 
		wp_posts.post_type = 'product' 
		AND wp_posts.post_status = 'publish'
		GROUP BY wp_posts.ID
		ORDER BY wp_posts.post_title $order";

		global $wpdb;

		$results = $wpdb->get_results( $sql_query ); //PHPCS:ignore:WordPress.DB.PreparedSQL.NotPrepared

		$products = [];

		foreach ( $results as $post ) {

			$product = \wc_get_product( $post->ID );

			if ( ! empty( $product ) ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Return the products with its custom data
	 *
	 * @param array $products List products.
	 * @return array
	 */
	public static function get_formatted_response_products( $products ) {
		return array_map(
			function( $product ) {
				return array(
					'id'                  => $product->get_id(),
					'name'                => $product->get_name(),
					'slug'                => $product->get_slug(),
					'availability'        => $product->get_availability(),
					'price_html'          => self::get_product_price_html( $product ),
					'permalink'           => $product->get_permalink(),
					'image'               => $product->get_image( array( 60, 60 ) ),
					'is_variable_product' => is_a( $product, 'WC_Product_Variable' ),
					'is_variation'        => is_a( $product, 'WC_Product_Variation' ),
				);
			},
			$products
		);
	}

	/**
	 * Sort list products by Variable product first
	 *
	 * @param array $products List products.
	 * @return array
	 */
	public static function sort_product_list( &$products ) {
		\usort(
			$products,
			function( $a, $b ) {
				if ( is_a( $b, 'WC_Product_Variable' ) ) {
					return 1;
				}
				return -1;
			}
		);
		return $products;
	}

	/**
	 * Simplifies the product list by removing any duplicate products and sorting them alphabetically
	 *
	 * @param array  $products List products.
	 * @param string $match_type Match type.
	 * @return array
	 */
	public static function simplify_product_list( $products, $match_type = 'any' ) {
		$products = self::sort_product_list( $products );
		$ids      = array_map(
			function( $product ) {
				return $product->get_id();
			},
			$products
		);
		return array_reduce(
			$products,
			function( array $carry, $product ) use ( $ids, $match_type ) {

				// Check Variable Product.
				if ( $product->has_child() ) {
					if ( 'any' === $match_type ) {
						$carry[] = $product;
						return $carry;
					}
					$children_ids = $product->get_children();
					if ( count( array_intersect( $children_ids, $ids ) ) === count( $children_ids ) || 0 === count( array_intersect( $children_ids, $ids ) ) ) {
						$carry[] = $product;
					}
					return $carry;
				}
				$carry_ids = array_map(
					function( $c ) {
						return $c->get_id();
					},
					$carry
				);

				// Check Variation Product.
				if ( ! empty( $product->get_parent_id() ) && in_array( $product->get_parent_id(), $carry_ids, true ) ) {
					return $carry;
				}

				// Check Normal Product.
				if ( ! in_array( $product->get_id(), $carry_ids, true ) ) {
					$carry[] = $product;
				}
				return $carry;
			},
			array()
		);
	}

	/**
	 * Returns the HTML for displaying the price of a product
	 *
	 * @param WC_Product $product Given product.
	 */
	public static function get_product_price_html( $product ) {
		$product_type = $product->get_type();
		if ( 'variable' === $product_type ) {
			return self::get_variable_product_price_html( $product );
		}
		if ( 'grouped' === $product_type ) {
			return self::get_grouped_product_price_html( $product );
		}

		return self::get_single_product_price_html( $product );

	}

	/**
	 * @deprecated 2.4.1
	 */
	public static function get_wc_price_arg() {
		return array(
			'ex_tax_label'       => false,
			'currency'           => get_option( 'woocommerce_currency' ),
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimals'           => wc_get_price_decimals(),
			'price_format'       => get_woocommerce_price_format(),
		);
	}

	public static function get_single_product_price_html( $product ) {
		if ( '' === $product->get_price( 'origin' ) ) {
			$price = '';
		} elseif ( $product->is_on_sale() ) {
			$price = wc_format_sale_price( wc_price( $product->get_regular_price( 'origin' ) ), wc_price( $product->get_price( 'origin' ) ) ) . $product->get_price_suffix();
		} else {
			$price = wc_price( $product->get_price( 'origin' ) ) . $product->get_price_suffix();
		}

		return $price;
	}

	public static function get_variable_product_price_html( $product ) {
		$prices = $product->get_variation_prices( true );

		if ( empty( $prices['price'] ) ) {
			$price = '';
		} else {
			$min_price           = \YAYDP\Helper\YAYDP_Pricing_Helper::reverse_price( current( $prices['price'] ) );
			$max_price           = \YAYDP\Helper\YAYDP_Pricing_Helper::reverse_price( end( $prices['price'] ) );
			$min_reg_price       = \YAYDP\Helper\YAYDP_Pricing_Helper::reverse_price( current( $prices['regular_price'] ) );
			$max_reg_price       = \YAYDP\Helper\YAYDP_Pricing_Helper::reverse_price( end( $prices['regular_price'] ) );
			$formatted_min_price = \wc_price( $min_price );
			if ( $min_price !== $max_price ) {
				$formatted_max_price = \wc_price( $max_price );
				$price               = $formatted_min_price . ' - ' . $formatted_max_price;
			} elseif ( $product->is_on_sale() && $min_reg_price === $max_reg_price ) {
				$formatted_max_reg_price = \wc_price( $max_reg_price );
				$price                   = \wc_format_sale_price( $formatted_max_reg_price, $formatted_min_price );
			} else {
				$price = $formatted_min_price;
			}
		}

		return $price;
	}

	public static function get_grouped_product_price_html( $product ) {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$child_prices     = array();
		$children         = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );

		foreach ( $children as $child ) {
			if ( '' !== $child->get_price() ) {
				$child_prices[] = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $child ) : wc_get_price_excluding_tax( $child );
			}
		}

		if ( ! empty( $child_prices ) ) {
			$min_price = min( $child_prices );
			$max_price = max( $child_prices );
		} else {
			$min_price = '';
			$max_price = '';
		}

		$min_price = \YAYDP\Helper\YAYDP_Pricing_Helper::reverse_price( $min_price );
		$max_price = \YAYDP\Helper\YAYDP_Pricing_Helper::reverse_price( $max_price );

		if ( '' !== $min_price ) {
			if ( $min_price !== $max_price ) {
				$price = wc_price( $min_price ) . ' - ' . wc_price( $max_price );
			} else {
				$price = wc_price( $min_price );
			}

			$is_free = 0 === $min_price && 0 === $max_price;

			if ( $is_free ) {
				$price = apply_filters( 'woocommerce_grouped_free_price_html', __( 'Free!', 'woocommerce' ), $product );
			} else {
				$price = apply_filters( 'woocommerce_grouped_price_html', $price . $product->get_price_suffix(), $product, $child_prices );
			}
		} else {
			$price = '';
		}

		return $price;
	}

	public static function get_products_by_custom_filter( $type, $value, $comparation ) {
		return apply_filters( "yaydp_get_matching_products_by_{$type}", array(), $type, $value, $comparation );
	}

	public static function sort_products_by_name( &$products, $order = 'asc' ) {
		\usort(
			$products,
			function( $a, $b ) use ( $order ) {
				$check = -1;
				if ( $a->get_name() > $b->get_name() ) {
					$check = 1;
				}

				return 'asc' === $order ? $check : -( $check );
			}
		);
		return $products;
	}

	public static function get_raw_matching_products_by_rule( $filters, $match_type, $is_buy_x_get_y ) {
		$capable_products = array();
		foreach ( $filters as $index  => $filter ) {
			$pros = self::get_matching_products( $filter );
			if ( 'any' === $match_type || $is_buy_x_get_y ) {
				$capable_products = \array_merge( $capable_products, $pros );
			} else {
				if ( 0 === $index ) {
					$capable_products = $pros;
				} else {
					self::sort_product_list( $capable_products );
					self::sort_product_list( $pros );
					$new_capable_products = array();
					foreach ( $pros as $product ) {
						$cp_ids = array_map(
							function( $cp ) {
								return $cp->get_id();
							},
							$capable_products
						);
						if ( $product->has_child() ) {
							$children_ids = $product->get_children();
							$p_intersect  = array_intersect( $children_ids, $cp_ids );
							if ( count( $p_intersect ) === count( $children_ids ) ) {
								$new_capable_products[] = $product;
								continue;
							}
							foreach ( $p_intersect as $p_i_id ) {
								$new_capable_products[] = \wc_get_product( $p_i_id );
							}
						}
						if ( ! empty( $product->get_parent_id() ) ) {
							if ( in_array( $product->get_parent_id(), $cp_ids, true ) ) {
								$new_capable_products[] = $product;
							}
						}
						if ( in_array( $product->get_id(), $cp_ids, true ) ) {
							$new_capable_products[] = $product;
						}
					}

					foreach ( $capable_products as $product ) {
						$ncp_ids = array_map(
							function( $ncp ) {
								return $ncp->get_id();
							},
							$capable_products
						);
						if ( $product->has_child() ) {
							$children_ids = $product->get_children();
							if ( count( array_intersect( $children_ids, $ncp_ids ) ) === count( $children_ids ) ) {
								$new_capable_products[] = $product;
							}
							continue;
						}
					}
					$capable_products = $new_capable_products;
				}
			}
		}
		return self::simplify_product_list( $capable_products, $match_type );
	}

	private static function get_order( $order ) {
		return 'none' !== $order ? array(
			'order'   => $order,
			'orderby' => 'title',
		) : array(
			'orderby' => 'include',
		);
	}
}
