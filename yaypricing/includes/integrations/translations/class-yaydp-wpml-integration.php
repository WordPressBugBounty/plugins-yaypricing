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

	const PRICING_TABLE_WPML_GROUP    = 'yaypricing';
	const PRICING_TABLE_FALLBACK_FLAG = 'yaydp_pt_titles_wpml_registered';

	/**
	 * Maps Pricing Table settings field keys to their WPML string names.
	 *
	 * @var array
	 */
	protected static $pricing_table_string_names = array(
		'table_title'    => 'pricing_table_table_title',
		'quantity_title' => 'pricing_table_quantity_title',
		'discount_title' => 'pricing_table_discount_title',
		'price_title'    => 'pricing_table_price_title',
	);

	protected function __construct() {
		add_filter( 'yaydp_translated_object_id', array( __CLASS__, 'get_translation_object_id' ), 10, 2 );
		add_filter( 'yaydp_translated_list_object_id', array( __CLASS__, 'get_list_translation_object_id' ), 10, 2 );
		add_action( 'yaydp_after_saving_data', array( __CLASS__, 'on_save_pricing_table_titles' ) );
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

	/**
	 * Detects whether WPML String Translation is available.
	 *
	 * @return bool
	 */
	public static function is_wpml_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || has_action( 'wpml_register_string' );
	}

	/**
	 * Registers Pricing Table titles with WPML String Translation.
	 *
	 * @param array $titles Field key => text value (e.g. 'table_title' => 'Quantity discounts').
	 */
	public static function register_pricing_table_titles( array $titles ) {
		if ( ! self::is_wpml_active() ) {
			return;
		}
		foreach ( self::$pricing_table_string_names as $field_key => $string_name ) {
			if ( ! isset( $titles[ $field_key ] ) || ! is_string( $titles[ $field_key ] ) || '' === $titles[ $field_key ] ) {
				continue;
			}
			do_action( 'wpml_register_single_string', self::PRICING_TABLE_WPML_GROUP, $string_name, $titles[ $field_key ] );
		}
	}

	/**
	 * Registers Pricing Table titles with WPML right after settings are saved.
	 *
	 * @param array $body REST request body passed to the `yaydp_after_saving_data` action.
	 */
	public static function on_save_pricing_table_titles( $body ) {
		if ( ! self::is_wpml_active() ) {
			return;
		}
		$titles = isset( $body['settings']['product_pricing']['pricing_table'] ) ? $body['settings']['product_pricing']['pricing_table'] : array();
		if ( empty( $titles ) ) {
			return;
		}
		self::register_pricing_table_titles( $titles );
		update_option( self::PRICING_TABLE_FALLBACK_FLAG, true );
	}

	/**
	 * Translates a Pricing Table title through WPML String Translation.
	 *
	 * @param string $value     Current (default or admin-saved) title text.
	 * @param string $field_key One of 'table_title', 'quantity_title', 'discount_title', 'price_title'.
	 *
	 * @return string
	 */
	public static function translate_pricing_table_title( $value, $field_key ) {
		if ( ! isset( self::$pricing_table_string_names[ $field_key ] ) ) {
			return $value;
		}
		self::maybe_fallback_register_pricing_table_titles();
		return apply_filters( 'wpml_translate_single_string', $value, self::PRICING_TABLE_WPML_GROUP, self::$pricing_table_string_names[ $field_key ] );
	}

	/**
	 * Registers pre-existing (already-saved) Pricing Table titles once, so sites that
	 * customized titles before this feature shipped don't need to re-save settings.
	 */
	protected static function maybe_fallback_register_pricing_table_titles() {
		static $checked = false;
		if ( $checked || ! self::is_wpml_active() || get_option( self::PRICING_TABLE_FALLBACK_FLAG ) ) {
			$checked = true;
			return;
		}
		$checked  = true;
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		self::register_pricing_table_titles(
			array(
				'table_title'    => $settings->get_pricing_table_title(),
				'quantity_title' => $settings->get_pricing_table_quantity_title(),
				'discount_title' => $settings->get_pricing_table_discount_title(),
				'price_title'    => $settings->get_pricing_table_price_title(),
			)
		);
		update_option( self::PRICING_TABLE_FALLBACK_FLAG, true );
	}

}
