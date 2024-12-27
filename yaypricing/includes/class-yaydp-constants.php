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
		$this->define( 'YAYDP_PRODUCT_CALCULATE_PRIORITY', 110 );
		$this->define( 'YAYDP_CART_CALCULATE_PRIORITY', 111 );
		$this->define( 'YAYDP_CHECKOUT_CALCULATE_PRIORITY', 112 );
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
