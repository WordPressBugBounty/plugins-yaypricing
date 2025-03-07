<?php
/**
 * Handles the integration of WPClever Product Bundles plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WPClever;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_WPC_Product_Bundles_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! function_exists( 'woosb_init' ) ) {
			return;
		}

		// add_filter( 'yaydp_init_cart_items', array( $this, 'remove_initial_bundled_items' ) );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'adjust_cart_item_price_html' ), 10000, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'adjust_cart_item_subtotal_html' ), 10000, 3 );
		add_filter( 'woosb_item_price_before_set', array( $this, 'adjust_item_price' ), 100, 2 );
	}

	public function remove_initial_bundled_items( $items ) {
		foreach ( $items as $key => $item ) {
			
			//Exclude items which have fixed price
			if ( empty( $item['woosb_fixed_price'] ) ) {
				continue;
			}
			if ( ! empty( $item['woosb_parent_id'] ) ) {
				unset( $items[ $key ] );
			}
		}
		return $items;
	}

	private function get_bundle_item_prices( $cart_item ) {
		$cart_items = \WC()->cart->get_cart();
		$item_price = 0;
		$item_initial_price = 0;
		
		foreach ($cart_items as $key => $item) {
			if ( ! in_array( $key, $cart_item['woosb_keys'] ) ) {
				continue;
			}
			$yaydp_cart_item    = new \YAYDP\Core\YAYDP_Cart_Item( $item );
			if ( ! $yaydp_cart_item->can_modify() ) {
				$item_price += $item['data']->get_price();
				$item_initial_price += $item['data']->get_price();
				continue;
			}

			$item_price    += $item['yaydp_custom_data']['price'];
			$item_initial_price    += $item['yaydp_custom_data']['initial_price'];

		}
		return [
			'price' => $item_price,
			'initial_price' => $item_initial_price
		];
	}

	public function adjust_cart_item_price_html( $html, $cart_item ) {

		$yaydp_cart_item    = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );

		if ( ! empty( $cart_item['woosb_fixed_price'] ) ) {
			return $html;
		}

		if ( empty( $cart_item['woosb_keys'] ) ) {
			return $html;
		}

		$prices = $this->get_bundle_item_prices( $cart_item );
		$item_price = $prices['price'];
		$item_initial_price = $prices['initial_price'];
		$item_quantity = $cart_item['quantity'];

		$product       = $yaydp_cart_item->get_product();
		$subtotal           = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $item_price,
				'qty'             => $item_quantity,
				'display_context' => 'cart',
			)
		);
		$subtotal = \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) );
		$html = $subtotal;

		if ( ! \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_original_subtotal_price() ) {
			return $html;
		}
		$subtotal           = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $item_initial_price,
				'qty'             => $item_quantity,
				'display_context' => 'cart',
			)
		);
		if ( $yaydp_cart_item->can_modify() ) {
			ob_start();
			?>
			<del><?php echo \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) ); ?></del>
			<?php
			$extra_html = ob_get_contents();
			ob_end_clean();
			$html = '<div class="price">' . $extra_html . $html . '</div>';
		}
		return apply_filters( 'yaydp_cart_item_price_html', $html, $cart_item );

		return $html;
	}
	public function adjust_cart_item_subtotal_html( $html, $cart_item ) {

		$yaydp_cart_item    = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );

		if ( ! empty( $cart_item['woosb_fixed_price'] ) ) {
			return $html;
		}

		if ( empty( $cart_item['woosb_keys'] ) ) {
			return $html;
		}

		$prices = $this->get_bundle_item_prices( $cart_item );
		$item_price = $prices['price'];
		$item_initial_price = $prices['initial_price'];
		$item_quantity = $cart_item['quantity'];

		$product       = $yaydp_cart_item->get_product();
		$subtotal           = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $item_price,
				'qty'             => $item_quantity,
				'display_context' => 'cart',
			)
		);
		$subtotal = \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) );
		$html = $subtotal;

		if ( ! \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_original_subtotal_price() ) {
			return $html;
		}
		$subtotal           = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $item_initial_price,
				'qty'             => $item_quantity,
				'display_context' => 'cart',
			)
		);
		if ( $yaydp_cart_item->can_modify() ) {
			ob_start();
			?>
			<del><?php echo \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) ); ?></del>
			<?php
			$extra_html = ob_get_contents();
			ob_end_clean();
			$html = '<div class="price">' . $extra_html . $html . '</div>';
		}
		return apply_filters( 'yaydp_cart_item_price_html', $html, $cart_item );
	}

	public function adjust_item_price( $price, $cart_item ) {
		$yaydp_cart_item    = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );

		if ( ! empty( $cart_item['woosb_fixed_price'] ) ) {
			return $price;
		}

		if ( ! $yaydp_cart_item->can_modify() ) {
			return $price;
		}

		return $cart_item['yaydp_custom_data']['price'];
	}
}
