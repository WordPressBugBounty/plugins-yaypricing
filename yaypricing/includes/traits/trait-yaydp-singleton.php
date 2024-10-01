<?php
/**
 * Singleton Trait
 * This trait can be used to implement the Singleton design pattern in PHP classes
 * It ensures that only one instance of the class is created and provides a global point of access to it
 *
 * @package YayPricing\Traits
 */

namespace YAYDP\Traits;

defined( 'ABSPATH' ) || exit;

trait YAYDP_Singleton {

	/**
	 * Instance of the class
	 *
	 * This variable holds the instance of the singleton class
	 *
	 * @var YAYDP_Singleton
	 */
	private static $instance = null;

	/**
	 * This method returns the singleton instance of the class.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}
