<?php
/**
 * Condition type: bought_products
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Bought_Products_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'bought_products';
	}

	public function label() {
		return __( 'Purchased products (lifetime)', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Purchased products among all the orders they have placed in the past.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_PURCHASE_HISTORY;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::contain();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'products',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$user   = $ctx->user();
		$ids    = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$bought = array();
		foreach ( $ids as $product_id ) {
			if ( function_exists( 'wc_customer_bought_product' ) && \wc_customer_bought_product( $user->user_email, $user->ID, $product_id ) ) {
				$bought[] = $product_id;
			}
		}
		return YAYDP_Comparators::matches_contain( $bought, count( $ids ), $condition );
	}
}
