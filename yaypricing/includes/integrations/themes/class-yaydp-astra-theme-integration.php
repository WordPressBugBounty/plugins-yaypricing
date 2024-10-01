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

}
