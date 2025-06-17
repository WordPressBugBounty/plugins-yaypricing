<?php
/**
 * CTX Feed and YayPricing compatibility.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\CtxFeed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAYDP_Ctx_Feed_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
		if ( ! class_exists( 'Woo_Feed' ) ) {
			return;
		}

		add_filter( 'woo_feed_filter_product_sale_price', array( $this, 'get_sale_price' ), 10, 5 );
	}

	public function get_sale_price( $price, $product, $config, $with_tax, $price_type ) {
		if ( empty( $product ) ) {
			return $price;
		}

		$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_discounted_price = $product_sale->get_min_max_discounted_price();

		if ( is_null( $min_max_discounted_price ) ) {
			return $price;
		}

		$min_discounted_price = $min_max_discounted_price['min'];
		$price = \wc_get_price_including_tax( $product, array( 'price' => $min_discounted_price ) );

		return $price;
	}
}
