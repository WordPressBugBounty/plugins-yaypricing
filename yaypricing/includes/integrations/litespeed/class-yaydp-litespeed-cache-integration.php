<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
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
		add_action( 'yaydp_after_saving_data', array( $this, 'remove_cache' ) );
	}

	public function remove_cache() {
		do_action( 'litespeed_purge_all' );
	}

}
