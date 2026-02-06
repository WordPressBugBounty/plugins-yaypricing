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
class YAYDP_WooCommerce_Currency_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! self::is_3rd_party_activated() ) {
			return;
		}
		add_filter( 'yaydp_converted_price', array( __CLASS__, 'convert_price' ) );
		add_filter( 'yaydp_reversed_price', array( __CLASS__, 'reverse_price' ) );
	}

	public static function is_3rd_party_activated() {
		return class_exists( '\WCPay\MultiCurrency\MultiCurrency' );
	}

	/**
	 * Converts a price from default currency to another
	 *
	 * @param float $price Original price.
	 *
	 * @return float The Converted price as a float value
	 */
	public static function convert_price( $price ) {
		if ( ! self::is_3rd_party_activated() ) {
			return $price;
		}

		$wc_payments_multi_currency = \WC_Payments_Multi_Currency();

		if ( empty( $wc_payments_multi_currency ) || ! ( $wc_payments_multi_currency instanceof \WCPay\MultiCurrency\MultiCurrency ) ) {
			return $price;
		}

		$store_currency    = $wc_payments_multi_currency->get_default_currency()->get_code();
		$selected_currency = $wc_payments_multi_currency->get_selected_currency()->get_code();

		// If currencies are the same, no need to convert prices in the query.
		if ( $store_currency === $selected_currency ) {
			return $price;
		}

		try {

			$new_price = $wc_payments_multi_currency->get_price( $price, 'product' );

			return $new_price;
		} catch ( \Exception $e ) {
			return $price;
		}
	}

	public static function reverse_price( $price ) {
		if ( ! self::is_3rd_party_activated() ) {
			return $price;
		}

		$wc_payments_multi_currency = \WC_Payments_Multi_Currency();

		if ( empty( $wc_payments_multi_currency ) || ! ( $wc_payments_multi_currency instanceof \WCPay\MultiCurrency\MultiCurrency ) ) {
			return $price;
		}

		$store_currency    = $wc_payments_multi_currency->get_default_currency()->get_code();
		$selected_currency = $wc_payments_multi_currency->get_selected_currency()->get_code();

		// If currencies are the same, no need to convert prices in the query.
		if ( $store_currency === $selected_currency ) {
			return $price;
		}

		try {
			$currency        = $wc_payments_multi_currency->get_selected_currency();
			$current_rate    = $currency->get_rate();
			if ( empty( $current_rate ) ) {
				return $price;
			}
			$new_price = ( (float) $price ) / $current_rate;

			$type = 'product';
			$charm_compatible_types = [ 'product', 'shipping' ];
			$apply_charm_pricing    = $wc_payments_multi_currency->get_apply_charm_only_to_products()
				? 'product' === $type
				: in_array( $type, $charm_compatible_types, true );
			$default_currency = $wc_payments_multi_currency->get_default_currency();

			return $wc_payments_multi_currency->get_adjusted_price( $new_price, $apply_charm_pricing, $default_currency );
		} catch ( \Exception $e ) {
			return $price;
		}
	}

}
