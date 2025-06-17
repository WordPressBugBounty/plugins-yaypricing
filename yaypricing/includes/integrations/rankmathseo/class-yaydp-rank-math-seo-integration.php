<?php
/**
 * Handles the integration of YayPricing plugin with Google Rich Results Test (Structured Data)
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\RankMathSeo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAYDP_Rank_Math_Seo_Integration {

	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
		add_filter( 'rank_math/json_ld', array( $this, 'modify_structured_data_product' ), PHP_INT_MAX, 2 );
	} 

	/**
	 * Modifies the Rank Math SEO structured data to include YayPricing discounted prices
	 *
	 * @param array $data The structured data array from Rank Math
	 * @param object $jsonld The JSON-LD object containing post data
	 * @return array Modified structured data with updated pricing information
	 */
	public function modify_structured_data_product( $data, $jsonld ) {
		$product = \wc_get_product($jsonld->post_id);
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $data;
		}

		$discounted_prices = $this->get_yaydp_discounted_prices( $product );
		
		if ( empty( $discounted_prices ) ) {
			return $data;
		}

		if ( isset( $data['richSnippet']['offers'] ) && is_array( $data['richSnippet']['offers'] ) ) {
			$data['richSnippet']['offers'] = $this->modify_offer_prices( $data['richSnippet']['offers'], $product, $discounted_prices );
		}

		return $data;
	}

	/**
	 * Get YayPricing discounted prices for a product
	 *
	 * @param \WC_Product $product The WooCommerce product object.
	 * @return array|null Array containing min/max discounted prices or null if no discounts.
	 */
	private function get_yaydp_discounted_prices( $product ) {
		$product_sale = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$discounted_prices = $product_sale->get_min_max_discounted_price();

		if ( is_null( $discounted_prices ) ) {
			return null;
		}

		$original_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		
		if ( \yaydp_is_variable_product( $product ) ) {
			$min_price = \yaydp_get_variable_product_min_price( $product );
			$max_price = \yaydp_get_variable_product_max_price( $product );
			
			if ( $min_price === $discounted_prices['min'] && $max_price === $discounted_prices['max'] ) {
				return null;
			}
		} else {
			if ( $original_price === $discounted_prices['min'] && $original_price === $discounted_prices['max'] ) {
				return null;
			}
		}

		return $discounted_prices;
	}

	/**
	 * Modify individual offer prices with YayPricing discounts
	 *
	 * @param array       $offer             The offer array from structured data.
	 * @param \WC_Product $product           The WooCommerce product object.
	 * @param array       $discounted_prices Array containing min/max discounted prices.
	 * @return array Modified offer array.
	 */
	private function modify_offer_prices( $offer, $product, $discounted_prices ) {
		$currency = get_woocommerce_currency();
		$price_decimals = wc_get_price_decimals();
		$is_variable = \yaydp_is_variable_product( $product );
		$has_price_range = $is_variable && $discounted_prices['min'] !== $discounted_prices['max'];

		if ( $has_price_range ) {
			$offer['lowPrice'] = wc_format_decimal( $discounted_prices['min'], $price_decimals );
			$offer['highPrice'] = wc_format_decimal( $discounted_prices['max'], $price_decimals );
			unset( $offer['price'] );
		} else {
			$offer['price'] = wc_format_decimal( $discounted_prices['min'], $price_decimals );
		}

		if ( ! empty( $offer['priceSpecification'] ) && is_array( $offer['priceSpecification'] ) ) {
			$offer['priceSpecification']['price'] = wc_format_decimal( $discounted_prices['min'], $price_decimals );
			$offer['priceSpecification']['priceCurrency'] = $currency;
		}

		$offer['priceCurrency'] = $currency;

		return $offer;
	}

	public static function init() {
		self::get_instance();
	}
}

YAYDP_Rank_Math_Seo_Integration::init();