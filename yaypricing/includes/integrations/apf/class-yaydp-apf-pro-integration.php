<?php

/**
 * Handles the integration of Advanced Product Fields plugin with YayDP system.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\APF;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_APF_Pro_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'wapf_pro' ) ) {
			return;
		}
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'adjust_cart_item_subtotal_html' ), 10000, 3 );
	}

	public function initialize_cart_item_price( $item_price, $cart_item ) {

		if ( ! class_exists( '\SW_WAPF_PRO\Includes\Classes\Fields' ) ) {
			return $item_price;
		}

		if ( ! class_exists( '\SW_WAPF_PRO\Includes\Classes\Cart' ) ) {
			return $item_price;
		}

		$quantity      = empty( $cart_item['quantity'] ) ? 1 : \wc_stock_amount( $cart_item['quantity'] );
		$options_total = 0;

		if ( empty( $cart_item['wapf'] ) ) {
			return $item_price;
		}

		$product_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
		$product    = \wc_get_product( $product_id );

		if ( empty( $product ) ) {
			return $item_price;
		}

		$base         = \SW_WAPF_PRO\Includes\Classes\Cart::get_cart_item_base_price( $product, $quantity, $cart_item );
		$formula_base = apply_filters( 'wapf/pricing/cart_item_base_for_formulas', $base, $product, $quantity, $cart_item );

		foreach ( $cart_item['wapf'] as $field ) {
			if ( ! empty( $field['values'] ) ) {
				$clone_idx = $field['clone_idx'] ?? ( $cart_item['wapf_clone'] ?? 0 );
				$qty_based = ( isset( $field['clone_type'] ) && $field['clone_type'] === 'qty' ) || ! empty( $field['qty_based'] );

				foreach ( $field['values'] as $value ) {

					if ( 0 === $value['price'] || ( isset( $value['price_type'] ) && $value['price_type'] === 'none' ) ) {
						continue;
					}

					$v     = $value['label'] ?? $field['raw'] ?? '';
					$price = \SW_WAPF_PRO\Includes\Classes\Fields::do_pricing( $qty_based, $value['price_type'] ?? 'fx', $value['price'], $base, $formula_base, $quantity, $v, $product_id, $cart_item['wapf'], $cart_item['wapf_field_groups'] ?? [], $clone_idx, $options_total );
					$options_total = $options_total + $price;

				}
			}
		}

		return $item_price + $options_total;
	}

	/**
	 * Adjust cart item subtotal to reflect yaypricing discount
	 *
	 * @param string $html Current item subtotal html.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string Modified subtotal html.
	 */
	public function adjust_cart_item_subtotal_html( $html, $cart_item, $cart_item_key ) {
		if ( empty( $cart_item['wapf'] ) ) {
			return $html;
		}

		$yaydp_cart_item = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );

		if ( ! $yaydp_cart_item->can_modify() ) {
			return $html;
		}

		// Get the modified price from yaypricing (includes discount applied to base + APF fields)
		$item_price = isset( $cart_item['yaydp_custom_data']['price'] ) ? $cart_item['yaydp_custom_data']['price'] : $cart_item['data']->get_price();
		$item_initial_price = isset( $cart_item['yaydp_custom_data']['initial_price'] ) ? $cart_item['yaydp_custom_data']['initial_price'] : $yaydp_cart_item->get_initial_price();
		$item_quantity = $cart_item['quantity'] ?? 1;

		$product = $yaydp_cart_item->get_product();
		$subtotal = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $item_price,
				'qty'             => $item_quantity,
				'display_context' => 'cart',
			)
		);
		$subtotal = \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) );
		$html = $subtotal;

		// Optionally show original subtotal with strikethrough
		if ( \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_original_subtotal_price() ) {
			$original_subtotal = \wc_get_price_to_display(
				$product,
				array(
					'price'           => $item_initial_price,
					'qty'             => $item_quantity,
					'display_context' => 'cart',
				)
			);
			ob_start();
			?>
			<del><?php echo \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $original_subtotal ) ); ?></del>
			<?php
			$extra_html = ob_get_contents();
			ob_end_clean();
			$html = '<div class="price">' . $extra_html . $html . '</div>';
		}

		return apply_filters( 'yaydp_cart_item_subtotal_html', $html, $cart_item );
	}
}
