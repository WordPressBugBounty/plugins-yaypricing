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
		add_action( 'yaydp_after_saving_data', array( $this, 'register_countdown_strings' ) );
		add_action( 'yaydp_after_saving_data', array( $this, 'register_offer_description_strings' ) );
		add_action( 'yaydp_after_saving_data', array( $this, 'register_tooltip_strings' ) );
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
	 * Registers countdown start/end text strings with WPML String Translation
	 * so translators can provide per-language versions via the WPML UI.
	 *
	 * @param array $body Saved data from the REST save_page_data request.
	 */
	public function register_countdown_strings( $body ) {
		$settings   = $body['settings'] ?? array();
		$rule_types = array( 'product_pricing', 'cart_discount', 'checkout_fee' );

		foreach ( $rule_types as $type ) {
			$countdown = $settings[ $type ]['countdown_timer'] ?? array();

			if ( ! empty( $countdown['start_text'] ) ) {
				do_action( 'wpml_register_single_string', 'yaypricing', "{$type}_start_text", $countdown['start_text'] );
			}
			if ( ! empty( $countdown['end_text'] ) ) {
				do_action( 'wpml_register_single_string', 'yaypricing', "{$type}_end_text", $countdown['end_text'] );
			}
		}
	}

	/**
	 * Registers the offer description texts of product pricing rules with WPML String Translation
	 * so translators can provide per-language versions via the WPML UI.
	 *
	 * @param array $body Saved data from the REST save_page_data request.
	 */
	public function register_offer_description_strings( $body ) {
		$rules = $body['rules']['product_pricing'] ?? array();

		foreach ( $rules as $rule ) {
			$rule_id = $rule['id'] ?? '';
			if ( empty( $rule_id ) ) {
				continue;
			}
			foreach ( array( 'buy_product_description', 'get_product_description' ) as $field ) {
				$content = $rule['offer_description'][ $field ] ?? '';
				if ( empty( $content ) ) {
					continue;
				}
				do_action( 'wpml_register_single_string', 'yaypricing', "rule_{$rule_id}_{$field}", $content );
			}
		}
	}

	/**
	 * Registers the tooltip contents and names of every rule type with WPML String Translation
	 * so translators can provide per-language versions via the WPML UI.
	 *
	 * @param array $body Saved data from the REST save_page_data request.
	 */
	public function register_tooltip_strings( $body ) {
		foreach ( array( 'product_pricing', 'cart_discount', 'checkout_fee' ) as $type ) {
			$rules = $body['rules'][ $type ] ?? array();

			foreach ( $rules as $rule ) {
				$rule_id = $rule['id'] ?? '';
				if ( empty( $rule_id ) ) {
					continue;
				}
				$content = $rule['tooltip']['content'] ?? '';
				if ( ! empty( $content ) ) {
					do_action( 'wpml_register_single_string', 'yaypricing', "rule_{$rule_id}_tooltip_content", $content );
				}
				$name = $rule['name'] ?? '';
				if ( ! empty( $name ) ) {
					do_action( 'wpml_register_single_string', 'yaypricing', "rule_{$rule_id}_name", $name );
				}
			}
		}
	}

}
