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
		if ( class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			add_filter( 'YayCurrency/GetCouponAmount', array( __CLASS__, 'round_converted_coupon_amount' ), 10, 3 );
			if ( ! self::is_disable_convert() ) {
				add_filter( 'yaydp_converted_price', array( __CLASS__, 'convert_price' ) );
				add_filter( 'yaydp_reversed_price', array( __CLASS__, 'reverse_price' ), 10, 2 );
				add_filter( 'yaydp_product_fixed_price', array( __CLASS__, 'get_product_fixed_price' ), 10, 2 );
				add_filter( 'yaydp_cart_discount_subtotal', array( __CLASS__, 'get_displayed_cart_subtotal' ), 10, 2 );
			}
			add_action( 'yaydp_before_set_cart_item_price', array( __CLASS__, 'before_set_cart_item_price' ), 10, 2 );
			add_action( 'yaydp_remove_3rd_currency_format', array( __CLASS__, 'remove_currency_formmat' ), 10 );
			add_filter( 'yaydp_checkout_coupon_fee_html', array( __CLASS__, 'get_checkout_coupon_fee_html' ), 10, 2 );
			add_action( 'yaydp_register_conditions', array( $this, 'register_conditions' ) );
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
	 * Snap the converted amount of our coupons to the precision of the current currency.
	 *
	 * Our coupons hold an amount in the store currency, so converting it back for the cart goes
	 * through a division and a multiplication and lands a few billionths beside the intended value.
	 * WooCommerce hands out the leftover of a fixed cart discount as a whole cent per item
	 * ( see WC_Discounts::apply_coupon_remainder ), so such a residue becomes a visible unit of
	 * discount - a full 1 in a currency displayed without decimals.
	 *
	 * @param float      $converted_amount Coupon amount converted to the current currency.
	 * @param \WC_Coupon $coupon Given coupon.
	 * @param array      $apply_currency Current currency.
	 *
	 * @return float
	 */
	public static function round_converted_coupon_amount( $converted_amount, $coupon, $apply_currency ) {
		if ( ! function_exists( '\yaydp_is_coupon' ) || ! $coupon instanceof \WC_Coupon ) {
			return $converted_amount;
		}
		if ( ! \yaydp_is_coupon( $coupon->get_code() ) ) {
			return $converted_amount;
		}
		return round( floatval( $converted_amount ), \wc_get_price_decimals() );
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
	 * A price known to be in a given currency ( e.g. one stored while the customer shopped in it ) is
	 * reversed from that currency, whatever the customer is shopping in now or checks out in.
	 *
	 * @param float  $price Converted price.
	 * @param string $currency_code Currency the price is in, the current currency when empty.
	 *
	 * @return float The original price as a float value
	 */
	public static function reverse_price( $price, $currency_code = '' ) {
		if ( class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			$from_currency = '' !== $currency_code ? \Yay_Currency\Helpers\YayCurrencyHelper::get_currency_by_currency_code( $currency_code ) : false;
			if ( ! empty( $from_currency ) ) {
				return \Yay_Currency\Helpers\YayCurrencyHelper::reverse_calculate_price_by_currency( $price, $from_currency );
			}
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

	/**
	 * Base cart discounts on the subtotal the customer sees in the current currency.
	 *
	 * YayCurrency converts each item price and then rounds it ( e.g. INR at rate 89 rounded up to
	 * the next 1000: $280 shows as 25,000 ). A 10% discount taken from the store currency
	 * subtotal converts to 10% of the unrounded 24,920 = 2,492 instead of 2,500. Returning the
	 * displayed prices, reversed to the store currency, keeps the discount at 10% of what is shown.
	 *
	 * @param float                  $subtotal Cart subtotal in the store currency.
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 *
	 * @return float
	 */
	public static function get_displayed_cart_subtotal( $subtotal, $cart ) {
		$apply_currency = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
		if ( empty( $apply_currency['currency'] ) || \get_option( 'woocommerce_currency' ) === $apply_currency['currency'] ) {
			return $subtotal;
		}
		if ( \Yay_Currency\Helpers\YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency ) ) {
			return $subtotal;
		}

		$displayed_subtotal  = 0;
		$display_include_tax = ! empty( \WC()->cart ) && \WC()->cart->display_prices_including_tax();
		foreach ( $cart->get_items() as $item ) {
			if ( $item->is_extra() ) {
				continue;
			}
			$item_quantity = $item->get_quantity();
			$item_price    = self::get_displayed_price( $item->get_effective_price(), $apply_currency );
			if ( $display_include_tax ) {
				$displayed_subtotal += wc_get_price_including_tax(
					$item->get_product(),
					array(
						'qty'   => $item_quantity,
						'price' => $item_price,
					)
				);
			} else {
				$displayed_subtotal += $item_quantity * $item_price;
			}
		}
		return $displayed_subtotal;
	}

	/**
	 * Store currency price that converts exactly to the price displayed in the given currency.
	 *
	 * @param float $price Price in the store currency.
	 * @param array $apply_currency Current currency.
	 *
	 * @return float
	 */
	private static function get_displayed_price( $price, $apply_currency ) {
		$converted = \Yay_Currency\Helpers\YayCurrencyHelper::calculate_price_by_currency( $price, true, $apply_currency );
		// Fixed prices come in reversed ( fixed / rate ): drop the float residue before rounding up.
		$converted = round( $converted, 6 );
		$converted = \Yay_Currency\Helpers\YayCurrencyHelper::round_price_by_currency( $converted, $apply_currency );
		return \Yay_Currency\Helpers\YayCurrencyHelper::reverse_calculate_price_by_currency( $converted, $apply_currency );
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

	/**
	 * Register this integration's condition types.
	 *
	 * @param \YAYDP\Condition\YAYDP_Condition_Registry $registry Registry.
	 */
	public function register_conditions( $registry ) {
		$registry->register( new YAYDP_YayCurrency_Currency_Condition() );
	}
}
