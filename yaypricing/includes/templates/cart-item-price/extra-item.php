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

$free_text = apply_filters( 'yaydp_extra_item_text', __( 'Free', 'yaypricing' ) );

?>
<div class="yaydp-cart-item-price">
	<span class="yaydp-gift-price"><?php echo esc_html( $free_text ); ?></span>
	<?php echo \yaydp_render_tooltips( $tooltips ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output, tooltip content is wp_kses_post'ed. ?>
</div>
