<?php

/**
 * Handles the integration of Advanced Product Fields plugin with YayDP system.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\APF;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_APF_Intergration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'wapf' ) ) {
			return;
		}
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 100, 2 );
	}

	public function initialize_cart_item_price( $item_price, $cart_item ) {

		if ( ! class_exists( '\SW_WAPF\Includes\Classes\Fields' ) ) {
			return $item_price;
		}

		$quantity      = empty( $cart_item['quantity'] ) ? 1 : \wc_stock_amount( $cart_item['quantity'] );
		$options_total = 0;

		if ( empty( $cart_item['wapf'] ) ) {
			return $item_price;
		}

		foreach ( $cart_item['wapf'] as $field ) {
			if ( ! empty( $field['price'] ) ) {
				foreach ( $field['price'] as $price ) {

					if ( 0 === $price['value'] ) {
						continue;
					}

					$options_total = $options_total + \SW_WAPF\Includes\Classes\Fields::do_pricing( $price['value'], $quantity );

				}
			}
		}

		return $item_price + $options_total;
	}
}
