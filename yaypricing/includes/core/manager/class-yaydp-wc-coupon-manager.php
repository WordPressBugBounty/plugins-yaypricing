<?php
/**
 * This class is responsible for managing the cart discount in the YAYDP system.
 *
 * @package YayPricing\Classes
 * @since 3.4.2
 */

namespace YAYDP\Core\Manager;

use YAYDP\Settings\YAYDP_General_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_WC_Coupon_Manager {

	use \YAYDP\Traits\YAYDP_Singleton;

	const INVALID_INDIVIDUAL_USE = 1000;

	protected function __construct() {
		if ( YAYDP_General_Settings::get_instance()->is_sync_with_coupon_individual_use_only() ) {
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'check_valid_individual_use' ), 10, 2 );
		}
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'coupon_check_valid_cart_include_sale_items' ), 10, 2 );
		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'coupon_check_valid_product_on_sale' ), 10, 3 );
		if ( \YAYDP\Settings\YAYDP_Cart_Discount_Settings::get_instance()->can_use_together_with_single_use_coupon() ) {
			add_filter( 'woocommerce_apply_individual_use_coupon', array( $this, 'keep_yaydp_coupon_when_other_is_single_use' ), 10, 3 );
			add_filter( 'woocommerce_apply_with_individual_use_coupon', array( $this, 'allow_add_yaydp_coupon_when_other_is_single_use' ), 10, 3 );
		}
	}

	public function check_valid_individual_use( $check, $coupon ) {

		if ( ! $coupon->get_individual_use() ) {
			return $check;
		}

		global $yaydp_cart;

		if ( empty( $yaydp_cart ) ) {
			return $check;
		}

		if ( $yaydp_cart->has_product_discounts() ) {
			throw new \Exception( $this->get_error_message( self::INVALID_INDIVIDUAL_USE ), self::INVALID_INDIVIDUAL_USE );
		}

		return $check;
	}

	public function coupon_check_valid_cart_include_sale_items( $check, $coupon ) {

		if ( ! $coupon->get_exclude_sale_items() ) {
			return $check;
		}

		global $yaydp_cart;

		if ( empty( $yaydp_cart ) ) {
			return $check;
		}

		if ( $coupon->is_type( \wc_get_product_coupon_types() ) ) {
			return $check;
		}

		if ( ! $coupon->get_exclude_sale_items() ) {
			return $check;
		}

		if ( $yaydp_cart->has_product_discounts() ) {
			throw new \Exception( __( 'Sorry, this coupon is not valid for sale items.', 'woocommerce' ), \WC_Coupon::E_WC_COUPON_NOT_VALID_SALE_ITEMS );
		}

		return $check;

	}

	public function coupon_check_valid_product_on_sale( $check, $product, $coupon ) {

		if ( ! $coupon->is_type( \wc_get_product_coupon_types() ) || ! is_a( $product, \WC_Product::class ) ) {
			return $check;
		}

		if ( ! $coupon->get_exclude_sale_items() ) {
			return $check;
		}

		global $yaydp_cart;

		if ( empty( $yaydp_cart ) ) {
			return $check;
		}

		$product_id = $product->get_id();

		foreach ( $yaydp_cart->get_items() as $cart_item ) {
			if ( ! $cart_item->can_modify() ) {
				continue;
			}
			$cart_product = $cart_item->get_product();
			if ( ! is_a( $cart_product, \WC_Product::class ) ) {
				continue;
			}
			$cart_product_id = $cart_product->get_id();
			if ( $product_id == $cart_product_id ) {
				return false;
			}
		}

		return $check;

	}

	public function get_error_message( $error_code ) {
		if ( self::INVALID_INDIVIDUAL_USE == $error_code ) {
			return __( 'Coupon cannot be used in combination with other discounts.', 'yaypricing' );
		}

		return __( 'Coupon is not valid.', 'woocommerce' );
	}

	public function keep_yaydp_coupon_when_other_is_single_use( $result, $the_coupon, $applied_coupons ) {
		foreach ( $applied_coupons as $coupon_code ) {
			if ( yaydp_is_coupon( $coupon_code ) ) {
				$result[] = $coupon_code;
			}
		}
		return $result;
	}

	public function allow_add_yaydp_coupon_when_other_is_single_use( $check, $the_coupon, $coupon ){

		if ( $the_coupon->get_individual_use() && yaydp_is_coupon( $coupon->get_code() ) ) {
			return true;
		}

		if ( $coupon->get_individual_use() && yaydp_is_coupon( $the_coupon->get_code() ) ) {
			return true;
		}

		return $check;
		
	}

}
