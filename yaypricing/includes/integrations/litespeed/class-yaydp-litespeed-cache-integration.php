<?php
/**
 * Handles the Litespeed cache
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\LiteSpeed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_LiteSpeed_Cache_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'yaydp_clear_cache', array( __CLASS__, 'remove_cache' ) );
	}

	public static function remove_cache() {
		do_action( 'litespeed_purge_all' );
	}

}
