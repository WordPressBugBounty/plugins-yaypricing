<?php
/**
 * Condition type: wcml_currency — the shopper's current WCML currency.
 *
 * @package YayPricing\Integrations
 * @since 3.5.8
 */

namespace YAYDP\Integrations\WCML;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_WCML_Currency_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'wcml_currency';
	}

	public function label() {
		return __( 'WCML current currency', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		$options = array();
		$wcml    = $this->wcml();
		if ( $wcml ) {
			$names = get_woocommerce_currencies();
			foreach ( $wcml->multi_currency->get_currencies( true ) as $code => $data ) {
				$name      = isset( $names[ $code ] ) ? $names[ $code ] : $code;
				$options[] = array(
					'value' => $code,
					'label' => "$name ( $code )",
				);
			}
		}
		return array(
			'kind'    => 'select',
			'options' => $options,
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$wcml = $this->wcml();
		if ( ! $wcml ) {
			return false;
		}
		$current = $wcml->multi_currency->get_client_currency();
		$in_list = in_array( $current, \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
		return YAYDP_Comparators::matches_list( $in_list, $condition );
	}

	/**
	 * WCML's multi-currency controller, exposed only as a global by WCML.
	 *
	 * @return object|null
	 */
	private function wcml() {
		$wcml = isset( $GLOBALS['woocommerce_wpml'] ) ? $GLOBALS['woocommerce_wpml'] : null;
		return ( $wcml && isset( $wcml->multi_currency ) ) ? $wcml : null;
	}
}
