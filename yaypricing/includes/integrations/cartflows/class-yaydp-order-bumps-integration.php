<?php
/**
 * Handles the integration of YayCurrency plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\CartFlows;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Order_Bumps_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'Cartflows_Pro_Loader' ) ) {
			return;
		}
		add_filter( 'cartflows_single_order_bump_custom_price', array( $this, 'change_custom_price' ), 100, 2 );
	}

	public function change_custom_price( $price, $product ) {

		if ( empty( $product ) || ! ( $product instanceof \WC_Product ) ) {
			return $price;
		}

		$test_cart                   = new \YAYDP\Core\YAYDP_Cart(
			array(
				'data'         => $product,
				'quantity'     => 1,
				'custom_price' => $price,
				'key'          => 'check_product',
			)
		);
		$product_pricing_adjustments = new \YAYDP\Core\Adjustments\YAYDP_Product_Pricing_Adjustments( $test_cart );
		$product_pricing_adjustments->do_stuff();

		$items = $test_cart->get_items();

		foreach ( $items as $item ) {
			if ( 'check_product' === $item->get_key() ) {
				return $item->get_price();
			}
		}

		return $price;

	}

}
