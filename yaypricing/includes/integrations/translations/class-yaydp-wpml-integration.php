<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Translations;

use YAYDP\Traits\YAYDP_Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_WPML_Integration {

	use YAYDP_Singleton;

	protected function __construct() {
		add_filter( 'yaydp_translated_object_id', array( __CLASS__, 'get_translation_object_id' ), 10, 2 );
		add_filter( 'yaydp_translated_list_object_id', array( __CLASS__, 'get_list_translation_object_id' ), 10, 2 );
	}

	public static function get_translation_object_id( $object_id, $object_type = 'post' ) {
		$current_language = apply_filters( 'wpml_current_language', null );
		return apply_filters( 'wpml_object_id', $object_id, $object_type, true, $current_language );
	}

	public static function get_list_translation_object_id( $list, $object_type = 'post' ) {
		$translated_list = array();
		foreach ( $list as $id ) {
			$translated_list[] = $id;
			$translated_list[] = self::get_translation_object_id( $id, $object_type );
		}
		return array_unique( $translated_list );
	}

}
