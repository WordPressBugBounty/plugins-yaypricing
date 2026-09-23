<?php
/**
 * Builds the markup for a grouped free gift cart row.
 *
 * One row stands for one rule: the rule name is the title and every gift
 * product granted by that rule is listed beneath it.
 *
 * @package YayPricing\Frontend
 */

namespace YAYDP\Frontend;

use YAYDP\Helper\YAYDP_Gift_Helper as Gift_Helper;
use YAYDP\Helper\YAYDP_Gift_Group_Helper as Gift_Group;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Row_Renderer class
 */
class YAYDP_Gift_Row_Renderer {

	/**
	 * Render the whole gift row body: badge, rule name and selections.
	 *
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function render_name( $cart_item, $cart_item_key ) {
		$rule       = Gift_Helper::get_gift_rule( $cart_item );
		$badge_text = apply_filters( 'yaydp_gift_badge_text', __( 'Free gift', 'yaypricing' ) );
		$rule_name  = is_null( $rule ) ? '' : $rule->get_name();
		if ( empty( $rule_name ) ) {
			$rule_name = __( 'Free gift', 'yaypricing' );
		}

		$html  = self::get_heading( $rule_name, $badge_text );
		$html .= self::get_selections_summary( $cart_item_key );

		return '<div class="yaydp-gift-item">' . $html . '</div>';
	}

	/**
	 * Badge plus rule name, the top line of every gift row.
	 *
	 * @param string $rule_name Rule name.
	 * @param string $badge_text Badge text.
	 * @return string
	 */
	private static function get_heading( $rule_name, $badge_text ) {
		return '<span class="yaydp-gift-badge">' . esc_html( $badge_text ) . '</span>'
			. '<span class="yaydp-gift-title">' . esc_html( $rule_name ) . '</span>';
	}

	/**
	 * Render a checkout summary row: a compact badge in front of the product name.
	 *
	 * @param string $name Product name HTML.
	 * @return string
	 */
	public static function render_checkout_name( $name ) {
		$badge_text = apply_filters( 'yaydp_gift_badge_text', __( 'Free gift', 'yaypricing' ) );

		return '<span class="yaydp-gift-badge-small">' . esc_html( $badge_text ) . '</span> ' . $name;
	}

	/**
	 * List the products making up this gift, one line per product.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	private static function get_selections_summary( $cart_item_key ) {
		$selections = Gift_Group::get_group_selections( $cart_item_key );
		if ( empty( $selections ) ) {
			return self::get_empty_selections_summary();
		}

		$html = '<ul class="yaydp-gift-selections">';
		foreach ( $selections as $selection ) {
			$product = $selection['product'];
			$name    = $product->get_name();
			if ( \yaydp_is_variation_product( $product ) ) {
				$variation = \wc_get_formatted_variation( $product, true );
				if ( ! empty( $variation ) ) {
					$name .= ' - ' . $variation;
				}
			}
			$html .= '<li class="yaydp-gift-selection-item">'
				. '<span class="yaydp-gift-selection-qty">' . esc_html( $selection['qty'] ) . ' &times;</span>'
				. '<span class="yaydp-gift-selection-name">' . esc_html( $name ) . '</span>'
				. '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * The placeholder shown when a gift row holds no products.
	 *
	 * @return string
	 */
	private static function get_empty_selections_summary() {
		return '<div class="yaydp-gift-selections yaydp-gift-none"><span class="yaydp-gift-none-text">'
			. esc_html__( 'No gift selected', 'yaypricing' ) . '</span></div>';
	}
}
