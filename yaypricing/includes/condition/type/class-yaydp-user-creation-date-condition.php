<?php
/**
 * Condition type: user_creation_date
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_User_Creation_Date_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'user_creation_date';
	}

	public function label() {
		return __( 'Registration date', 'yaypricing' );
	}

	public function tooltip() {
		return __( 'Applies to users who joined on, before, after, or between specific dates.', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CUSTOMER;
	}

	public function comparators() {
		return YAYDP_Comparators::date( __( 'Between', 'yaypricing' ) );
	}

	public function editor() {
		return array( 'kind' => 'date' );
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( ! $ctx->is_logged_in() ) {
			return false;
		}
		return $this->compare( $ctx->user()->user_registered, $condition );
	}

	/**
	 * String comparison on the Y-m-d part of the registration timestamp.
	 * Historical quirk kept: the range's upper bound is compared against the
	 * full timestamp, so a range ending on the registration day excludes it.
	 *
	 * @param string $registered user_registered ("Y-m-d H:i:s").
	 * @param array  $condition  Stored condition.
	 * @return bool
	 */
	private function compare( $registered, array $condition ) {
		$date  = explode( ' ', $registered )[0];
		$value = $condition['value'];
		if ( YAYDP_Comparators::BEFORE === $condition['comparation'] ) {
			return $date < $value;
		}
		if ( YAYDP_Comparators::AFTER === $condition['comparation'] ) {
			return $date > $value;
		}
		if ( YAYDP_Comparators::ON === $condition['comparation'] ) {
			return $date === $value;
		}
		if ( YAYDP_Comparators::IN_RANGE === $condition['comparation'] ) {
			return $date >= $value[0] && $registered <= $value[1];
		}
		return false;
	}
}
