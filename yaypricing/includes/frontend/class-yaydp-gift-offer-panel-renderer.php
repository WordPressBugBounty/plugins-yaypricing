<?php
/**
 * Builds the gift panel row that follows an offer's lines on the cart page.
 *
 * One row per anchor line, one panel per rule anchored there: a header with the
 * title, the gifts in the cart and the gift subtotal.
 *
 * @package YayPricing\Frontend
 */

namespace YAYDP\Frontend;

use YAYDP\Helper\YAYDP_Gift_Group_Helper as Gift_Group;
use YAYDP\Frontend\YAYDP_Gift_Offer_Line_Renderer as Line_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Offer_Panel_Renderer class
 */
class YAYDP_Gift_Offer_Panel_Renderer {

	/**
	 * The panel row of an anchor group.
	 *
	 * @param array $group Anchor group from YAYDP_Gift_Offer_Group_Helper::get_anchors().
	 * @return string
	 */
	public static function render_row( $group ) {
		$panels = '';
		foreach ( $group['rule_ids'] as $rule_id ) {
			$panels .= self::render_panel( $rule_id );
		}
		if ( '' === $panels ) {
			return '';
		}

		$colspan   = max( 1, absint( apply_filters( 'yaydp_gift_panel_colspan', 6 ) ) );
		$is_orphan = '' === $group['anchor'];
		// An anchored panel starts at the thumbnail column: skip the remove
		// column. An orphan has no line to align with.
		$offset = $is_orphan ? 0 : min( $colspan - 1, absint( apply_filters( 'yaydp_gift_panel_offset_columns', 1 ) ) );
		$class  = 'yaydp-offer-panel-row' . ( $is_orphan ? ' yaydp-offer-orphan' : '' );
		$spacer = $offset > 0 ? sprintf( '<td class="yaydp-offer-panel-spacer" colspan="%d"></td>', $offset ) : '';

		return sprintf(
			'<tr class="%1$s">%2$s<td class="yaydp-offer-panel-cell" colspan="%3$d">%4$s</td></tr>',
			esc_attr( $class ),
			$spacer,
			$colspan - $offset,
			$panels
		);
	}

	/**
	 * The panel of one rule: header with the title, the gifts in the cart and
	 * the gift subtotal.
	 *
	 * @param string $rule_id Rule id.
	 * @return string
	 */
	private static function render_panel( $rule_id ) {
		$gifts = Gift_Group::get_rule_selections( $rule_id );
		if ( empty( $gifts ) ) {
			return '';
		}

		$regular = 0.0;
		$lines   = '';
		foreach ( $gifts as $gift ) {
			$regular += Line_Renderer::get_regular_total( $gift['product'], $gift['qty'] );
			$lines   .= Line_Renderer::render( $gift['product'], $gift['qty'] );
		}

		$html = '<div class="yaydp-offer-header">'
			. '<span class="yaydp-offer-heading">'
			. '<span class="yaydp-offer-icon" aria-hidden="true">&#127873;</span>'
			. '<span class="yaydp-offer-title">' . esc_html__( 'Free gifts', 'yaypricing' ) . '</span>'
			. '</span>'
			. '</div>';

		$html .= '<ul class="yaydp-offer-gifts">' . $lines . '</ul>';

		$html .= '<div class="yaydp-offer-subtotal">'
			. '<span class="yaydp-offer-subtotal-label">' . esc_html__( 'Gift subtotal', 'yaypricing' ) . '</span>'
			. '<span class="yaydp-offer-subtotal-price">' . ( $regular > 0 ? '<del>' . \wc_price( $regular ) . '</del> ' : '' ) . '<strong>' . \wc_price( 0 ) . '</strong></span>'
			. '</div>';

		return '<div class="yaydp-offer-panel" data-rule-id="' . esc_attr( $rule_id ) . '">' . $html . '</div>';
	}
}
