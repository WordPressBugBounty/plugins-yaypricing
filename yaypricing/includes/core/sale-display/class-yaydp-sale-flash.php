<?php
/**
 * Managing all things about the sale flash displaying.
 *
 * @since 2.4
 *
 * @package YayPricing\SaleDisplay
 */

namespace YAYDP\Core\Sale_Display;

use DOMDocument;

/**
 * Declare class
 */
class YAYDP_Sale_Flash {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		// Add sale tag.
		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'add_sale_tag' ), 100 );
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'single_product_image_thumbnail_html' ), 100, 2 );
		// Remove woocommerce sale flash.
		add_filter( 'woocommerce_sale_flash', array( $this, 'remove_sale_flash' ), 100, 3 );
		add_action( 'yaydp_custom_sale_tag', array( $this, 'add_sale_tag' ) );
	}

	/**
	 * Add sale tag
	 */
	public function add_sale_tag( $product = null ) {
		if ( empty( $product ) ) {
			global $product;
		}
		if ( empty( $product ) ) {
			return;
		}
		$sale_tag = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $product );
		if ( ! $sale_tag->can_display() ) {
			return;
		}
		$html = $sale_tag->get_content();
		echo wp_kses_post( $html );
	}

	/**
	 * Callback for woocommerce_single_product_image_thumbnail_html hook
	 *
	 * @param string $html HTML.
	 * @param int    $attachment_id Image id.
	 */
	public function single_product_image_thumbnail_html( $html, $attachment_id ) {
		global $product;
		if ( empty( $product ) ) {
			return $html;
		}
		$current_product = $product;
		if ( \yaydp_is_variable_product( $product ) ) {
			$variation = \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_variation_with_attachment_id( $product, $attachment_id );
			if ( ! empty( $variation ) ) {
				$current_product = $variation;
			}
		}
		$sale_tag = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $current_product );
		if ( ! $sale_tag->can_display() ) {
			return $html;
		}
		$sale_tag_content = $sale_tag->get_content();
		libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$fragment = $dom->createDocumentFragment();
		$fragment->appendXML( $sale_tag_content );
		if ( ! $fragment->hasChildNodes() ) {
			return $html;
		}
		if ( $dom->firstChild ) {
			$dom->firstChild->appendChild( $fragment );
		} else {
			$dom->appendChild( $fragment );
		}
		$html = $dom->saveHTML();
		return $html;
	}

	/**
	 * Callback for woocommerce_sale_flash hook
	 *
	 * @param string $wc_sale_flash_html HTML.
	 * @param
	 * @param \WC_Product $product Given product.
	 */
	public function remove_sale_flash( $wc_sale_flash_html, $post, $product ) {
		if ( empty( $product ) ) {
			return $wc_sale_flash_html;
		}
		$sale_tag         = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $product );
		$sale_tag_content = $sale_tag->get_content();
		if ( empty( $sale_tag_content ) ) {
			return $wc_sale_flash_html;
		}
		return '';
	}
}
