<?php
/**
 * Handles the integration of YayCurrency plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Geiger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_GTM_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'GTM4WP_VERSION' ) || ! defined( 'GTM4WP_WPFILTER_EEC_PRODUCT_ARRAY' ) ) {
			return;
		}
		add_filter( GTM4WP_WPFILTER_EEC_PRODUCT_ARRAY, array( $this, 'change_item' ), 100, 2 );
	}

	public function change_item( $feed_item, $product ) {

		if ( empty( $feed_item['internal_id'] ) ) {
			return $feed_item;
		}

		$product = \wc_get_product( $feed_item['internal_id'] );

		if ( empty( $product ) || ! ( $product instanceof \WC_Product ) ) {
			return $feed_item;
		}

		$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_discounted_price = $product_sale->get_min_max_discounted_price();

		// Note: Acceptable when not empty min_max. Current price is different with min_max.
		if ( is_null( $min_max_discounted_price ) ) {
			return $feed_item;
		}

		if ( \yaydp_is_variable_product( $product ) ) {
			$min_price = \yaydp_get_variable_product_min_price( $product );
			$max_price = \yaydp_get_variable_product_max_price( $product );
			if ( $min_price === $min_max_discounted_price['min'] && $max_price === $min_max_discounted_price['max'] ) {
				return $feed_item;
			}
		} else {
			$product_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
			if ( $product_price === $min_max_discounted_price['min'] && $product_price === $min_max_discounted_price['max'] ) {
				return $feed_item;
			}
		}

		$min_discounted_price = $min_max_discounted_price['min'];
		// $max_discounted_price = $min_max_discounted_price['max'];

		$feed_item['price'] = \wc_get_price_including_tax( $product, array( 'price' => $min_discounted_price ) );

		return $feed_item;
	}

}
