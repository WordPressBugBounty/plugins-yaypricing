<?php
/**
 * Condition type: shipping_total
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Shipping_Total_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	const INCOMPLETE_KEY = 'cart_shipping_total';

	public function slug() {
		return 'shipping_total';
	}

	public function label() {
		return __( 'Shipping total', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_OTHERS;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::pick( array( YAYDP_Comparators::GREATER_THAN, YAYDP_Comparators::LESS_THAN, YAYDP_Comparators::LTE, YAYDP_Comparators::GTE ) );
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
		return YAYDP_Comparators::compare_numeric( \yaydp_get_shipping_fee(), $condition );
	}

	public function incomplete( array $condition, YAYDP_Condition_Context $ctx ) {
		return $this->shortfall( \yaydp_get_shipping_fee(), $condition );
	}

	public function incomplete_priority() {
		return 3;
	}
}
