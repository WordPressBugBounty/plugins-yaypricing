<?php
/**
 * Integration: WPClever Fly Cart (woo-fly-cart)
 * Ensures YayPricing discounted prices are shown in the mini cart and
 * cart totals are recalculated on add-to-cart before fragments render.
 */

namespace YAYDP\Integrations\WPClever;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAYDP_WPC_Fly_Cart_Integration {

	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
		if ( ! function_exists( 'woofc_init' ) ) {
			return;
		}
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'recalculate_before_fragments' ), 1 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'recalculate_before_fragments' ), 1 );
	}

	public function recalculate_before_fragments( $fragments ) {
		if ( function_exists( 'WC' ) && \WC()->cart ) {
			\WC()->cart->calculate_totals();
		}

		return $fragments;
	}
}
