<?php
/**
 * Condition type: cart_total_weight
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Cart_Total_Weight_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	const INCOMPLETE_KEY = 'cart_total_weight';

	public function slug() {
		return 'cart_total_weight';
	}

	public function label() {
		return __( 'Cart total weight', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CART;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
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
		return YAYDP_Comparators::compare_numeric( \yaydp_get_cart_total_weight(), $condition );
	}

	public function incomplete( array $condition, YAYDP_Condition_Context $ctx ) {
		return $this->shortfall( \yaydp_get_cart_total_weight(), $condition );
	}

	public function incomplete_priority() {
		return 4;
	}
}
