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
 * @param $show_regular_price
 * @param $product
 * @param $item \YAYDP\Core\YAYDP_Cart_Item|null (absent in overrides that predate it)
 */

defined( 'ABSPATH' ) || exit;

$item = isset( $item ) ? $item : null;

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
		// All Products for Subscriptions reads the price off the product object, so it
		// is set per line below; keep the original to restore once rendering is done.
		$product_price_before_render = null;
		foreach ( $prices_base_on_quantity as $price => $quantity ) :
			$is_subscription_item = false;
			if ( ! is_null( $item ) && class_exists( '\WCS_ATT_Product_Prices' ) && class_exists( '\WCS_ATT_Display_Cart' ) && ! empty( \WC()->cart ) ) {
				$cart_item = \WC()->cart->get_cart_item( $item->get_key() );
				if ( isset( $cart_item['wcsatt_data']['active_subscription_scheme'] ) ) {
					$scheme_key           = $cart_item['wcsatt_data']['active_subscription_scheme'];
					$is_subscription_item = true;
					if ( is_null( $product_price_before_render ) ) {
						$product_price_before_render = $product->get_price( 'edit' );
					}
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
			// Compare at display precision so tax rounding noise never shows a struck-through identical price.
			$is_price_changed = \wc_format_decimal( $origin_price, \wc_get_price_decimals() ) !== \wc_format_decimal( $price, \wc_get_price_decimals() );
			?>
				<div class="price">
					<span class="yaydp-cart-item-quantity"><?php echo esc_html( $quantity ); ?>&nbsp;&times;&nbsp;</span>
					<?php if ( $show_regular_price && $is_price_changed ) : ?>
						<del><?php echo \wc_price( $origin_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></del>
					<?php endif; ?>
					<?php echo \wc_price( $price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php
			endforeach;
		if ( ! is_null( $product_price_before_render ) ) {
			$product->set_price( $product_price_before_render );
		}
		?>
	</div>
	<?php echo \yaydp_render_tooltips( $tooltips ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output, tooltip content is wp_kses_post'ed. ?>
</div>
