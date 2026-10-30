<?php
/**
 * The Template for displaying cart item price ( only for extra item )
 *
 * @since 2.4
 *
 * @package YayPricing\Templates\CartItemPrice
 *
 * @param $tooltips
 */

defined( 'ABSPATH' ) || exit;

$free_text   = apply_filters( 'yaydp_extra_item_text', __( 'Free', 'yaypricing' ) );
$badge_class = \YAYDP\Frontend\YAYDP_Gift_Row_Renderer::uses_legacy_classes() ? 'yaydp-gift-price yaydp-free-item-badge' : 'yaydp-gift-price';

?>
<div class="yaydp-cart-item-price">
	<span class="<?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $free_text ); ?></span>
	<?php echo \yaydp_render_tooltips( $tooltips ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output, tooltip content is wp_kses_post'ed. ?>
</div>