<?php
/**
 * Condition type: cart_quantity
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

class YAYDP_Cart_Quantity_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	const INCOMPLETE_KEY = 'cart_quantity';

	public function slug() {
		return 'cart_quantity';
	}

	public function label() {
		return __( 'Cart quantity', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CART;
	}

	public function comparators() {
		return YAYDP_Comparators::numeric();
	}

	public function editor() {
		return array( 'kind' => 'number' );
	}

	public function default_value() {
		return 100;
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		return YAYDP_Comparators::compare_numeric( YAYDP_Cart_Item_Matcher::total_quantity( $ctx->cart_items() ), $condition );
	}

	public function incomplete( array $condition, YAYDP_Condition_Context $ctx ) {
		return $this->shortfall( YAYDP_Cart_Item_Matcher::total_quantity( $ctx->cart_items() ), $condition );
	}

	public function incomplete_priority() {
		return 1;
	}
}
