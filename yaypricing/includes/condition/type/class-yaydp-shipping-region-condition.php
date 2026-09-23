<?php
/**
 * Condition type: shipping_region
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Shipping_Region_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'shipping_region';
	}

	public function label() {
		return __( 'Shipping region', 'yaypricing' );
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
			'source' => 'shipping_regions',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$customer = $ctx->customer();
		return $customer ? $this->matches( $this->codes( $customer ), $condition, $ctx ) : false;
	}

	/** Country and state codes read from the customer. */
	protected function codes( $customer ) {
		return array( $customer->get_shipping_country(), $customer->get_shipping_state() );
	}

	/** Whether a stored `continent:X` / `country:X` / `state:X:Y` value names [ country, state ]. */
	protected function matches( array $codes, array $condition, YAYDP_Condition_Context $ctx ) {
		$country   = strtoupper( \wc_clean( $codes[0] ) );
		$state     = strtoupper( \wc_clean( $codes[1] ) );
		$continent = strtoupper( \wc_clean( $ctx->continent_code( $country ) ) );
		$names     = array_filter( array( $continent ? 'continent:' . esc_attr( $continent ) : '', $country ? 'country:' . esc_attr( $country ) : '', $state ? 'state:' . esc_attr( $country . ':' . $state ) : '' ) );
		$in_list   = ! empty( array_intersect( $this->codes_in( $condition ), $names ) );
		return YAYDP_Comparators::matches_list( $in_list, $condition );
	}

	/**
	 * Stored codes: bare strings (rules saved before 3.5.8), { value, title }
	 * objects (admin since 3.5.8) or a mix — the only place that knows both shapes.
	 */
	private function codes_in( array $condition ) {
		$codes = array_map(
			function ( $entry ) {
				return is_array( $entry ) ? ( isset( $entry['value'] ) ? $entry['value'] : '' ) : $entry;
			},
			(array) $condition['value']
		);
		return array_values( array_filter( $codes, 'is_string' ) );
	}
}
