<?php
/**
 * Condition type: has_switch_subscription — the cart contains a subscription
 * switch of the given direction(s).
 *
 * @package YayPricing\Integrations
 * @since 3.5.8
 */

namespace YAYDP\Integrations\WooCommerce;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Has_Switch_Subscription_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'has_switch_subscription';
	}

	public function label() {
		return __( 'Switch subscription', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'    => 'select',
			'options' => array(
				array(
					'value' => 'downgrade',
					'label' => __( 'Downgrade', 'yaypricing' ),
				),
				array(
					'value' => 'upgrade',
					'label' => __( 'Upgrade', 'yaypricing' ),
				),
			),
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$switches = \wcs_cart_contains_switches( 'switch' );
		if ( false == $switches ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- historical.
			return false;
		}
		$directions = array(
			'upgraded'    => array( 'upgrade' ),
			'downgraded'  => array( 'downgrade' ),
			'crossgraded' => array( 'upgrade', 'downgrade' ),
		);
		$in_cart    = array();
		foreach ( $switches as $item ) {
			$type = $item['upgraded_or_downgraded'];
			if ( isset( $directions[ $type ] ) ) {
				$in_cart = array_merge( $in_cart, $directions[ $type ] );
			}
		}
		$matched = array_intersect( array_unique( $in_cart ), \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) );
		return YAYDP_Comparators::matches_list( ! empty( $matched ), $condition );
	}
}
