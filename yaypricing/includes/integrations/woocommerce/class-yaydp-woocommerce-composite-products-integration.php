<?php
/**
 * Handles the integration of WooCommerce Composite Products plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_WooCommerce_Composite_Products_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'WC_Composite_Products' ) ) {
			return;
		}

		add_filter( 'yaydp_init_cart_items', array( $this, 'initialize_cart_items' ) );
		add_action( 'yaydp_after_set_cart_item_price', array( $this, 'handle_composite_container_cart_item' ), 10, 2 );
		add_filter( 'woocommerce_composited_item_price', array( $this, 'replace_composite_cart_item_price' ), 100, 2 );
		add_filter( 'yaydp_before_calculate_product_pricing', array( $this, 'remove_all_things' ) );
		add_filter( 'yaydp_cart_item_price_html', array( $this, 'adjust_cart_item_price_html' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'adjust_cart_item_price_html' ), 12, 3 );
	}

	public function remove_all_things() {
		foreach ( \WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			unset( \WC()->cart->cart_contents[ $cart_item_key ]['yaydp_custom_data']['item_extra_data']['wc_composite'] );
		}
	}

	public function initialize_cart_items( $items ) {
		if ( ! function_exists( 'wc_cp_is_composited_cart_item' ) || ! function_exists( 'wc_cp_is_composite_container_cart_item' ) || ! function_exists( 'wc_cp_get_composited_cart_item_container' ) ) {
			return $items;
		}

		foreach ( $items as $key => &$item ) {

			$extra_data = $item['extra_data'] ?? array();

			if ( \wc_cp_is_composite_container_cart_item( $item ) ) {
				$base_price   = \WC_CP_Products::filter_get_price_cart( \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $item['data'] ), $item['data'] );
				$custom_price = $base_price;
				if ( ! empty( $item['composite_children'] ) ) {
					foreach ( $items as $check_key => $check_item ) {
						if ( ! in_array( $check_key, $item['composite_children'] ) ) {
							continue;
						}
						$custom_price += \WC_CP_Products::filter_get_price_cart( \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $check_item['data'] ), $check_item['data'] );
					}
				}

				$extra_data['wc_composite'] = array(
					'is_composite_container_item' => true,
					'base_price'                  => $base_price,
				);

				$item['custom_price'] = $custom_price;

			} elseif ( \wc_cp_is_composited_cart_item( $item ) ) {
				$extra_data['wc_composite'] = array(
					'is_composite_item' => true,
					'base_price'        => \WC_CP_Products::filter_get_price_cart( \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $item['data'] ), $item['data'] ),
				);
				$composite_container_item   = \wc_cp_get_composited_cart_item_container( $item );
				if ( ! empty( $composite_container_item ) ) {
					$composite    = $composite_container_item['data'];
					$product_id   = $item['product_id'];
					$component_id = $item['composite_item'];

					$component_option = $composite->get_component_option( $component_id, $product_id );

					$extra_data['wc_composite']['is_priced_individually'] = $component_option->is_priced_individually();
				}
				$item['custom_price'] = 0;
			}

			$item['extra_data'] = $extra_data;

		}

		return $items;
	}

	public function handle_composite_container_cart_item( $item, $item_key ) {
		if ( empty( \WC()->cart->cart_contents[ $item_key ] ) ) {
			return;
		}
		$cart_item = \WC()->cart->cart_contents[ $item_key ];
		if ( empty( $cart_item['yaydp_custom_data']['item_extra_data']['wc_composite'] ) ) {
			return;
		}

		$base_price = $cart_item['yaydp_custom_data']['item_extra_data']['wc_composite']['base_price'];
		if ( ! empty( $cart_item['yaydp_custom_data']['item_extra_data']['wc_composite']['is_composite_container_item'] ) ) {
			$discount_amount           = max( $cart_item['yaydp_custom_data']['initial_price'] - $cart_item['yaydp_custom_data']['price'], 0 );
			$remaining_discount_amount = $base_price < $discount_amount ? abs( $base_price - $discount_amount ) : 0;
			$discounted_price          = max( 0, $base_price - $discount_amount );
			\WC()->cart->cart_contents[ $item_key ]['data']->set_price( $discounted_price );
			\WC()->cart->cart_contents[ $item_key ]['yaydp_custom_data']['item_extra_data']['wc_composite']['remaining_discount_amount'] = $remaining_discount_amount;
			\WC()->cart->cart_contents[ $item_key ]['yaydp_custom_data']['item_extra_data']['wc_composite']['bundle_discounted_price']   = $cart_item['yaydp_custom_data']['price'];
			\WC()->cart->cart_contents[ $item_key ]['yaydp_custom_data']['item_extra_data']['wc_composite']['bundle_initial_price']      = $cart_item['yaydp_custom_data']['initial_price'];
			\WC()->cart->cart_contents[ $item_key ]['yaydp_custom_data']['price'] = $discounted_price;
		}

		if ( ! empty( $cart_item['yaydp_custom_data']['item_extra_data']['wc_composite']['is_composite_item'] ) ) {
			$composite_parent_key = $cart_item['composite_parent'] ?? '';
			$composite_parent     = \WC()->cart->cart_contents[ $composite_parent_key ];
			if ( ! empty( $composite_parent ) ) {
				$discount_amount           = $composite_parent['yaydp_custom_data']['item_extra_data']['wc_composite']['remaining_discount_amount'] ?? 0;
				$remaining_discount_amount = $base_price < $discount_amount ? abs( $base_price - $discount_amount ) : 0;
				\WC()->cart->cart_contents[ $item_key ]['data']->replaced_price = max( 0, $base_price - $discount_amount );
				\WC()->cart->cart_contents[ $composite_parent_key ]['yaydp_custom_data']['item_extra_data']['wc_composite']['remaining_discount_amount'] = $remaining_discount_amount;
			}
		}
	}

	public function replace_composite_cart_item_price( $price, $product ) {
		if ( ! isset( $product->replaced_price ) ) {
			return $price;
		}
		return $product->replaced_price;
	}

	public function adjust_cart_item_price_html( $html, $cart_item ) {
		$yaydp_cart_item = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );
		if ( ! $yaydp_cart_item->can_modify() ) {
			return $html;
		}

		if ( ! empty( $cart_item['yaydp_custom_data']['item_extra_data']['wc_composite']['is_priced_individually'] ) ) {
			ob_start();
			echo wp_kses_post( \wc_price( $cart_item['data']->get_price() ) );
			$html = ob_get_contents();
			ob_end_clean();
		}

		if ( ! empty( $cart_item['yaydp_custom_data']['item_extra_data']['wc_composite']['is_composite_container_item'] ) ) {
			$yaydp_cart_item = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );
			$tooltips        = array();
			foreach ( $yaydp_cart_item->get_modifiers() as $modifier ) {
				$rule    = $modifier->get_rule();
				$tooltip = $rule->get_tooltip( $modifier );
				if ( ! $tooltip->is_enabled() ) {
					continue;
				}
				$tooltips[] = $tooltip;
			}
			$show_regular_price = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_regular_price();
			$origin_price       = $cart_item['yaydp_custom_data']['item_extra_data']['wc_composite']['bundle_initial_price'];
			$discounted_price   = $cart_item['yaydp_custom_data']['item_extra_data']['wc_composite']['bundle_discounted_price'];
			ob_start();
			\wc_get_template(
				'cart-item-price/normal-item.php',
				array(
					'tooltips'                => $tooltips,
					'origin_price'            => $origin_price,
					'prices_base_on_quantity' => array( strval( $discounted_price ) => $yaydp_cart_item->get_quantity() ),
					'show_regular_price'      => $show_regular_price,
					'product'                 => $cart_item['data'],
				),
				'',
				YAYDP_PLUGIN_PATH . 'includes/templates/'
			);
			$html = ob_get_contents();
			ob_end_clean();
		}
		return $html;
	}
}
