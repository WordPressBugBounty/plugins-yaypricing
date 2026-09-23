<?php
/**
 * Condition type: billing_region
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Billing_Region_Condition extends YAYDP_Shipping_Region_Condition {

	public function slug() {
		return 'billing_region';
	}

	public function label() {
		return __( 'Billing region', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_OTHERS;
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
			'source' => 'billing_regions',
		);
	}

	/**
	 * Billing address instead of shipping address.
	 *
	 * @param \WC_Customer $customer Customer.
	 * @return string[] [ country, state ]
	 */
	protected function codes( $customer ) {
		return array( $customer->get_billing_country(), $customer->get_billing_state() );
	}
}
