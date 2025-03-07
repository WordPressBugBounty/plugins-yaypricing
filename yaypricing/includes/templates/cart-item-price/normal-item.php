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
		$origin_price = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $origin_price,
				'display_context' => 'cart',
			)
		);
		$origin_price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $origin_price );
		foreach ( $prices_base_on_quantity as $price => $quantity ) :
			$is_subscription_item = false;
			if ( class_exists( '\WCS_ATT_Product_Prices' ) && class_exists( '\WCS_ATT_Display_Cart' ) ) {
				$item_key = $item->get_key();
				$cart_item = \WC()->cart->get_cart_item( $item_key );
				if ( isset( $cart_item['wcsatt_data']['active_subscription_scheme'] ) ) {
					$scheme_key = $cart_item['wcsatt_data']['active_subscription_scheme'];
					$is_subscription_item = true;
					$product->set_price( $price );
					if ( ! \WCS_ATT_Display_Cart::display_prices_including_tax() ) {
						$price = wc_get_price_excluding_tax( $product, array( 'price' => \WCS_ATT_Product_Prices::get_price( $product, $scheme_key ) ) );
					} else {
						$price = wc_get_price_including_tax( $product, array( 'price' => \WCS_ATT_Product_Prices::get_price( $product, $scheme_key ) ) );
					}
				}
			}
			if ( ! $is_subscription_item ) {
				$price = \wc_get_price_to_display(
					$product,
					array(
						'price'           => $price,
						'display_context' => 'cart',
					)
				);
			}
			$price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $price );
			?>
				<div class="price">
					<span class="yaydp-cart-item-quantity"><?php echo esc_html( $quantity ); ?>&nbsp;&times;&nbsp;</span>
					<?php if ( $show_regular_price && floatval( $origin_price ) !== floatval( $price ) ) : ?>
						<del><?php echo \wc_price( $origin_price ); ?></del>
					<?php endif; ?>
					<?php echo \wc_price( $price ); ?>
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
