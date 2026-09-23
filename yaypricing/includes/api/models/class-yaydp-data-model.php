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
			function ( $item ) {
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
		global $wpdb;

		$offset = ( $page - 1 ) * $limit;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return array();
		}

		/**
		 * Variation IDs are paginated in SQL so only the requested page is turned into
		 * product objects. Loading every variable product and instantiating each of its
		 * variations exhausts the PHP memory limit on large catalogues.
		 *
		 * A variation post_title is the parent product name followed by its formatted
		 * attributes, so matching on it covers searching by product name.
		 */
		$where  = "p.post_type = 'product_variation' AND p.post_status != 'trash'";
		$params = array();

		if ( '' !== $search ) {
			$where   .= ' AND p.post_title LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// One extra row signals to the frontend that a further page exists.
		$params[] = $limit + 1;
		$params[] = $offset;

		$variation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID
				 FROM {$wpdb->posts} p
				 WHERE {$where}
				 ORDER BY p.post_parent ASC, p.menu_order ASC, p.ID ASC
				 LIMIT %d OFFSET %d",
				$params
			)
		);

		if ( empty( $variation_ids ) ) {
			return array();
		}

		$result = array();

		foreach ( $variation_ids as $variation_id ) {
			$variation_product = \wc_get_product( $variation_id );
			if ( ! $variation_product ) {
				continue;
			}
			$options = $variation_product->get_attributes();
			$name    = $variation_product->get_name();
			if ( is_array( $options ) && ! empty( $options ) ) {
				$name .= ' - ' . implode( ' - ', array_values( $options ) );
			}
			$result[] = array(
				'id'   => $variation_product->get_id(),
				'name' => $name,
				'slug' => $variation_product->get_slug(),
			);
		}

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
			function ( $item ) {
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
			function ( $item ) use ( $taxonomy_with_label ) {
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
	 * Retrieves the global attribute taxonomies (pa_*), searched by label or name.
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_attribute_taxonomies( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$rows = array_map(
			function ( $tax ) {
				return array(
					'id'   => wc_attribute_taxonomy_name( $tax->attribute_name ),
					'name' => $tax->attribute_label,
					'slug' => wc_attribute_taxonomy_name( $tax->attribute_name ),
				);
			},
			array_values( \wc_get_attribute_taxonomies() )
		);
		return YAYDP_Collections::paginate( $rows, $search, $page, $limit, array( YAYDP_Collections::class, 'name_or_id_contains' ) );
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

		$offset              = ( $page - 1 ) * $limit;
		$specific_attributes = array();

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return array();
		}

		$search_lower = '' !== $search ? strtolower( $search ) : '';

		/**
		 * Number of unique entries needed before reading can stop, plus the one extra row
		 * that tells the frontend a further page exists. Entries are kept in insertion
		 * order, so anything past this point would be discarded by array_slice.
		 */
		$needed = $offset + $limit + 1;

		/**
		 * Only rows that can contribute are read. A blob is a serialized array of
		 * attributes, each carrying `is_taxonomy` as 0/1 (WooCommerce), so a product whose
		 * attributes are all global never matches and is skipped in SQL. Without this the
		 * loop below unserializes every product on a catalogue with no custom attributes
		 * and still returns nothing. The two extra clauses keep the legacy shapes the PHP
		 * filter accepts (boolean flag, flag absent).
		 */
		$params = array();
		$where  = "pm.meta_key = '_product_attributes'
			 AND p.post_type = 'product'
			 AND p.post_status != 'trash'
			 AND ( pm.meta_value LIKE '%\"is_taxonomy\";i:0;%'
				OR pm.meta_value LIKE '%\"is_taxonomy\";b:0;%'
				OR pm.meta_value NOT LIKE '%\"is_taxonomy\"%' )";

		// Attribute names and options are stored verbatim inside the blob, so a
		// case-insensitive LIKE narrows the scan; the exact match is still done in PHP.
		if ( '' !== $search ) {
			$where   .= ' AND pm.meta_value LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		/**
		 * Meta rows are read in chunks instead of all at once: a store-wide result set of
		 * serialized attribute blobs is large enough to exhaust the PHP memory limit.
		 */
		$chunk_size   = 500;
		$chunk_offset = 0;

		while ( count( $specific_attributes ) < $needed ) {
			// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders -- $where holds literals and %s placeholders only; values travel through prepare().
			$rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT pm.meta_value
					 FROM {$wpdb->postmeta} pm
					 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
					 WHERE {$where}
					 ORDER BY pm.meta_id ASC
					 LIMIT %d OFFSET %d",
					array_merge( $params, array( $chunk_size, $chunk_offset ) )
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders

			if ( empty( $rows ) ) {
				break;
			}

			$rows_read     = count( $rows );
			$chunk_offset += $chunk_size;

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

			// Release the chunk before reading the next one.
			unset( $rows );
			$wpdb->flush();

			// Last chunk reached.
			if ( $rows_read < $chunk_size ) {
				break;
			}
		}

		// One extra row signals to the frontend that a further page exists.
		return array_slice( array_values( $specific_attributes ), $offset, $limit + 1 );
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
			'lang'       => '',
		);

		$tags = array_map(
			function ( $item ) {
				return array(
					'id'   => $item->term_id,
					'name' => $item->name,
					'slug' => $item->slug,
				);
			},
			\array_values( \get_terms( $args ) )
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

		$list  = $wp_roles->get_names();
		$roles = \array_map(
			function ( $slug ) use ( $list ) {
				return array(
					'id'   => $slug,
					'name' => $list[ $slug ],
				);
			},
			array_keys( $list ? $list : array() )
		);
		return YAYDP_Collections::paginate( $roles, $search, $page, $limit, array( __CLASS__, 'name_contains' ) );
	}

	/**
	 * Case-insensitive "contains" on the row name (the roles / payment matcher).
	 *
	 * @param array  $row    Row with name.
	 * @param string $search Search text.
	 * @return bool
	 */
	public static function name_contains( array $row, $search ) {
		return false !== strpos( strtolower( (string) $row['name'] ), strtolower( $search ) );
	}

	/**
	 * Retrieves customer list in database by search query
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_customers( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		global $wpdb;

		/**
		 * One query instead of WP_User_Query so the first / last name the chip
		 * displays are searchable too. Matching stays a "contains" on login,
		 * email and nicename; only users of the current site (they carry the
		 * site's capabilities meta, which is what WP_User_Query scoped by);
		 * ordering stays user_login ASC; the window is limit + 1 rows so the
		 * picker knows a further page exists.
		 */
		$like   = '%' . $wpdb->esc_like( (string) $search ) . '%';
		$offset = ( max( 1, (int) $page ) - 1 ) * max( 1, (int) $limit );
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.ID AS id, u.user_email AS email,
				        COALESCE( MAX( fn.meta_value ), '' ) AS first_name,
				        COALESCE( MAX( ln.meta_value ), '' ) AS last_name
				 FROM {$wpdb->users} u
				 INNER JOIN {$wpdb->usermeta} cap ON cap.user_id = u.ID AND cap.meta_key = %s
				 LEFT JOIN {$wpdb->usermeta} fn ON fn.user_id = u.ID AND fn.meta_key = 'first_name'
				 LEFT JOIN {$wpdb->usermeta} ln ON ln.user_id = u.ID AND ln.meta_key = 'last_name'
				 WHERE u.user_login LIKE %s OR u.user_email LIKE %s OR u.user_nicename LIKE %s
				    OR fn.meta_value LIKE %s OR ln.meta_value LIKE %s
				 GROUP BY u.ID, u.user_email, u.user_login
				 ORDER BY u.user_login ASC
				 LIMIT %d OFFSET %d",
				$wpdb->get_blog_prefix() . 'capabilities',
				$like,
				$like,
				$like,
				$like,
				$like,
				max( 1, (int) $limit ) + 1,
				$offset
			),
			ARRAY_A
		);

		return array_map(
			function ( $row ) {
				$row['id'] = (int) $row['id'];
				return $row;
			},
			$rows ? $rows : array()
		);
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
		$payment_gateways = \WC()->payment_gateways->payment_gateways();
		$payment_methods  = array_map(
			function ( $id ) use ( $payment_gateways ) {
				$method = $payment_gateways[ $id ];
				return array(
					'id'      => $id,
					'name'    => ! empty( $method->method_title ) ? $method->method_title : $method->title,
					'enabled' => 'yes' === $method->enabled,
				);
			},
			array_keys( $payment_gateways ? $payment_gateways : array() )
		);

		return YAYDP_Collections::paginate( $payment_methods, $search, $page, $limit, array( __CLASS__, 'name_contains' ) );
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
			function ( $post ) {
				return array(
					'id'   => $post->ID,
					'name' => $post->post_title,
				);
			},
			$coupon_posts
		);

		return $coupons;
	}

	public static function get_tax_classes() {
		$tax_classes = array_map(
			function ( $tax ) {
				return array(
					'name' => $tax->name,
					'slug' => $tax->slug,
				);
			},
			\WC_Tax::get_tax_rate_classes()
		);
		array_unshift(
			$tax_classes,
			array(
				'name' => 'Standard',
				'slug' => 'standard',
			)
		);

		return $tax_classes;
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
			function ( $continent_slug ) use ( $continents, $allowed_countries ) {
				$continent = $continents[ $continent_slug ];
				$countries = array_intersect( array_keys( $allowed_countries ? $allowed_countries : array() ), $continent['countries'] );
				return array(
					'continent_slug' => $continent_slug,
					'continent_name' => $continent['name'],
					'countries'      => \array_map(
						function ( $country_code ) use ( $allowed_countries ) {
							$country_states = \WC()->countries->get_states( $country_code );
							return array(
								'country_code' => $country_code,
								'country_name' => $allowed_countries[ $country_code ],
								'states'       => array_map(
									function ( $state_code ) use ( $country_states ) {
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
	 * Registered shipping methods plus any zone-only method, searched by name or id.
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_shipping_methods( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$available_methods = array_values(
			array_map(
				function ( $method ) {
					return array(
						'id'   => $method->id,
						'name' => $method->method_title,
					);
				},
				\WC()->shipping->get_shipping_methods()
			)
		);
		foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( ! in_array( $method->id, array_column( $available_methods, 'id' ), true ) ) {
					$available_methods[] = array(
						'id'   => $method->id,
						'name' => $method->method_title,
					);
				}
			}
		}
		return YAYDP_Collections::paginate( $available_methods, $search, $page, $limit, array( YAYDP_Collections::class, 'name_or_id_contains' ) );
	}

	/**
	 * Shipping classes, searched by name or id.
	 *
	 * @param string $search Search name.
	 * @param number $page Current page.
	 * @param number $limit Limit to get.
	 */
	public static function get_shipping_classes( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$rows = array_values(
			array_map(
				function ( $class ) {
					return array(
						'id'   => $class->term_id,
						'name' => $class->name,
					);
				},
				\WC()->shipping->get_shipping_classes()
			)
		);
		return YAYDP_Collections::paginate( $rows, $search, $page, $limit, array( YAYDP_Collections::class, 'name_or_id_contains' ) );
	}

	public static function get_yayextra_options( $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$option_set_id_list = \YayExtra\Init\Settings::get_instance()->get_option_set_id_list();
		$option_set_list = \YayExtra\Init\CustomPostType::get_option_set_array( $option_set_id_list );
		$option_set_list = array_filter( $option_set_list, function( $option_set ) {
			return  $option_set['status'] === "1";
		});
		$yayextra_options = array();
		
		foreach ( $option_set_list as $option_set ) {
			foreach ( $option_set['options'] as $option ) {
				$yayextra_options[] = array(
					'id'   => $option['id'],
					'name' => $option['name']
				);
			}
		}
		
		if ( ! empty( $search ) ) {
			$yayextra_options = array_filter(
				$yayextra_options,
				function ( $option ) use ( $search ) {
					return stripos( $option['name'], $search ) !== false;
				}
			);
		}
		
		$offset = ( $page - 1 ) * $limit;
		$yayextra_options = array_slice( $yayextra_options, $offset, $limit + 1 );
		
		return array_values( $yayextra_options );
	}
}
