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
class YAYDP_Google_Tag_Manager_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( defined( 'GTM4WP_WPFILTER_EEC_PRODUCT_ARRAY' ) ) {
			add_filter( GTM4WP_WPFILTER_EEC_PRODUCT_ARRAY, array( $this, 'change_data' ), 100 );
		}
	}

	public function change_data( $data ) {
		if ( empty( $data ) ) {
			return $data;
		}

		if ( empty( $data['internal_id'] ) ) {
			return $data;
		}

		$product = \wc_get_product( $data['internal_id'] );

		if ( empty( $product ) || ! ( $product instanceof \WC_Product ) ) {
			return $data;
		}

		$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_discounted_price = $product_sale->get_min_max_discounted_price();

		// Note: Acceptable when not empty min_max. Current price is different with min_max.
		if ( is_null( $min_max_discounted_price ) ) {
			return $data;
		}

		if ( \yaydp_is_variable_product( $product ) ) {
			$min_price = \yaydp_get_variable_product_min_price( $product );
			$max_price = \yaydp_get_variable_product_max_price( $product );
			if ( $min_price === $min_max_discounted_price['min'] && $max_price === $min_max_discounted_price['max'] ) {
				return $data;
			}
		} else {
			$product_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
			if ( $product_price === $min_max_discounted_price['min'] && $product_price === $min_max_discounted_price['max'] ) {
				return $data;
			}
		}

		$min_discounted_price = $min_max_discounted_price['min'];
		$max_discounted_price = $min_max_discounted_price['max'];

		$data['price'] = $min_discounted_price;

		return $data;
	}

}
