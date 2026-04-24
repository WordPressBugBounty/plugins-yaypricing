<?php
/**
 * This class represents the model for the YAYDP Data
 *
 * @package YayPricing\Models
 */

namespace YAYDP\API\Models;

/**
 * Declare class
 */
class YAYDP_Data_Model {

	/**
	 * Retrieves products in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_products( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$offset   = ( $page - 1 ) * $limit;
		$args     = array(
			'limit'   => $limit + 1,
			'offset'  => $offset,
			's'       => $search,
			'order'   => 'ASC',
			'orderby' => 'title',
		);
		$products = array_map(
			function( $item ) {
				return array(
					'id'   => $item->get_id(),
					'name' => $item->get_name(),
					'slug' => $item->get_slug(),
				);
			},
			\wc_get_products( $args )
		);
		return $products;
	}

	/**
	 * Retrieves product variations in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_variations( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$offset = ( $page - 1 ) * $limit;
		$query = new \WC_Product_Query( array(
            'limit'     => -1,
            'type' => 'variable',
            's' => $search,
            'return'    => 'ids', // Returns an array of product IDs
        ) );

		$query_variables = $query->get_products();

		$result = [];

		foreach ( $query_variables as $variable_product_id ) {
			$variable_product = \wc_get_product( $variable_product_id );
			$variations = $variable_product->get_children();
			foreach ( $variations as $variation_id ) {
				$variation_product = \wc_get_product( $variation_id );
				$options = $variation_product->get_attributes();
				$name = $variation_product->get_name();
				if ( is_array( $options ) && ! empty( $options ) ) {
					$name .= ' - ' . implode( ' - ', array_values( $options ) );
				}
				$result[] = array(
					'id'   => $variation_product->get_id(),
					'name' => $name,
					'slug' => $variation_product->get_slug(),
				);
			}
		}

		$result = array_slice( $result, $offset, $limit + 1 );

		return $result;
	}

	/**
	 * Retrieves product categories in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_categories( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$offset = ( $page - 1 ) * $limit;
		$args   = array(
			'number'     => $limit + 1,
			'offset'     => $offset,
			'order'      => 'ASC',
			'orderby'    => 'name',
			'taxonomy'   => 'product_cat',
			'name__like' => $search,
		);

		$categories = array_map(
			function( $item ) {
				$parent_label = '';
				$cat          = $item;
				while ( ! empty( $cat->parent ) ) {
					$parent = get_term( $cat->parent );
					if ( is_null( $parent ) || is_wp_error( $parent ) ) {
						continue;
					}
					$parent_label .= $parent->name . ' ⇒ ';
					$cat           = $parent;
				}
				return array(
					'id'   => $item->term_id,
					'name' => $parent_label . $item->name,
					'slug' => $item->slug,
				);
			},
			\array_values( \get_categories( $args ) )
		);
		return $categories;
	}

	/**
	 * Retrieves product tags in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_tags( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$offset = ( $page - 1 ) * $limit;
		$args   = array(
			'number'     => $limit + 1,
			'offset'     => $offset,
			'order'      => 'ASC',
			'orderby'    => 'name',
			'taxonomy'   => 'product_tag',
			'name__like' => $search,
		);

		$tags = array_map(
			function( $item ) {
				return array(
					'id'   => $item->term_id,
					'name' => $item->name,
					'slug' => $item->slug,
				);
			},
			\array_values( \get_categories( $args ) )
		);
		return $tags;
	}

	/**
	 * Retrieves customer roles in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_customer_roles( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		global $wp_roles;

		$offset = ( $page - 1 ) * $limit;

		$list  = $wp_roles->get_names();
		$roles = \array_filter(
			array_keys( $list ? $list : array() ),
			function( $slug ) use ( $list, $search ) {
				if ( ! empty( $search ) ) {
					return false !== strpos( strtolower( $list[ $slug ] ), strtolower( $search ) );
				}
				return true;
			}
		);
		$roles = \array_map(
			function( $slug ) use ( $list ) {
				return array(
					'id'   => $slug,
					'name' => $list[ $slug ],
				);
			},
			$roles
		);

		return array_slice( $roles, $offset, $limit );
	}

	/**
	 * Retrieves customer list in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_customers( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$offset = ( $page - 1 ) * $limit;
		$args   = array(
			'number'         => $limit + 1,
			'offset'         => $offset,
			'order'          => 'ASC',
			'search'         => "*$search*",
			'search_columns' => array( 'user_login', 'user_email', 'user_nicename' ),
		);

		$query         = new \WP_User_Query( $args );
		$query_results = $query->get_results();
		$customers     = array();
		if ( ! empty( $query_results ) ) {
			$customers = \array_map(
				function( $item ) {
					$first_name = get_user_meta( $item->ID, 'first_name', true );
					$last_name  = get_user_meta( $item->ID, 'last_name', true );
					return array(
						'id'         => $item->ID,
						'email'      => $item->user_email,
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'label'      => $first_name . $last_name . '(#' . $item->user_email . ')',
					);
				},
				$query_results
			);
		}

		return $customers;
	}

	/**
	 * Retrieves all regions and its country in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_shipping_regions( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		return self::get_regions( $search, $page, $limit );
	}

	/**
	 * Retrieves all payment methods in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_payment_methods( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {

		$offset = ( $page - 1 ) * $limit;

		$payment_gateways = \WC()->payment_gateways->payment_gateways();
		$payment_methods  = array_map(
			function( $id ) use ( $payment_gateways ) {
				$method = $payment_gateways[ $id ];
				return array(
					'id'      => $id,
					'name'    => ! empty( $method->method_title ) ? $method->method_title : $method->title,
					'enabled' => 'yes' === $method->enabled,
				);
			},
			array_keys( $payment_gateways ? $payment_gateways : array() )
		);

		$payment_methods = array_filter(
			$payment_methods,
			function( $method ) use ( $search ) {
				if ( ! empty( $search ) ) {
					return false !== strpos( strtolower( $method['name'] ), strtolower( $search ) );
				}
				return true;
			}
		);

		return array_slice( $payment_methods, $offset, $limit );
	}

	/**
	 * Retrieves all coupons in database by search query
	 *
	 * @since 2.0
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_coupons( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {

		$offset = ( $page - 1 ) * $limit;

		$coupon_posts = get_posts(
			array(
				'posts_per_page' => $limit + 1,
				'offset'         => $offset,
				's'              => $search,
				'orderby'        => 'name',
				'order'          => 'asc',
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
			)
		);

		$coupons = array_map(
			function( $post ) {
				return array(
					'id'   => $post->ID,
					'name' => $post->post_title,
				);
			},
			$coupon_posts
		);

		return $coupons;
	}

	/**
	 * Retrieves product categories in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_attributes( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$offset = ( $page - 1 ) * $limit;

		$taxonomy_names = \wc_get_attribute_taxonomy_names();

		$taxonomy_with_label = array();

		foreach ( $taxonomy_names as $taxonomy_name ) {
			$taxonomy_with_label[ $taxonomy_name ] = \wc_attribute_label( $taxonomy_name );
		}

		$args = array(
			'number'     => $limit + 1,
			'offset'     => $offset,
			'order'      => 'ASC',
			'orderby'    => 'name',
			'taxonomy'   => $taxonomy_names,
			'name__like' => $search,
		);

		$categories = array_map(
			function( $item ) use ( $taxonomy_with_label ) {
				$parent_label = '';
				$cat          = $item;
				while ( ! empty( $cat->parent ) ) {
					$parent = get_term( $cat->parent );
					if ( is_null( $parent ) || is_wp_error( $parent ) ) {
						continue;
					}
					$parent_label .= $parent->name . ' ⇒ ';
					$cat           = $parent;
				}
				$taxonomy_label = isset( $taxonomy_with_label[ $item->taxonomy ] ) ? $taxonomy_with_label[ $item->taxonomy ] : '';
				return array(
					'id'   => $item->term_id,
					'name' => $taxonomy_label . ': ' . $parent_label . $item->name,
					'slug' => $item->slug,
				);
			},
			\array_values( \get_categories( $args ) )
		);
		return $categories;
	}

	/**
	 * Returns all product attribute taxonomies
	 *
	 * @since 3.4.1
	 */
	public static function get_attribute_taxonomies() {

		$attribute_taxonomies = \wc_get_attribute_taxonomies();
		return array_values(
			array_map(
				function( $tax ) {
					return array(
						'id'   => wc_attribute_taxonomy_name( $tax->attribute_name ),
						'name' => $tax->attribute_label,
						'slug' => wc_attribute_taxonomy_name( $tax->attribute_name ),
					);
				},
				$attribute_taxonomies
			)
		);

	}

