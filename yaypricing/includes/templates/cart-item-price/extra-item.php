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
	<div class="yaydp-free-item-badge"><?php esc_html_e( $free_text, 'yaypricing' ); ?></div>
	<?php if ( count( $tooltips ) > 0 ) : ?>
		<span class="yaydp-tooltip-icon">
			<div class="yaydp-tooltip-content">
				<?php foreach ( $tooltips as $tooltip ) : ?>
					<div><?php echo wp_kses_post( $tooltip->get_content() ); ?></div>
				<?php endforeach; ?>
			</div>
		</span>
	<?php endif; ?>
</div>
