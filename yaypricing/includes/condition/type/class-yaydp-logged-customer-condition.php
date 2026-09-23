<?php
/**
 * Condition type: logged_customer
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Logged_Customer_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	const INCOMPLETE_KEY = 'logged_customer';

	public function slug() {
		return 'logged_customer';
	}

	public function label() {
		return __( 'Login status', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CUSTOMER;
	}

	public function comparators() {
		return YAYDP_Comparators::boolean();
	}

	public function editor() {
		return array( 'kind' => 'none' );
	}

	public function default_value() {
		return true;
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		return $condition['comparation'] ? $ctx->is_logged_in() : ! $ctx->is_logged_in();
	}

	public function incomplete( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( $this->check( $condition, $ctx ) ) {
			return array();
		}
		return array(
			'type'          => self::INCOMPLETE_KEY,
			'missing_value' => false,
		);
	}
}
