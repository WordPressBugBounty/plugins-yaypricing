<?php
/**
 * Builds one gift line of the grouped gift panel on the cart page.
 *
 * @package YayPricing\Frontend
 */

namespace YAYDP\Frontend;

use YAYDP\Frontend\YAYDP_Gift_Row_Renderer as Gift_Row_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Offer_Line_Renderer class
 */
class YAYDP_Gift_Offer_Line_Renderer {

	/**
	 * Regular price of a gift line, as displayed, for the struck-through price.
	 *
	 * @param \WC_Product $product Gift product.
	 * @param int         $qty Quantity.
	 * @return float
	 */
	public static function get_regular_total( $product, $qty ) {
		// The gift row's product object is zero-priced by the pricing engine,
		// so read the catalogue price from a fresh copy.
		$catalogue = \wc_get_product( $product->get_id() );
		$regular   = $catalogue ? (float) $catalogue->get_regular_price() : 0.0;
		if ( $regular <= 0 ) {
			return 0.0;
		}
		return (float) \wc_get_price_to_display(
			$catalogue,
			array(
				'price' => $regular,
				'qty'   => $qty,
			)
		);
	}

	/**
	 * Thumbnail, name, quantity and ~~price~~ Free.
	 *
	 * @param \WC_Product $product Gift product.
	 * @param int         $qty Quantity.
	 * @return string
	 */
	public static function render( $product, $qty ) {
		$name    = Gift_Row_Renderer::get_display_name( $product );
		$regular = self::get_regular_total( $product, $qty );

		$price = '';
		if ( $regular > 0 ) {
			$price .= '<del>' . \wc_price( $regular ) . '</del> ';
		}
		$price .= '<span class="yaydp-offer-gift-free">'
			. esc_html( apply_filters( 'yaydp_extra_item_text', __( 'Free', 'yaypricing' ) ) ) . '</span>';

		/* translators: %d: gift quantity. */
		$qty_text = sprintf( __( 'Quantity: %d', 'yaypricing' ), $qty );

		$name_html = esc_html( $name );
		if ( $product->is_visible() ) {
			$name_html = '<a href="' . esc_url( $product->get_permalink() ) . '">' . $name_html . '</a>';
		}

		return '<li class="yaydp-offer-gift">'
			. '<span class="yaydp-offer-gift-thumb">' . $product->get_image( 'woocommerce_gallery_thumbnail' ) . '</span>'
			. '<span class="yaydp-offer-gift-info">'
			. '<span class="yaydp-offer-gift-name">' . $name_html . '</span>'
			. '<span class="yaydp-offer-gift-qty">' . esc_html( $qty_text ) . '</span>'
			. '</span>'
			. '<span class="yaydp-offer-gift-price">' . wp_kses_post( $price ) . '</span>'
			. '</li>';
	}
}
