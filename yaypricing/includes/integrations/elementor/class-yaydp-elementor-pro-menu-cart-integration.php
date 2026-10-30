<?php
/**
 * Handles the integration of Elementor Pro Menu Cart widget with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Elementor_Pro_Menu_Cart_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Whether a shopper-added (non gift) item was added to cart in this request.
	 *
	 * @var bool
	 */
	protected $has_shopper_added_item = false;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return;
		}
		add_action( 'woocommerce_add_to_cart', array( $this, 'track_shopper_added_item' ), 10, 6 );
		add_filter( 'elementor_pro/frontend/localize_settings', array( $this, 'remove_gift_only_added_to_cart_flag' ), PHP_INT_MAX );
	}

	/**
	 * Remember when a real (non gift) item is added to cart.
	 *
	 * Free gift items (BOGO / Buy X Get Y) are re-added through WC()->cart->add_to_cart() on every
	 * totals recalculation, which also fires `woocommerce_add_to_cart`.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id Product ID.
	 * @param int    $quantity Quantity.
	 * @param int    $variation_id Variation ID.
	 * @param array  $variation Variation attributes.
	 * @param array  $cart_item_data Cart item data.
	 */
	public function track_shopper_added_item( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( empty( $cart_item_data['is_extra'] ) ) {
			$this->has_shopper_added_item = true;
		}
	}

	/**
	 * Elementor Pro sets `productAddedToCart` whenever `woocommerce_add_to_cart` fires, and the Menu Cart
	 * then opens itself on page load. Drop the flag when only our gift items were re-added, so the cart
	 * does not pop open on every page while a gift is in the cart.
	 *
	 * @param array $settings Elementor Pro frontend settings.
	 *
	 * @return array
	 */
	public function remove_gift_only_added_to_cart_flag( $settings ) {
		if ( ! $this->has_shopper_added_item && isset( $settings['woocommerce']['productAddedToCart'] ) ) {
			unset( $settings['woocommerce']['productAddedToCart'] );
		}
		return $settings;
	}
}
