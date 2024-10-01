<?php

/**
 * The Template for displaying cart item price
 *
 * @since 2.4
 *
 * @package YayPricing\Templates\CartItemPrice
 *
 * @param $origin_price
 * @param $prices_base_on_quantity
 * @param $tooltips
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="yaydp-cart-item-price">
	<div>
		<?php
		$origin_price = \wc_get_price_to_display( $product, array( 'price' => $origin_price ) );
		$origin_price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $origin_price );
		foreach ( $prices_base_on_quantity as $price => $quantity ) :
			$price = \wc_get_price_to_display( $product, array( 'price' => $price ) );
			$price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $price );
			?>
			<div class="price">
				<span class="yaydp-cart-item-quantity"><?php echo esc_html( $quantity ); ?>&nbsp;&times;&nbsp;</span>
				<?php if ( $show_regular_price && floatval( $origin_price ) !== floatval( $price ) ) : ?>
					<del><?php echo wp_kses_post( \wc_price( $origin_price ) ); ?></del>
				<?php endif; ?>
				<?php echo wp_kses_post( \wc_price( $price ) ); ?>
			</div>
			<?php
		endforeach;
		?>
	</div>
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
