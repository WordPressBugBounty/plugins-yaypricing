<?php
/**
 * Handles the integration of Min Max Quantity Step Control plugin with YayPricing
 * Disables min/max quantity controls for extra products
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Minmaxcontrol;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAYDP_Min_Max_Quantity_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
		if ( ! class_exists( 'WC_MMQ' ) ) {
			return;
		}

		$this->init_hooks();
	}

	private function init_hooks() {
		add_filter( 'wcmmq_single_product_min_max_condition', array( $this, 'disable_min_max_for_extra_products' ), 10, 2 );
		add_filter( 'wcmmq_add_validation_check', array( $this, 'disable_min_max_validation_for_extra_products' ), 10, 2 );
		add_filter( 'wcmmq_cart_validation_check', array( $this, 'disable_cart_validation_for_extra_products' ), 10, 2 );

		add_filter( 'woocommerce_quantity_input_min', array( $this, 'override_min_quantity_for_extra_products' ), 10, 2 );
		add_filter( 'woocommerce_quantity_input_max', array( $this, 'override_max_quantity_for_extra_products' ), 10, 2 );
		add_filter( 'woocommerce_quantity_input_step', array( $this, 'override_step_quantity_for_extra_products' ), 10, 2 );

		add_filter( 'woocommerce_quantity_input_args', array( $this, 'disable_min_max_for_extra_products' ), 10, 2 );

	}

	/**
	 * Check if a product is an extra product
	 * 
	 * @param \WC_Product $product The product object
	 * @param array $cart_item Cart item data (optional)
	 * @return bool True if it's an extra product
	 */
	private function is_extra_product( $product, $cart_item = null ) {
		if ( $cart_item && isset( $cart_item['is_extra'] ) && $cart_item['is_extra'] ) {
			return true;
		}
		
		if ( $product && $product->get_id() ) {
			$is_extra = get_post_meta( $product->get_id(), '_is_extra_product', true );
			if ( $is_extra ) {
				return true;
			}
		}
		
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item_data ) {
				if ( $cart_item_data['product_id'] == $product->get_id() && 
					isset( $cart_item_data['is_extra'] ) && $cart_item_data['is_extra'] ) {
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Disable min/max quantity controls for extra products
	 * 
	 * @param array $args Quantity input arguments
	 * @param \WC_Product $product The product object
	 * @return array Modified arguments
	 */
	public function disable_min_max_for_extra_products( $args, $product ) {
		if ( $this->is_extra_product( $product ) ) {
			$args['min_value'] = 0;
			$args['min_qty'] = 0;
			$args['max_value'] = '';
			$args['max_qty'] = '';
			$args['step'] = 1;
			
			if ( isset( $args['classes'] ) && is_array( $args['classes'] ) ) {
				$args['classes'] = array_filter( $args['classes'], function( $class ) {
					return $class !== 'wcmmq-qty-input-box';
				});
			}
		}
		
		return $args;
	}

	/**
	 * Disable min/max validation for extra products
	 * 
	 * @param bool $validation_check Current validation status
	 * @param int $product_id Product ID
	 * @return bool Modified validation status
	 */
	public function disable_min_max_validation_for_extra_products( $validation_check, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $this->is_extra_product( $product ) ) {
			return false;
		}
		return $validation_check;
	}

	/**
	 * Disable cart validation for extra products
	 * 
	 * @param bool $validation_check Current validation status
	 * @param array $values Cart values
	 * @return bool Modified validation status
	 */
	public function disable_cart_validation_for_extra_products( $validation_check, $values ) {
		if ( isset( $values['is_extra'] ) && $values['is_extra'] ) {
			return false;
		}
		return $validation_check;
	}

	/**
	 * Override min quantity for extra products
	 * 
	 * @param int $min_quantity Current min quantity
	 * @param \WC_Product $product The product object
	 * @return int Modified min quantity
	 */
	public function override_min_quantity_for_extra_products( $min_quantity, $product ) {
		if ( $this->is_extra_product( $product ) ) {
			return 0;
		}
		return $min_quantity;
	}

	/**
	 * Override max quantity for extra products
	 * 
	 * @param int $max_quantity Current max quantity
	 * @param \WC_Product $product The product object
	 * @return int Modified max quantity
	 */
	public function override_max_quantity_for_extra_products( $max_quantity, $product ) {
		if ( $this->is_extra_product( $product ) ) {
			return '';
		}
		return $max_quantity;
	}

	/**
	 * Override step quantity for extra products
	 * 
	 * @param int $step_quantity Current step quantity
	 * @param \WC_Product $product The product object
	 * @return int Modified step quantity
	 */
	public function override_step_quantity_for_extra_products( $step_quantity, $product ) {
		if ( $this->is_extra_product( $product ) ) {
			return 1;
		}
		return $step_quantity;
	}
}