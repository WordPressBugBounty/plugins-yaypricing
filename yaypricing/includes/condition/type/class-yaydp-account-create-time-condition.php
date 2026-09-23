<?php
/**
 * Condition type: account_create_time
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Account_Create_Time_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'account_create_time';
	}

	public function label() {
		return __( 'Account age', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Account lifetime from the time they are created', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CUSTOMER;
	}

	public function comparators() {
		return YAYDP_Comparators::pick(
			array(
				YAYDP_Comparators::GREATER_THAN => __( 'Longer than', 'yaypricing' ),
				YAYDP_Comparators::LESS_THAN    => __(
					'Within',
					'yaypricing'
				),
			)
		);
	}

	public function editor() {
		return array( 'kind' => 'days' );
	}

	public function default_value() {
		return 1;
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( ! $ctx->is_logged_in() ) {
			return false;
		}
		$days    = max( 0, floatval( $condition['value'] ) );
		$bound   = new \DateTime( "- $days day" );
		$created = new \DateTime( $ctx->user()->user_registered );
		if ( YAYDP_Comparators::GREATER_THAN === $condition['comparation'] ) {
			return $created < $bound;
		}
		if ( YAYDP_Comparators::LESS_THAN === $condition['comparation'] ) {
			return $created > $bound;
		}
		return false;
	}
}
