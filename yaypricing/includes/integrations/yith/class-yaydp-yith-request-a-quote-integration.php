<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\YITH;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_YITH_Request_A_Quote_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! defined( 'YITH_YWRAQ_VERSION' ) ) {
			return;
		}
		add_filter( 'yith_ywraq_item_remove_link', array( $this, 'initialize_global_item_variable' ), 100, 2 );
		add_filter( 'yith_ywraq_hide_price_template', array( $this, 'change_rqa_price' ), 100, 2 );
	}

	public function initialize_global_item_variable( $remove_link, $raq_key ) {

		if ( ! function_exists( 'YITH_Request_Quote' ) ) {
			return $remove_link;
		}

		global $raq_current_key, $raq_cart;

		if ( empty( $raq_cart ) ) {
			$raq_content = \YITH_Request_Quote()->get_raq_return();
			foreach ($raq_content as $key => $raq_item) {
				$item_product = \wc_get_product( $raq_item['product_id'] );
				$raq_content[$key]['data'] = empty( $item_product ) ? null : $item_product;
			}
			$raq_cart = new \YAYDP\Core\YAYDP_Cart( $raq_content );
			$product_pricing_adjustments = new \YAYDP\Core\Adjustments\YAYDP_Product_Pricing_Adjustments( $raq_cart );
			$product_pricing_adjustments->do_stuff();
		}

		$raq_current_key = $raq_key;
		return $remove_link;
	}

	public function change_rqa_price( $price, $product ) {
		

		global $raq_current_key, $raq_cart;

		if ( empty( $raq_current_key ) || empty($raq_cart ) ) {
			return $price;
		}

		foreach ($raq_cart->get_items() as $key => $item) {
			if ( $raq_current_key === $key ) {
				$item_price = $item->get_price();
				$item_original_price = $item->get_initial_price();
				$item_quantity = $item->get_quantity();
				$html = \wc_price( $item_price * $item_quantity );
				if ( $item_price !== $item_original_price ) {
					$html = '<del>' . \wc_price( $item_original_price * $item_quantity ) . '</del>  ' . $html;
				}
				return $html;
			}
		}

		return $price;
	}
}
