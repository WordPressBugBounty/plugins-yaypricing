<?php
/**
 * Defines the constants used in Admin settings page
 *
 * @package YayPricing\Constants
 */

namespace YAYDP\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Admin_Settings {

	/**
	 * Retrieves the maximum number of search results to display
	 */
	public static function get_search_limit() {
		return \YAYDP_SEARCH_LIMIT;
	}

	/**
	 * Retrieves the available filters for products
	 */
	public static function get_product_filters() {
		$filters = array(
			array(
				'value'        => 'product',
				'label'        => 'Product',
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
			array(
				'value'        => 'product_variation',
				'label'        => 'Product variation',
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
			array(
				'value'        => 'product_category',
				'label'        => 'Product category',
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
			array(
				'value'        => 'product_attribute',
				'label'        => 'Product attribute',
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
			array(
				'value'        => 'product_attribute_taxonomies',
				'label'        => 'Product attribute taxonomies',
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
			array(
				'value'        => 'product_tag',
				'label'        => 'Product tag',
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
			array(
				'value'        => 'product_price',
				'label'        => 'Product price',
				'comparations' => array(
					array(
						'value' => 'greater_than',
						'label' => 'Greater than',
					),
					array(
						'value' => 'less_than',
						'label' => 'Less than',
					),
					/**
					 * New comparation types.
					 *
					 * @since 2.3
					 */
					array(
						'value' => 'gte',
						'label' => 'Greater than or equal',
					),
					array(
						'value' => 'lte',
						'label' => 'Less than or equal',
					),
				),
			),
			array(
				'value'        => 'product_in_stock',
				'label'        => 'Product in stock',
				'comparations' => array(
					array(
						'value' => 'greater_than',
						'label' => 'Greater than',
					),
					array(
						'value' => 'less_than',
						'label' => 'Less than',
					),
					/**
					 * New comparation types.
					 *
					 * @since 2.3
					 */
					array(
						'value' => 'gte',
						'label' => 'Greater than or equal',
					),
					array(
						'value' => 'lte',
						'label' => 'Less than or equal',
					),
				),
			),
			/**
			 * New comparation types.
			 *
			 * @since 3.4.2
			 */
			array(
				'value'        => 'products_on_sale_wc',
				'label'        => __( 'WooCommerce on sale status', 'yaypricing' ),
				'comparations' => array(
					array(
						'value' => 'on_sale',
						'label' => __( 'On sale', 'yaypricing' ),
					),
					array(
						'value' => 'not_on_sale',
						'label' => __( 'Not on sale', 'yaypricing' ),
					),
				),
			),
			array(
				'value'        => 'all_product',
				'label'        => 'All products',
				'comparations' => array(),
			),
			array(
				'value'        => 'shipping_class',
				'label'        => __( 'Shipping class', 'yaypricing' ),
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
		return apply_filters( 'yaydp_admin_product_filters', $filters );
	}
	public static function get_extra_conditions() {
		return apply_filters( 'yaydp_extra_conditions', array() );
	}
}
