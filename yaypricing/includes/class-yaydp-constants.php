<?php
/**
 * Define Plugin constants
 *
 * @package YayPricing\Classes
 * @version 1.0.0
 */

namespace YAYDP;

/**
 * YAYDP_Constants class
 */
class YAYDP_Constants {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructoring function
	 */
	protected function __construct() {
		$this->define_constants();
	}

	/**
	 * Defines all plugin constants
	 */
	protected function define_constants() {
		$this->define( 'YAYDP_SEARCH_LIMIT', 20 );

	}

	/**
	 * Define constant
	 *
	 * @param string $name constant name.
	 * @param string $value constant name.
	 */
	protected function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}
