<?php
/**
 * Condition type: specific_customer
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Specific_Customer_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'specific_customer';
	}

	public function label() {
		return __( 'Specific customer', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CUSTOMER;
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'customers',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$in_list = in_array( $ctx->user()->ID, \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- stored ids may be strings.
		return YAYDP_Comparators::matches_list( $in_list, $condition );
	}
}