	/**
	 * Retrieves product specific attributes in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_product_specific_attributes( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		global $wpdb;

		$offset = ( $page - 1 ) * $limit;
		$specific_attributes = array();

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return array();
		}

		try {
			$rows = $wpdb->get_col(
				"SELECT pm.meta_value
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_product_attributes'
				 AND p.post_type IN ('product', 'product_variation')
				 AND p.post_status != 'trash'"
			);
		} catch ( \Exception $e ) {
			return array();
		}

		if ( empty( $rows ) ) {
			return array();
		}

		$search_lower = '' !== $search ? strtolower( $search ) : '';

		foreach ( $rows as $raw ) {
			$attributes = maybe_unserialize( $raw );
			if ( ! is_array( $attributes ) ) {
				continue;
			}

			foreach ( $attributes as $attribute ) {
				if ( ! is_array( $attribute ) ) {
					continue;
				}
				// Skip taxonomy-based attributes (is_taxonomy flag set).
				if ( ! empty( $attribute['is_taxonomy'] ) ) {
					continue;
				}

				$attribute_name = isset( $attribute['name'] ) ? (string) $attribute['name'] : '';
				if ( '' === $attribute_name ) {
					continue;
				}

				$value_raw = isset( $attribute['value'] ) ? (string) $attribute['value'] : '';
				if ( '' === $value_raw ) {
					continue;
				}

				$options = array_map( 'trim', explode( '|', $value_raw ) );

				foreach ( $options as $option ) {
					if ( '' === $option ) {
						continue;
					}

					$slug = sanitize_title( $attribute_name . '_' . $option );
					if ( '' === $slug || isset( $specific_attributes[ $slug ] ) ) {
						continue;
					}

					if ( '' !== $search_lower ) {
						if ( false === strpos( strtolower( $option ), $search_lower )
							&& false === strpos( strtolower( $attribute_name ), $search_lower ) ) {
							continue;
						}
					}

					$specific_attributes[ $slug ] = array(
						'id'             => $slug,
						'name'           => $attribute_name . ': ' . $option,
						'slug'           => $slug,
						'attribute_name' => $attribute_name,
						'option'         => $option,
					);
				}
			}
		}

		$specific_attributes = array_values( $specific_attributes );

		return array_slice( $specific_attributes, $offset, $limit );
	}


	/**
	 * Retrieves all regions and its country in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 *
	 * @since 3.4.2
	 */
	public static function get_regions( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$continents        = \WC()->countries->get_shipping_continents();
		$allowed_countries = \WC()->countries->get_shipping_countries();
		$regions           = \array_map(
			function( $continent_slug ) use ( $continents, $allowed_countries ) {
				$continent = $continents[ $continent_slug ];
				$countries = array_intersect( array_keys( $allowed_countries ? $allowed_countries : array() ), $continent['countries'] );
				return array(
					'continent_slug' => $continent_slug,
					'continent_name' => $continent['name'],
					'countries'      => \array_map(
						function( $country_code ) use ( $allowed_countries ) {
							$country_states = \WC()->countries->get_states( $country_code );
							return array(
								'country_code' => $country_code,
								'country_name' => $allowed_countries[ $country_code ],
								'states'       => array_map(
									function( $state_code ) use ( $country_states ) {
										return array(
											'state_code' => $state_code,
											'state_name' => $country_states[ $state_code ],
										);
									},
									array_keys( $country_states ? $country_states : array() )
								),
							);
						},
						array_values( $countries ? $countries : array() )
					),
				);
			},
			array_keys( $continents ? $continents : array() )
		);
		return $regions;
	}

