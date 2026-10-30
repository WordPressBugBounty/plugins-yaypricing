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
		add_action( 'yaydp_after_saving_data', array( $this, 'register_combined_name_string' ) );
		add_action( 'yaydp_after_saving_data', array( $this, 'register_pricing_table_strings' ) );
	}

	/**
	 * Registers the pricing table titles with WPML String Translation so
	 * translators can provide per-language versions via the WPML UI.
	 *
	 * Default titles are skipped; they are translated through the text domain.
	 *
	 * @param array $body Saved data from the REST save_page_data request.
	 */
	public function register_pricing_table_strings( $body ) {
		$table    = $body['settings']['product_pricing']['pricing_table'] ?? array();
		$defaults = \YAYDP\Constants\YAYDP_Pricing_Table::get_default();

		foreach ( array( 'table_title', 'quantity_title', 'discount_title', 'price_title' ) as $key ) {
			$title = $table[ $key ] ?? '';
			if ( empty( $title ) || ! is_string( $title ) || $defaults[ $key ] === $title ) {
				continue;
			}
			do_action( 'wpml_register_single_string', 'yaypricing', "pricing_table_{$key}", $title );
		}
	}

	/**
	 * Registers the combined discount name with WPML String Translation so
	 * translators can provide per-language versions via the WPML UI.
	 *
	 * Only the displayed label is translated; the coupon code keeps using the
	 * raw name, so it stays identical in every language.
	 *
	 * @param array $body Saved data from the REST save_page_data request.
	 */
	public function register_combined_name_string( $body ) {
		// Sanitised to match what get_coupon_name() looks up; Polylang keys by value.
		$name = sanitize_text_field( $body['settings']['cart_discount']['combined_name'] ?? '' );

		if ( ! empty( $name ) ) {
			do_action( 'wpml_register_single_string', 'yaypricing', 'combined_discount_name', $name );
		}
	}

	public static function get_translation_object_id( $object_id, $object_type = 'post' ) {
		$current_language = apply_filters( 'wpml_current_language', null );
		return apply_filters( 'wpml_object_id', $object_id, $object_type, true, $current_language );
	}

	/**
	 * Expands stored ids with their translations in every active language.
	 *
	 * Matching only the current-language translation breaks whenever WPML's
	 * current language differs from the cart's (wc-ajax / Store API requests,
	 * rules saved while the admin was in another language), so a rule set up
	 * once applies to every translation of its products and terms.
	 *
	 * @param array  $list        Stored ids.
	 * @param string $object_type WPML element type: product | product_cat | product_tag ...
	 * @return array
	 */
	public static function get_list_translation_object_id( $list, $object_type = 'post' ) {
		$languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
		$codes     = is_array( $languages ) ? array_keys( $languages ) : array();

		$translated_list = array();
		foreach ( $list as $id ) {
			$translated_list[] = $id;
			$translated_list[] = self::get_translation_object_id( $id, $object_type );
			foreach ( $codes as $code ) {
				$translated_id = apply_filters( 'wpml_object_id', $id, $object_type, false, $code );
				if ( ! empty( $translated_id ) ) {
					$translated_list[] = $translated_id;
				}
			}
		}
		return array_values( array_unique( $translated_list ) );
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
