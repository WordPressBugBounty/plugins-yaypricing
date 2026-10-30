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
	 * Gift box icon shared by every gift surface. Drawn in currentColor.
	 */
	const GIFT_ICON = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M20 7h-2.3c.2-.4.3-.9.3-1.4C18 4 16.7 2.8 15 2.8c-1.2 0-2.3.7-3 1.8-.7-1.1-1.8-1.8-3-1.8C7.3 2.8 6 4 6 5.6c0 .5.1 1 .3 1.4H4c-.6 0-1 .4-1 1v3.5h1V20c0 .6.4 1 1 1h14c.6 0 1-.4 1-1v-8.5h1V8c0-.6-.4-1-1-1zM15 4.3c.9 0 1.5.6 1.5 1.3S15.9 7 15 7h-2.4c.2-1.5 1.2-2.7 2.4-2.7zM7.5 5.6c0-.7.6-1.3 1.5-1.3 1.2 0 2.2 1.2 2.4 2.7H9c-.9 0-1.5-.6-1.5-1.4zM4.5 8.5h6.7v2H4.5v-2zm1 3h5.7v8H5.5v-8zm13 8h-5.7v-8h5.7v8zm1-9h-6.7v-2h6.7v2z"></path></svg>';

	/**
	 * Whether gift markup carries the legacy yaydp-free-item-badge class that
	 * theme styles target. The classic mini cart turns them off while it
	 * renders separate gift rows, which predate them.
	 *
	 * @var bool
	 */
	private static $legacy_classes = true;

	/**
	 * Stop adding the legacy classes, until enable_legacy_classes().
	 */
	public static function disable_legacy_classes() {
		self::$legacy_classes = false;
	}

	/**
	 * Add the legacy classes again.
	 */
	public static function enable_legacy_classes() {
		self::$legacy_classes = true;
	}

	/**
	 * Check whether gift markup should carry the legacy classes.
	 *
	 * @return bool
	 */
	public static function uses_legacy_classes() {
		return self::$legacy_classes;
	}

	/**
	 * The gift icon that stands in for a gift row's product thumbnail.
	 *
	 * @return string
	 */
	public static function get_thumbnail_icon() {
		return '<span class="yaydp-gift-icon" aria-hidden="true">' . self::GIFT_ICON . '</span>';
	}

	/**
	 * Render the whole gift row body: badge, rule name and selections.
	 *
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function render_name( $cart_item, $cart_item_key ) {
		$rule      = Gift_Helper::get_gift_rule( $cart_item );
		$rule_name = is_null( $rule ) ? '' : $rule->get_name();
		if ( empty( $rule_name ) ) {
			$rule_name = __( 'Free gift', 'yaypricing' );
		}

		$html  = self::get_heading( $rule_name );
		$html .= self::get_selections_summary( $cart_item_key );

		return '<div class="yaydp-gift-item">' . $html . '</div>';
	}

	/**
	 * Render a gift row that stands for one gift product only: the product's
	 * own name.
	 *
	 * @param string $name Product name HTML.
	 * @return string
	 */
	public static function render_single_name( $name ) {
		return '<div class="yaydp-gift-item"><span class="yaydp-gift-title">' . $name . '</span></div>';
	}

	/**
	 * Rule name, the top line of every gift row.
	 *
	 * @param string $rule_name Rule name.
	 * @return string
	 */
	private static function get_heading( $rule_name ) {
		return '<span class="yaydp-gift-title">' . esc_html( $rule_name ) . '</span>';
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
			$name    = self::get_display_name( $product );
			$html   .= '<li class="yaydp-gift-selection-item">'
				. '<span class="yaydp-gift-selection-image">' . $product->get_image( 'woocommerce_gallery_thumbnail' ) . '</span>'
				. '<span class="yaydp-gift-selection-qty">' . esc_html( $selection['qty'] ) . ' &times;</span>'
				. '<span class="yaydp-gift-selection-name">' . esc_html( $name ) . '</span>'
				. '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Product name, with the variation attributes of a variation appended.
	 *
	 * @param \WC_Product $product Product.
	 * @return string Plain text.
	 */
	public static function get_display_name( $product ) {
		$name = $product->get_name();
		if ( \yaydp_is_variation_product( $product ) ) {
			$variation = \wc_get_formatted_variation( $product, true );
			if ( ! empty( $variation ) ) {
				$name .= ' - ' . $variation;
			}
		}
		return $name;
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
