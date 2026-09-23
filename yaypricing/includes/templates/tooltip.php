<?php
/**
 * The Template for a rule tooltip icon + popover, shared by cart item prices,
 * cart discount coupon rows and checkout fee rows. Render it through
 * yaydp_render_tooltips(). Markup mirrors window.yaydpTooltip.build() in
 * assets/js/tooltip.js, which renders the same tooltip inside the WooCommerce
 * Cart / Checkout / Mini-Cart blocks.
 *
 * @since 3.5.8
 *
 * @package YayPricing\Templates
 *
 * @param \YAYDP\Abstracts\YAYDP_Tooltip[] $tooltips Enabled tooltips to show.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $tooltips ) ) {
	return;
}

// uniqid() rather than wp_unique_id(): AJAX-refreshed fragments (mini-cart) would restart
// that counter and collide with ids already on the page.
$yaydp_tooltip_id = \uniqid( 'yaydp-tooltip-' );

?>
<span class="yaydp-tooltip-icon" tabindex="0" role="button" aria-expanded="false" aria-describedby="<?php echo esc_attr( $yaydp_tooltip_id ); ?>" aria-label="<?php esc_attr_e( 'Discount details', 'yaypricing' ); ?>">
	<div class="yaydp-tooltip-content" id="<?php echo esc_attr( $yaydp_tooltip_id ); ?>" role="tooltip">
		<?php foreach ( $tooltips as $tooltip ) : ?>
			<div><?php echo wp_kses_post( $tooltip->get_content() ); ?></div>
		<?php endforeach; ?>
	</div>
</span>