	/**
	 * Retrieves all regions and its country in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 *
	 * @since 3.4.2
	 */
	public static function get_billing_regions( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		return self::get_regions( $search, $page, $limit );
	}

	/**
	 * Retrieves all shipping methods in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 * @since 3.5.2
	 */
	public static function get_shipping_methods( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$shipping_methods = \WC()->shipping->get_shipping_methods();
		$available_methods = array_values(array_map(
			function ( $method ) {
				return array(
					'id'   => $method->id,
					'name' => $method->method_title,
				);
			},
			$shipping_methods
		));
		$shipping_zones = \WC_Shipping_Zones::get_zones();
		foreach ( $shipping_zones as $zone ) {
			$zone_methods = $zone['shipping_methods'];
			foreach ( $zone_methods as $method ) {
				if ( ! in_array( $method->id, array_column( $available_methods, 'id' ) ) ) {
					$available_methods[] = array(
						'id'   => $method->id,
						'name' => $method->method_title,
					);
				}
			}
		}
		return $available_methods;
	}

	/**
	 * Retrieves all shipping classes in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 * @since 3.5.2
	 */
	public static function get_shipping_classes( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$shipping_classes = \WC()->shipping->get_shipping_classes();
		return array_values(array_map(
			function ( $class ) {
				return array(
					'id'   => $class->term_id,
					'name' => $class->name,
				);
			},
			$shipping_classes
		));
	}
}
