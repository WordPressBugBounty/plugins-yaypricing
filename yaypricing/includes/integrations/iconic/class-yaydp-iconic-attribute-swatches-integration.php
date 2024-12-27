<?php
/**
 * Handles the integration of Iconic Attribute Swatches
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Iconic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Iconic_Attribute_Swatches_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'Iconic_Woo_Attribute_Swatches' ) ) {
			return;
		}
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 100, 2 );
	}

	public function initialize_cart_item_price( $price, $cart_item ) {

		if ( ! class_exists( 'Iconic_WAS_Fees' ) ) {
			return $price;
		}

		if ( empty( $cart_item['variation'] ) || ! empty( $cart_item['iconic_was_fee'] ) ) {
			return $price;
		}

		foreach ( $cart_item['variation'] as $attribute => $attribute_value ) {
			if ( empty( $attribute_value ) ) {
				continue;
			}

			$attribute = str_replace( 'attribute_', '', $attribute );
			$price    += \Iconic_WAS_Fees::get_fees( $cart_item['product_id'], $attribute, $attribute_value );
		}

		return $price;
	}
}
