<?php
/**
 * Handles the integration of Astra theme with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Themes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Astra_Theme_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'after_setup_theme', array( $this, 'after_theme_setup' ) );
	}

	/**
	 * After theme setup function
	 */
	public function after_theme_setup() {
		if ( class_exists( 'Astra_Woocommerce' ) ) {
			add_filter( 'astra_addon_shop_cards_buttons_html', array( $this, 'remove_sale_flash' ), 100, 2 );

			/**
			 * Script for remove YayPricing data in sticky cart
			 */
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			/**
			 * Ensure cart subtotal and total reflect YayPricing discounts
			 */
			add_filter( 'woocommerce_cart_subtotal', array( $this, 'adjust_cart_subtotal' ), 100, 3 );
			add_filter( 'woocommerce_cart_total', array( $this, 'adjust_cart_total' ), 100, 1 );
			add_filter( 'woocommerce_cart_get_cart_contents_total', array( $this, 'adjust_cart_contents_total' ), 100, 1 );
		}
	}

	/**
	 * Removes the "Sale" flash from a product if it is sale by YayPricing.
	 *
	 * @param string      $sale_flash_html Current sale flash.
	 * @param \WC_Product $product Current product.
	 *
	 * @return string
	 */
	public function remove_sale_flash( $sale_flash_html, $product ) {

		if ( empty( $product ) ) {
			return $sale_flash_html;
		}
		$sale_tag         = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $product );
		$sale_tag_content = $sale_tag->get_content();
		if ( empty( $sale_tag_content ) ) {
			return $sale_flash_html;
		}

		$astra_instance = \Astra_Woocommerce::get_instance();
		return '' . $astra_instance->modern_add_to_cart();

	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'yaydp-integration-astra-theme',
			YAYDP_PLUGIN_URL . 'includes/integrations/themes/js/astra-theme-integration.js',
			array( 'jquery' ),
			YAYDP_VERSION,
			true
		);
	}

	/**
	 * Adjust cart subtotal to reflect YayPricing discounts
	 *
	 * @param string  $cart_subtotal Current cart subtotal HTML.
	 * @param boolean $compound Whether to include compound taxes.
	 * @param \WC_Cart $cart Cart object.
	 *
	 * @return string Modified cart subtotal HTML.
	 */
	public function adjust_cart_subtotal( $cart_subtotal, $compound, $cart ) {
		global $yaydp_cart;

		// If YayPricing cart is not initialized or has no discounts, return original subtotal
		if ( is_null( $yaydp_cart ) || ! $yaydp_cart->has_product_discounts() ) {
			return $cart_subtotal;
		}

		// Get the discounted subtotal from YayPricing
		$display_prices_including_tax = $cart->display_prices_including_tax();
		$yaydp_subtotal = $yaydp_cart->get_cart_subtotal( $display_prices_including_tax );

		// Calculate tax if needed
		if ( $compound ) {
			// For compound tax, include shipping and non-compound taxes
			$cart_contents_total = $yaydp_subtotal;
			$shipping_total = $cart->get_shipping_total();
			$taxes_total = $cart->get_taxes_total( false, false );
			$cart_subtotal = wc_price( $cart_contents_total + $shipping_total + $taxes_total );
		} elseif ( $display_prices_including_tax ) {
			// Include tax in subtotal
			$subtotal_tax = $cart->get_subtotal_tax();
			$cart_subtotal = wc_price( $yaydp_subtotal + $subtotal_tax );

			if ( $subtotal_tax > 0 && ! wc_prices_include_tax() ) {
				$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
			}
		} else {
			// Exclude tax from subtotal
			$cart_subtotal = wc_price( $yaydp_subtotal );

			$subtotal_tax = $cart->get_subtotal_tax();
			if ( $subtotal_tax > 0 && wc_prices_include_tax() ) {
				$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}
		}

		return $cart_subtotal;
	}

	/**
	 * Adjust cart total to reflect YayPricing discounts
	 *
	 * @param string $cart_total Current cart total HTML.
	 *
	 * @return string Modified cart total HTML.
	 */
	public function adjust_cart_total( $cart_total ) {
		global $yaydp_cart;

		// If YayPricing cart is not initialized or has no discounts, return original total
		if ( is_null( $yaydp_cart ) || ! $yaydp_cart->has_product_discounts() ) {
			return $cart_total;
		}

		// WooCommerce calculates total from cart items, which YayPricing already modifies
		// The total should already be correct, but we verify by checking if cart items have been modified
		$cart = WC()->cart;
		if ( empty( $cart ) ) {
			return $cart_total;
		}

		// Check if any cart items have YayPricing discounts applied
		$has_yaydp_discounts = false;
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['yaydp_custom_data']['price'] ) ) {
				$has_yaydp_discounts = true;
				break;
			}
		}

		// If discounts are applied, the total should already be correct from WooCommerce
		// But we ensure it's using the discounted prices by getting the total directly
		if ( $has_yaydp_discounts ) {
			// Get the total value (not formatted) to ensure it reflects discounts
			$total = $cart->get_total( 'edit' );
			$cart_total = wc_price( $total );
		}

		return $cart_total;
	}

	/**
	 * Adjust cart contents total to reflect YayPricing discounts
	 *
	 * @param float $cart_contents_total Current cart contents total.
	 *
	 * @return float Modified cart contents total.
	 */
	public function adjust_cart_contents_total( $cart_contents_total ) {
		global $yaydp_cart;

		// If YayPricing cart is not initialized or has no discounts, return original total
		if ( is_null( $yaydp_cart ) || ! $yaydp_cart->has_product_discounts() ) {
			return $cart_contents_total;
		}

		// Get the discounted subtotal from YayPricing (excluding tax)
		$yaydp_subtotal = $yaydp_cart->get_cart_subtotal( false );

		// The cart contents total should match the discounted subtotal (excluding tax)
		return $yaydp_subtotal;
	}

}
