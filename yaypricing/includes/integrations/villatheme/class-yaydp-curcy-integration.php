<?php
/**
 * Handles the integration of YayCurrency plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\VillaTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_CURCY_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( class_exists( 'WOOMULTI_CURRENCY' ) || class_exists( 'WOOMULTI_CURRENCY_F' ) ) {
			add_filter( 'yaydp_converted_price', array( __CLASS__, 'convert_price' ) );
			add_filter( 'yaydp_reversed_tax', array( __CLASS__, 'convert_price' ) );
			add_filter( 'yaydp_reversed_price', array( __CLASS__, 'reverse_price' ) );
			add_filter( 'yaydp_product_fixed_price', array( __CLASS__, 'get_product_fixed_price' ), 10, 2 );
		}
	}

	/**
	 * Converts a price from default currency to another
	 *
	 * @param float $price Original price.
	 *
	 * @return float The Converted price as a float value
	 */
	public static function convert_price( $price ) {
		if ( function_exists( 'wmc_get_price' ) ) {
			$price = \wmc_get_price( $price );
		}
		return $price;
	}

	/**
	 * Reverse a price from one currency to the default
	 *
	 * @param float $price Converted price.
	 *
	 * @return float The original price as a float value
	 */
	public static function reverse_price( $price ) {
		if ( function_exists( 'wmc_revert_price' ) && class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
			$setting          = \WOOMULTI_CURRENCY_Data::get_ins();
			$default_currency = $setting->get_default_currency();
			$current_currency = $setting->get_current_currency();
			if ( $current_currency !== $default_currency ) {
				$price = \wmc_revert_price( $price, $default_currency );
			}
		}
		return $price;
	}

	/**
	 * Retrieves the original price of a product
	 * If it has been fixed, returns the fixed one
	 *
	 * @param float       $price Converted price.
	 * @param \WC_Product $product Given product.
	 *
	 * @return float The original product price
	 */
	public static function get_product_fixed_price( $price, $product ) {
		if ( class_exists( 'WOOMULTI_CURRENCY_Frontend_Price' ) && class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
			$settings         = \WOOMULTI_CURRENCY_Data::get_ins();
			$current_currency = $settings->get_current_currency();
			$default_currency = get_option( 'woocommerce_currency' );
			if ( $settings->check_fixed_price() ) {
				$yaydp_settings            = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
				$is_based_on_regular_price = 'regular_price' === $yaydp_settings->get_discount_base_on();
				$fixed_price               = \WOOMULTI_CURRENCY_Frontend_Price::get_fixed_price( $product, $current_currency, $is_based_on_regular_price ? 'regular_price' : 'sale_price' );
				if ( false !== $fixed_price || ( $settings->get_params( 'ignore_exchange_rate' ) && $default_currency !== $current_currency ) ) {
					$price = $fixed_price;
					if ( $default_currency !== $current_currency ) {
						$price = self::reverse_price( $price );
					}
				}
			}
		}
		return $price;
	}

}
