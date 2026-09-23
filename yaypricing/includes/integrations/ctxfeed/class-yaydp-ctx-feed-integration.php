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

		add_filter( 'woo_feed_filter_product_sale_price', array( $this, 'get_discounted_price' ), 10, 5 );
		add_filter( 'woo_feed_filter_product_sale_price_with_tax', array( $this, 'get_discounted_price' ), 10, 5 );
		add_filter( 'woo_feed_filter_product_price_with_tax', array( $this, 'get_discounted_price' ), 10, 5 );
		add_filter( 'woo_feed_filter_product_regular_price_with_tax', array( $this, 'get_discounted_price' ), 10, 5 );
	}

	public function get_discounted_price( $price, $product, $config, $with_tax, $price_type ) {
		if ( empty( $product ) ) {
			return $price;
		}

		$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_discounted_price = $product_sale->get_min_max_discounted_price();

		if ( is_null( $min_max_discounted_price ) ) {
			return $price;
		}

		$discounted_price = $min_max_discounted_price['min'];

		if ( $with_tax ) {
			$discounted_price = \wc_get_price_including_tax( $product, array( 'price' => $discounted_price ) );
		}

		return $discounted_price;
	}
}
