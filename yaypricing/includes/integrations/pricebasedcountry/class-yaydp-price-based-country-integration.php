<?php
/**
 * Handles the compatibility with WooCommerce Product Price Based on Countries (WPPBC) plugin.
 *
 * WPPBC injects country-specific prices through the `woocommerce_product_get_*_price`
 * filters, which WooCommerce only runs for the "view" context. YayPricing reads its base
 * price with the "original" context to bypass currency converters and apply its own
 * conversion via `yaydp_converted_price`. WPPBC does not hook that filter, so its country
 * price never reaches the discount calculation.
 *
 * This integration replaces the base price with the current zone price (fetched straight
 * from the WPPBC zone API, which supports both "manual" and "exchange rate" modes) so
 * discounts are computed from the country-specific price.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\PriceBasedCountry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Price_Based_Country_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'wcpbc_the_zone' ) ) {
			return;
		}
		add_filter( 'yaydp_other_source_product_base_price', array( __CLASS__, 'get_country_base_price' ), 10, 2 );
	}

	/**
	 * Returns the country-specific base price for the current pricing zone.
	 *
	 * Mirrors the regular-vs-sale selection of YAYDP_Pricing_Helper::get_product_price().
	 *
	 * @param float       $price Original (default currency) base price.
	 * @param \WC_Product $product Given product.
	 *
	 * @return float
	 */
	public static function get_country_base_price( $price, $product ) {
		if ( empty( $product ) || ! is_callable( array( $product, 'get_id' ) ) ) {
			return $price;
		}

		$zone = \wcpbc_the_zone();
		if ( ! $zone || ! is_callable( array( $zone, 'get_price_prop' ) ) ) {
			// Base country customer: no zone override.
			return $price;
		}

		$settings         = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		$based_on_regular = 'regular_price' === $settings->get_discount_base_on();

		$original_regular = (float) $product->get_regular_price( 'original' );
		$original_sale    = (float) $product->get_sale_price( 'original' );

		$zone_regular = $zone->get_price_prop( $product, $original_regular, '_regular_price' );
		$zone_sale    = $zone->get_price_prop( $product, $original_sale, '_sale_price' );

		$is_on_sale    = ! empty( $zone_sale ) && floatval( $zone_sale ) < floatval( $zone_regular );
		$selected_sale = $is_on_sale ? $zone_sale : $zone_regular;
		$country_price = $based_on_regular ? $zone_regular : $selected_sale;

		if ( '' === $country_price || null === $country_price || floatval( $country_price ) <= 0 ) {
			return $price;
		}

		return floatval( $country_price );
	}
}
