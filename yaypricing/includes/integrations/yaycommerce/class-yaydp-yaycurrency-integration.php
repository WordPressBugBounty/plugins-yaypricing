<?php
/**
 * Handles the integration of YayCurrency plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\YayCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_YayCurrency_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( defined( 'YAYE_VERSION' ) ) {
			return;
		}
		if ( class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			if ( ! self::is_disable_convert() ) {
				add_filter( 'yaydp_converted_price', array( __CLASS__, 'convert_price' ) );
				add_filter( 'yaydp_reversed_price', array( __CLASS__, 'reverse_price' ) );
				add_filter( 'yaydp_product_fixed_price', array( __CLASS__, 'get_product_fixed_price' ), 10, 2 );
			}
			add_action( 'yaydp_before_set_cart_item_price', array( __CLASS__, 'before_set_cart_item_price' ), 10, 2 );
			add_action( 'yaydp_remove_3rd_currency_format', array( __CLASS__, 'remove_currency_formmat' ), 10 );
			add_filter( 'yaydp_checkout_coupon_fee_html', array( __CLASS__, 'get_checkout_coupon_fee_html' ), 10, 2 );
			add_filter( 'yaydp_extra_conditions', array( $this, 'currency_condition' ) );
			add_filter( 'yaydp_check_yaycurrency_currency_condition', array( $this, 'check_yaycurrency_currency_condition' ), 10, 2 );
			add_filter( 'yaydp_converted_fee', array( __CLASS__, 'convert_fee' ) );
		}
		if ( class_exists( 'Yay_Currency\Engine\FEPages\WooCommerceCurrency' ) ) {
			add_action(
				'woocommerce_cart_calculate_fees',
				function() {
					remove_action( 'woocommerce_cart_calculate_fees', array( \Yay_Currency\Engine\FEPages\WooCommerceCurrency::get_instance(), 'recalculate_cart_fees' ), 10 );
				},
				11
			);
		}
	}

	public static function is_disable_convert() {
		return get_option( 'yaydp_prevent_yaycurrency_convert_hooks' );
	}

	public static function before_set_cart_item_price() {
		remove_filter( 'yay_currency_get_price_fixed_by_currency', array( __CLASS__, 'reject_fixed_price' ), 11 );
		add_filter( 'yay_currency_get_price_fixed_by_currency', array( __CLASS__, 'reject_fixed_price' ), 11, 4 );
	}

	/**
	 * Convert fee to currency.
	 *
	 * @param float $value Pricing value.
	 */
	public static function convert_fee( $value ) {
		return self::convert_price( $value, true );
	}

	/**
	 * Converts a price from default currency to another
	 *
	 * @param float $price Original price.
	 *
	 * @return float The Converted price as a float value
	 */
	public static function convert_price( $price ) {
		if ( class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			$apply_currency               = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
			$disable_checkout_in_currency = \Yay_Currency\Helpers\YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency );
			if ( ! $disable_checkout_in_currency ) {
				$price = \Yay_Currency\Helpers\YayCurrencyHelper::calculate_price_by_currency( $price, false, $apply_currency );
			}
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
		if ( class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			$apply_currency               = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
			$disable_checkout_in_currency = \Yay_Currency\Helpers\YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency );
			if ( ! $disable_checkout_in_currency ) {
				$price = \Yay_Currency\Helpers\YayCurrencyHelper::reverse_calculate_price_by_currency( $price );
			}
		}
		return $price;
	}

	public static function get_product_fixed_price( $price, $product ) {
		if ( class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) && class_exists( '\Yay_Currency\Helpers\FixedPriceHelper' ) ) {
			$apply_currency               = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
			$disable_checkout_in_currency = \Yay_Currency\Helpers\YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency );
			$is_set_fixed_price           = \Yay_Currency\Helpers\FixedPriceHelper::is_set_fixed_price();
			$is_product_fixed_price       = \Yay_Currency\Helpers\FixedPriceHelper::product_is_set_fixed_price_by_currency( $product, $apply_currency );
			if ( $is_set_fixed_price && ! $disable_checkout_in_currency && $is_product_fixed_price ) {
				$price = \Yay_Currency\Helpers\FixedPriceHelper::get_price_fixed_by_apply_currency( $product, $price, $apply_currency );
				$price = self::reverse_price( $price );
			}
		}
		return $price;
	}

	public static function reject_fixed_price( $fixed_price, $product, $apply_currency, $price ) {
		if ( class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			$apply_currency               = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
			$disable_checkout_in_currency = \Yay_Currency\Helpers\YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency );
			$is_set_fixed_price           = \Yay_Currency\Helpers\FixedPriceHelper::is_set_fixed_price();
			if ( $is_set_fixed_price && ! $disable_checkout_in_currency ) {
				if ( self::is_disable_convert() ) {
					return self::reverse_price( $price );
				}
				return $price;
			}
		}
		return $fixed_price;
	}

	public static function remove_currency_formmat() {
		if ( class_exists( '\Yay_Currency\Engine\FEPages\WooCommercePriceFormat' ) ) {
			$priority = apply_filters( 'yay_currency_woocommerce_currency_priority', 10 );
			remove_filter( 'woocommerce_currency', array( \Yay_Currency\Engine\FEPages\WooCommercePriceFormat::get_instance(), 'change_woocommerce_currency' ), $priority, 1 );
			remove_filter( 'woocommerce_currency_symbol', array( \Yay_Currency\Engine\FEPages\WooCommercePriceFormat::get_instance(), 'change_existing_currency_symbol' ), $priority, 2 );
			remove_filter( 'pre_option_woocommerce_currency_pos', array( \Yay_Currency\Engine\FEPages\WooCommercePriceFormat::get_instance(), 'change_currency_position' ), $priority );
			remove_filter( 'wc_get_price_thousand_separator', array( \Yay_Currency\Engine\FEPages\WooCommercePriceFormat::get_instance(), 'change_thousand_separator' ), $priority );
			remove_filter( 'wc_get_price_decimal_separator', array( \Yay_Currency\Engine\FEPages\WooCommercePriceFormat::get_instance(), 'change_decimal_separator' ), $priority );
			remove_filter( 'wc_get_price_decimals', array( \Yay_Currency\Engine\FEPages\WooCommercePriceFormat::get_instance(), 'change_number_decimals' ), $priority );
		}
	}

	public static function get_checkout_coupon_fee_html( $result, $origin_html ) {
		$pattern = "/<span class='yay-currency-checkout-converted-approximately'>(.*)<\/span>\)<\/span>/";
		// $pattern = '/<span ?.*>(.*)<\/span>/';
		preg_match( $pattern, $origin_html, $matches );
		if ( isset( $matches[1] ) ) {
			$result .= " <span class='yay-currency-checkout-converted-approximately'>" . $matches[1] . '</span>)</span>';
		}
		return $result;
	}

	public function currency_condition( $conditions ) {
		if ( class_exists( '\Yay_Currency\Helpers\Helper' ) ) {
			$currencies     = \Yay_Currency\Helpers\Helper::get_currencies_post_type();
			$woo_currencies = \Yay_Currency\Helpers\Helper::woo_list_currencies();
			if ( ! empty( $currencies ) ) {
				$currencies_condition = array(
					'value'        => 'yaycurrency_currency',
					'label'        => 'YayCurrency current currency',
					'comparations' => array(
						array(
							'value' => 'in_list',
							'label' => 'In list',
						),
						array(
							'value' => 'not_in_list',
							'label' => 'Not in list',
						),
					),
					'values'       => array_map(
						function( $currency ) use ( $woo_currencies ) {
							$currency_code = $currency->post_title;
							$currency_name = isset( $woo_currencies[ $currency_code ] ) ? $woo_currencies[ $currency_code ] : '';
							return array(
								'value' => $currency->ID,
								'label' => "$currency_name ( $currency_code )",
							);
						},
						$currencies
					),
				);
				$conditions[]         = $currencies_condition;
			}
		}
		return $conditions;
	}

	public static function check_yaycurrency_currency_condition( $result, $condition ) {
		if ( class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			$current_currency = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
			$currency_id      = isset( $current_currency['ID'] ) ? $current_currency['ID'] : 0;
			$condition_values = array_map(
				function( $item ) {
					return $item['value'];
				},
				$condition['value']
			);
			$in_list          = in_array( $currency_id, $condition_values );
			return 'in_list' === $condition['comparation'] ? $in_list : ! $in_list;
		}
		return false;
	}

}
