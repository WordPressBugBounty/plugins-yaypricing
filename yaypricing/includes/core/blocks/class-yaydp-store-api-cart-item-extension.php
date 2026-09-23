<?php
/**
 * Exposes YayPricing per-line-item data on the WooCommerce Store API so the Cart,
 * Checkout and Mini-Cart blocks can render what the classic cart templates render.
 *
 * Adds `extensions.yaypricing.tooltips` to every `cart/items[]` entry: the rendered
 * (variables replaced, translated) tooltip HTML of each rule that modified the item.
 *
 * @package YayPricing\Blocks
 *
 * @since 3.5.8
 */

namespace YAYDP\Core\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Store_Api_Cart_Item_Extension {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Namespace under `extensions` in the Store API response.
	 */
	const NAMESPACE_KEY = 'yaypricing';

	/**
	 * Constructor.
	 * WooCommerce Blocks and this plugin both bootstrap on `plugins_loaded` in no
	 * guaranteed order, so register now if Blocks already announced itself.
	 */
	private function __construct() {
		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->register();
			return;
		}
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register' ) );
	}

	/**
	 * Register the `cart-item` endpoint extension.
	 */
	public function register() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}
		\woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => 'cart-item',
				'namespace'       => self::NAMESPACE_KEY,
				'schema_type'     => ARRAY_A,
				'schema_callback' => array( $this, 'get_item_schema' ),
				'data_callback'   => array( $this, 'get_item_data' ),
			)
		);
	}

	/**
	 * JSON schema of the extension payload.
	 */
	public function get_item_schema() {
		return array(
			'tooltips' => array(
				'description' => __( 'Rendered YayPricing tooltip HTML, one entry per rule applied to the item.', 'yaypricing' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Extension payload for one cart item.
	 * Totals are already calculated by the time the Store API serialises the cart, so
	 * the item carries the `modifiers` YayPricing stored during that pass; no rule
	 * evaluation happens here. Any failure degrades to "no tooltip" rather than a
	 * broken cart response.
	 *
	 * @param array $cart_item WooCommerce cart item.
	 */
	public function get_item_data( $cart_item ) {
		$tooltips = array();
		if ( ! empty( $cart_item['data'] ) && ! empty( $cart_item['modifiers'] ) ) {
			try {
				$item     = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );
				$tooltips = array_values( array_map( 'wp_kses_post', $item->get_available_tooltip_contents() ) );
			} catch ( \Throwable $error ) {
				$tooltips = array();
				if ( function_exists( 'wc_get_logger' ) ) {
					\wc_get_logger()->error( 'YayPricing cart item tooltip: ' . $error->getMessage(), array( 'source' => 'yaypricing' ) );
				}
			}
		}
		return array(
			'tooltips' => $tooltips,
		);
	}
}
