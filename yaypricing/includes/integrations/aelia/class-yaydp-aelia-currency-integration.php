<?php
/**
 * Handles the integration of Aelia currency plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Aelia;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Aelia_Currency_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
			add_filter( 'yaydp_converted_price', array( __CLASS__, 'convert_price' ), 100, 2 );
			add_filter( 'yaydp_reversed_price', array( __CLASS__, 'reverse_price' ) );
			add_filter( 'yaydp_converted_fee', array( __CLASS__, 'convert_fee' ) );
			add_filter( 'yaydp_converted_pricing_value', array( __CLASS__, 'convert_pricing_value' ), 10, 2 );
		}
	}

	/**
	 * Converts a price from default currency to another
	 *
	 * @param float $price Original price.
	 * @param bool  $from_yaydp Is source from YayPricing.
	 *
	 * @return float The Converted price as a float value
	 */
	public static function convert_price( $price, $from_yaydp = false ) {
		if ( ! $from_yaydp ) {
			return $price;
		}
		$currency_switcher = \WC_Aelia_CurrencySwitcher::instance();
		$to_currency       = $currency_switcher->get_selected_currency();
		$from_currency     = \WC_Aelia_CurrencySwitcher::settings()->base_currency();
		$price             = $currency_switcher->convert( $price, $from_currency, $to_currency );
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
		$currency_switcher = \WC_Aelia_CurrencySwitcher::instance();
		$to_currency       = $currency_switcher->get_selected_currency();
		$from_currency     = \WC_Aelia_CurrencySwitcher::settings()->base_currency();
		$price             = $currency_switcher->convert( $price, $to_currency, $from_currency );
		return $price;
	}

	/**
	 * Convert rule pricing value
	 *
	 * @param float  $value Pricing value.
	 * @param string $type Pricing type.
	 */
	public static function convert_pricing_value( $value, $type ) {
		if ( false === strpos( $type, 'percentage' ) ) {
			return self::convert_price( $value, true );
		}
		return $value;
	}

	/**
	 * Convert fee to currency.
	 *
	 * @param float $value Pricing value.
	 */
	public static function convert_fee( $value ) {
		return self::convert_price( $value, true );
	}

}
