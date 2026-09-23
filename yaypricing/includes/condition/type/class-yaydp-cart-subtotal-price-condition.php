<?php
/**
 * Condition type: cart_subtotal_price
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Cart_Item_Matcher;
use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Cart_Subtotal_Price_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	const INCOMPLETE_KEY = 'cart_subtotal';

	public function slug() {
		return 'cart_subtotal_price';
	}

	public function label() {
		return __( 'Cart subtotal price', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CART;
	}

	public function comparators() {
		return YAYDP_Comparators::numeric();
	}

	public function editor() {
		return array(
			'kind'   => 'number',
			'suffix' => 'currency',
		);
	}

	public function default_value() {
		return 100;
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		return YAYDP_Comparators::compare_numeric( $this->subtotal( $ctx ), $condition );
	}

	/** Always the item sum, whatever the family (historical). */
	public function incomplete( array $condition, YAYDP_Condition_Context $ctx ) {
		return $this->shortfall( YAYDP_Cart_Item_Matcher::subtotal( $ctx->cart_items() ), $condition );
	}

	public function incomplete_priority() {
		return 0;
	}

	/**
	 * Checkout-fee rules compare the whole cart's pre-tax subtotal; everything
	 * else sums the items under evaluation at their effective price.
	 *
	 * @param YAYDP_Condition_Context $ctx Context.
	 * @return float
	 */
	private function subtotal( YAYDP_Condition_Context $ctx ) {
		if ( $ctx->cart() && YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE === $ctx->family() ) {
			return $ctx->cart()->get_cart_subtotal( false );
		}
		return YAYDP_Cart_Item_Matcher::subtotal( $ctx->cart_items() );
	}
}
