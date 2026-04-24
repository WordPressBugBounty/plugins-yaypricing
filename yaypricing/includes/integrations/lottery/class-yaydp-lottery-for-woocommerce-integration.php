<?php
/**
 * Handles the integration of Lottery for WooCommerce plugin with YayPricing
 *
 * This integration fixes the issue where giveaway products created by the
 * lottery-for-woocommerce plugin are not recognized by YayPricing's product
 * matching system.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Lottery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Lottery_For_WooCommerce_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'FP_Lottery' ) && ! class_exists( 'WC_Product_Lottery' ) ) {
			return;
		}

		add_filter( 'product_type_selector', array( $this, 'register_lottery_product_type' ), 999, 1 );

		add_filter( 'woocommerce_product_class', array( $this, 'register_lottery_product_class' ), 10, 2 );

		add_filter( 'woocommerce_data_stores', array( $this, 'register_lottery_data_store' ), 10, 1 );

		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'include_lottery_products_in_query' ), 10, 2 );
	}

	/**
	 * Register the lottery product type with WooCommerce
	 * This ensures lottery products are included in general product queries
	 *
	 * @param array $types Product types.
	 *
	 * @return array
	 */
	public function register_lottery_product_type( $types ) {
		if ( ! isset( $types['lottery'] ) ) {
			$types['lottery'] = __( 'Giveaway', 'yaypricing' );
		}
		return $types;
	}

	/**
	 * Include lottery products in WooCommerce product queries
	 * This fixes wc_get_products() excluding lottery products when no type is specified
	 *
	 * @param array $query WP_Query args.
	 * @param array $query_vars Query variables passed to wc_get_products().
	 *
	 * @return array
	 */
	public function include_lottery_products_in_query( $query, $query_vars ) {
		if ( ! isset( $query_vars['type'] ) || empty( $query_vars['type'] ) ) {
			if ( isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ) {
				foreach ( $query['tax_query'] as $key => $tax_query_item ) {
					if ( isset( $tax_query_item['taxonomy'] ) && 'product_type' === $tax_query_item['taxonomy'] ) {
						if ( isset( $query['tax_query'][ $key ]['terms'] ) && is_array( $query['tax_query'][ $key ]['terms'] ) ) {
							if ( ! in_array( 'lottery', $query['tax_query'][ $key ]['terms'], true ) ) {
								$query['tax_query'][ $key ]['terms'][] = 'lottery';
							}
						}
					}
				}
			}
		}

		return $query;
	}

	/**
	 * Register the lottery product class with WooCommerce
	 *
	 * @param string $classname The product class name.
	 * @param string $product_type The product type.
	 *
	 * @return string
	 */
	public function register_lottery_product_class( $classname, $product_type ) {
		if ( 'lottery' === $product_type && class_exists( 'WC_Product_Lottery' ) ) {
			return 'WC_Product_Lottery';
		}
		return $classname;
	}

	/**
	 * Register the lottery product data store with WooCommerce
	 *
	 * @param array $stores Data stores.
	 *
	 * @return array
	 */
	public function register_lottery_data_store( $stores ) {
		if ( class_exists( 'WC_Product_Lottery_Data_Store_CPT' ) ) {
			$stores['product-lottery'] = 'WC_Product_Lottery_Data_Store_CPT';
		}
		return $stores;
	}
}
