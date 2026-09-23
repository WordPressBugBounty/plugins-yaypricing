<?php
/**
 * Renders free gift cart rows on every PHP-rendered surface.
 *
 * Gift rows are real cart items flagged `is_extra` by YAYDP_Cart::publish().
 * Price column and subtotal stay owned by YAYDP_Product_Pricing_Manager; this
 * class only fills the gaps it leaves: thumbnail, permalink, name and quantity.
 *
 * @package YayPricing\Frontend
 */

namespace YAYDP\Frontend;

use YAYDP\Helper\YAYDP_Gift_Helper as Gift_Helper;
use YAYDP\Helper\YAYDP_Gift_Group_Helper as Gift_Group;
use YAYDP\Frontend\YAYDP_Gift_Row_Renderer as Gift_Row_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Product_Display class
 */
class YAYDP_Gift_Product_Display {

	/**
	 * Whether the gift assets have been enqueued already.
	 *
	 * @var bool
	 */
	private $assets_enqueued = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_cart_item_visible', array( $this, 'hide_grouped_gift_rows' ), 20, 3 );
		add_filter( 'woocommerce_widget_cart_item_visible', array( $this, 'hide_grouped_gift_rows' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_thumbnail', array( $this, 'render_gift_thumbnail' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_permalink', array( $this, 'render_gift_permalink' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'render_gift_name' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'render_gift_quantity' ), 20, 3 );

		if ( \yaydp_is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			// The mini cart can render on any page, after the cart is loaded, so
			// enqueue again while it renders its gift rows.
			add_action( 'woocommerce_before_mini_cart', array( $this, 'enqueue_assets' ) );
		}
	}

	/**
	 * Check whether the current output is the checkout order summary, where each
	 * gift product keeps its own row instead of being grouped.
	 *
	 * @return bool
	 */
	private static function is_checkout_summary() {
		return is_checkout() && ! is_cart();
	}

	/**
	 * Show one row per gift group and hide the rest.
	 *
	 * @param bool   $visible Whether the row renders.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return bool
	 */
	public function hide_grouped_gift_rows( $visible, $cart_item, $cart_item_key ) {
		if ( ! Gift_Helper::is_gift_item( $cart_item ) || self::is_checkout_summary() ) {
			return $visible;
		}
		return Gift_Group::is_group_leader( $cart_item_key );
	}

	/**
	 * Replace the product thumbnail with a gift icon.
	 *
	 * @param string $thumbnail Thumbnail HTML.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function render_gift_thumbnail( $thumbnail, $cart_item, $cart_item_key ) {
		if ( ! Gift_Helper::is_gift_item( $cart_item ) ) {
			return $thumbnail;
		}
		return '<span class="yaydp-gift-icon">&#127873;</span>';
	}

	/**
	 * Stop the gift thumbnail and name from linking to the product.
	 *
	 * @param string $permalink Permalink.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function render_gift_permalink( $permalink, $cart_item, $cart_item_key ) {
		if ( ! Gift_Helper::is_gift_item( $cart_item ) ) {
			return $permalink;
		}
		return '#';
	}

	/**
	 * Prepend the FREE GIFT badge and append the rule name.
	 *
	 * @param string $name Product name HTML.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function render_gift_name( $name, $cart_item, $cart_item_key ) {
		if ( ! Gift_Helper::is_gift_item( $cart_item ) ) {
			return $name;
		}

		if ( self::is_checkout_summary() ) {
			return Gift_Row_Renderer::render_checkout_name( $name );
		}

		return Gift_Row_Renderer::render_name( $cart_item, $cart_item_key );
	}

	/**
	 * Show a read-only quantity instead of the stepper.
	 *
	 * @param string $quantity Quantity HTML.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $cart_item Cart item.
	 * @return string
	 */
	public function render_gift_quantity( $quantity, $cart_item_key, $cart_item ) {
		if ( ! Gift_Helper::is_gift_item( $cart_item ) ) {
			return $quantity;
		}
		return '<span class="yaydp-gift-qty">' . esc_html( Gift_Group::get_group_quantity( $cart_item_key ) ) . '</span>';
	}

	/**
	 * Load the gift styles on cart and checkout, and anywhere else only when the
	 * cart holds a gift or a running rule can still hand one out, so the mini
	 * cart renders its gift rows styled - including the row an AJAX fragment
	 * refresh adds after the page has loaded.
	 */
	public function enqueue_assets() {
		if ( $this->assets_enqueued ) {
			return;
		}

		if ( ! is_cart() && ! is_checkout() && ! Gift_Helper::cart_has_gift() && ! Gift_Helper::has_running_gift_rule() ) {
			return;
		}

		$this->assets_enqueued = true;

		wp_enqueue_style( 'yaydp-gift-product', YAYDP_PLUGIN_URL . 'assets/css/gift-product.css', array(), YAYDP_VERSION );
	}
}

new YAYDP_Gift_Product_Display();
