<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
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
class YAYDP_Klarna_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {

		add_filter( 'advanced_woo_discount_rules_get_product_discount_price_from_custom_price', array( $this, 'klarna_price' ), 100, 2 );
	}

	public function klarna_price( $price, $product ) {

		if ( empty( $product ) ) {
			return $price;
		}

		$show_discounted_price = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_discounted_price();
		if ( ! $show_discounted_price ) {
			return $price;
		}

		$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_discounted_price = $product_sale->get_min_max_discounted_price();

		// Note: Acceptable when not empty min_max. Current price is different with min_max.
		if ( is_null( $min_max_discounted_price ) ) {
			return $price;
		}

		if ( \yaydp_is_variable_product( $product ) ) {
			$min_price = \yaydp_get_variable_product_min_price( $product );
			$max_price = \yaydp_get_variable_product_max_price( $product );
			if ( $min_price === $min_max_discounted_price['min'] && $max_price === $min_max_discounted_price['max'] ) {
				return $price;
			}
		} else {
			$product_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
			if ( $product_price === $min_max_discounted_price['min'] && $product_price === $min_max_discounted_price['max'] ) {
				return $price;
			}
		}

		$min_discounted_price               = $min_max_discounted_price['min'];
		$max_discounted_price               = $min_max_discounted_price['max'];

		return $min_discounted_price;

	}

}
