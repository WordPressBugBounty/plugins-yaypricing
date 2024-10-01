<?php
/**
 * Handles the integration of YayCurrency plugin with our system
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
class YAYDP_WC_Listing_Ads_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( defined( 'WC_GLA_VERSION' ) ) {
			add_filter( 'woocommerce_gla_product_attribute_value_sale_price', array( $this, 'change_sale_price' ), 100, 3 );
		}
	}

	public function change_sale_price( $sale_price, $product, $tax_excluded ) {
		if ( empty( $product ) ) {
			return $sale_price;
		}

		try {

			$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
			$min_max_discounted_price = $product_sale->get_min_max_discounted_price();

			// Note: Acceptable when not empty min_max. Current price is different with min_max.
			if ( is_null( $min_max_discounted_price ) ) {
				return $sale_price;
			}

			if ( \yaydp_is_variable_product( $product ) ) {
				$min_price = \yaydp_get_variable_product_min_price( $product );
				$max_price = \yaydp_get_variable_product_max_price( $product );
				if ( $min_price === $min_max_discounted_price['min'] && $max_price === $min_max_discounted_price['max'] ) {
					return $sale_price;
				}
			} else {
				$product_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
				if ( $product_price === $min_max_discounted_price['min'] && $product_price === $min_max_discounted_price['max'] ) {
					return $sale_price;
				}
			}

			$min_discounted_price = $min_max_discounted_price['min'];
			$max_discounted_price = $min_max_discounted_price['max'];

			if ( $tax_excluded ) {
				$sale_price = \wc_get_price_excluding_tax( $product, array( 'price' => $min_discounted_price ) );
			} else {
				$sale_price = \wc_get_price_including_tax( $product, array( 'price' => $min_discounted_price ) );
			}

			return $sale_price;

		} catch ( \Error $error ) {
			return $sale_price;
		}

	}

}
