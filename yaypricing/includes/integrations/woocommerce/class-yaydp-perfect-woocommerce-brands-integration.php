<?php
/**
 * Handles the integration of Perfect WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Perfect_WooCommerce_Brands_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	private $taxonomy = 'pwb-brand';

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'PWB_PLUGIN_FILE' ) ) {
			return;
		}
		add_action( 'init', array( $this, 'initialize_pwb_integration' ), 100 );
	}

	/**
	 * Initialize callback
	 */
	public function initialize_pwb_integration() {
		if ( ! defined( 'PWB_PLUGIN_FILE' ) ) {
			return;
		}
		add_filter( 'yaydp_admin_product_filters', array( $this, 'admin_product_filters' ) );
		add_filter( 'yaydp_admin_extra_localize_data', array( $this, 'taxonomies_localize_data' ) );

		$pwb_taxonomy = $this->taxonomy;
		add_filter( "yaydp_admin_custom_filter_{$pwb_taxonomy}_result", array( __CLASS__, 'get_filter_result' ), 10, 5 );
		add_filter( "yaydp_get_matching_products_by_{$pwb_taxonomy}", array( __CLASS__, 'get_matching_products' ), 10, 4 );
		add_filter( "yaydp_check_condition_by_{$pwb_taxonomy}", array( __CLASS__, 'check_condition' ), 10, 3 );
	}

	/**
	 * Add filter to current product filters
	 *
	 * @param array $filters Given filters.
	 *
	 * @return array
	 */
	public function admin_product_filters( $filters ) {
		$pwb_filter_array = array(
			array(
				'value'        => $this->taxonomy,
				'label'        => 'Perfect WooCommerce Brands',
				'comparations' => array(
					array(
						'value' => 'in_list',
						'label' => 'In list',
					),
					array(
						'value' => 'not_in_list',
						'label' => 'Not in list',
					),
				),
			),
		);
		return array_merge( $filters, $pwb_filter_array );
	}

	/**
	 * Add Perfect WooCommerce Brands taxonomies data to current localize data
	 *
	 * @param array $data Localize data.
	 *
	 * @return array
	 */
	public function taxonomies_localize_data( $data ) {
		return array();
	}

	/**
	 * Alter search filter result
	 *
	 * @param array  $result Search result.
	 * @param string $filter_name Name of the filter.
	 * @param string $search Search text.
	 * @param int    $page Current page.
	 * @param int    $limit Limit result to get.
	 *
	 * @return array
	 */
	public static function get_filter_result( $result, $filter_name, $search = '', $page = 1, $limit = YAYDP_SEARCH_LIMIT ) {
		$offset = ( $page - 1 ) * $limit;
		$args   = array(
			'number'     => $limit + 1,
			'offset'     => $offset,
			'order'      => 'ASC',
			'orderby'    => 'name',
			'taxonomy'   => $filter_name,
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
	 * Alter matching products result
	 *
	 * @param array  $products Result.
	 * @param string $type  Custom Post Type taxonomy name.
	 * @param string $value  Custom Post Type term value.
	 * @param string $comparation Comparison operation.
	 *
	 * @return array
	 */
	public static function get_matching_products( $products, $type, $value, $comparation ) {
		if ( empty( $value ) ) {
			return array();
		}
		$args     = array(
			'limit'     => -1,
			'order'     => 'ASC',
			'orderby'   => 'title',
			'tax_query' => array(
				array(
					'taxonomy' => $type,
					'terms'    => $value,
					'operator' => 'in_list' === $comparation ? 'IN' : 'NOT IN',
				),
			),
		);
		$products = \wc_get_products( $args );
		return $products;
	}

	/**
	 * Alter check condition result
	 *
	 * @param array       $result Result.
	 * @param \WC_Product $product  Given product.
	 * @param array       $filter Checking filter.
	 *
	 * @return bool
	 */
	public static function check_condition( $result, $product, $filter ) {
		$filter_brand_ids = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		
		// Early return if no brands to check against
		if ( empty( $filter_brand_ids ) ) {
			return 'in_list' !== $filter['comparation'];
		}

		$product_id = $product->get_id();
		$taxonomy   = $filter['type'];
		
		// Get brand IDs directly (more efficient than getting term objects)
		$product_brand_ids = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'ids' ) );
		
		// Handle WP_Error
		if ( is_wp_error( $product_brand_ids ) ) {
			$product_brand_ids = array();
		}

		$has_match = false;

		// Check if any brand ID matches directly
		if ( ! empty( $product_brand_ids ) ) {
			$has_match = ! empty( array_intersect( $product_brand_ids, $filter_brand_ids ) );
			
			// If no direct match, check parent brands (ancestors)
			if ( ! $has_match ) {
				foreach ( $product_brand_ids as $brand_id ) {
					$brand_ancestors = get_ancestors( $brand_id, $taxonomy );
					if ( ! empty( $brand_ancestors ) && ! empty( array_intersect( $brand_ancestors, $filter_brand_ids ) ) ) {
						$has_match = true;
						break;
					}
				}
			}
		}

		// If still no match, check parent product (for variations)
		if ( ! $has_match ) {
			$parent_id = $product->get_parent_id();
			if ( ! empty( $parent_id ) ) {
				$parent_product = \wc_get_product( $parent_id );
				if ( $parent_product ) {
					// Recursive call returns final result with comparison applied
					return self::check_condition( false, $parent_product, $filter );
				}
			}
		}

		return 'in_list' === $filter['comparation'] ? $has_match : ! $has_match;
	}
}

