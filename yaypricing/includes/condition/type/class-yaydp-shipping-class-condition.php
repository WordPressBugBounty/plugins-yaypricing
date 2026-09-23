<?php
/**
 * Condition type: shipping_class
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Shipping_Class_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'shipping_class';
	}

	public function label() {
		return __( 'Cart item - shipping class', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CART_ITEMS;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'shipping_classes',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$wc_cart = $ctx->wc_cart();
		if ( ! $wc_cart ) {
			return false;
		}
		$ids     = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$in_list = YAYDP_Comparators::IN_LIST === $condition['comparation'];
		foreach ( $wc_cart->get_cart() as $cart_item ) {
			$class_id = $cart_item['data']->get_shipping_class_id();
			if ( ! empty( $class_id ) && in_array( $class_id, $ids ) === $in_list ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- stored ids may be strings.
				return true;
			}
		}
		return false;
	}
}
