<?php
/**
 * Handles the integration of YayCurrency plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_WC_Product_Feed_Pro_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( class_exists( 'WooSEA_Get_Products' ) ) {
			add_filter( 'adt_get_product_data', array( $this, 'change_feed_item' ), 100, 3 );
		}
	}

	public function change_feed_item( $product_data, $feed, $product ) {
		if ( empty( $product ) ) {
			return $product_data;
		}

		$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_discounted_price = $product_sale->get_min_max_discounted_price();

		// Note: Acceptable when not empty min_max. Current price is different with min_max.
		if ( is_null( $min_max_discounted_price ) ) {
			return $product_data;
		}

		if ( \yaydp_is_variable_product( $product ) ) {
			$min_price = \yaydp_get_variable_product_min_price( $product );
			$max_price = \yaydp_get_variable_product_max_price( $product );
			if ( $min_price === $min_max_discounted_price['min'] && $max_price === $min_max_discounted_price['max'] ) {
				return $product_data;
			}
		} else {
			$product_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
			if ( $product_price === $min_max_discounted_price['min'] && $product_price === $min_max_discounted_price['max'] ) {
				return $product_data;
			}
		}

		$min_discounted_price = $min_max_discounted_price['min'];
		$max_discounted_price = $min_max_discounted_price['max'];

		$product_data['net_price']  = \wc_get_price_excluding_tax( $product, array( 'price' => $min_discounted_price ) );
		$product_data['price'] = \wc_get_price_including_tax( $product, array( 'price' => $min_discounted_price ) );
		$product_data['sale_price'] = \wc_get_price_including_tax( $product, array( 'price' => $min_discounted_price ) );
		$product_data['net_sale_price'] = \wc_get_price_excluding_tax( $product, array( 'price' => $min_discounted_price ) );

		return $product_data;
	}

}
