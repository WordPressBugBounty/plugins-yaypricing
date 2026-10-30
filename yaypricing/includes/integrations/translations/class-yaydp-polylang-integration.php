<?php
/**
 * Polylang string translation integration for countdown timer texts
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Translations;

use YAYDP\Traits\YAYDP_Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers admin-entered countdown texts with Polylang so translators
 * can provide per-language versions via the Polylang Strings Translations screen.
 */
class YAYDP_Polylang_Integration {

	use YAYDP_Singleton;

	protected function __construct() {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}
		add_action( 'yaydp_after_saving_data', array( $this, 'register_countdown_strings' ) );
		add_action( 'yaydp_after_saving_data', array( $this, 'register_offer_description_strings' ) );
		add_action( 'yaydp_after_saving_data', array( $this, 'register_tooltip_strings' ) );
		add_action( 'yaydp_after_saving_data', array( $this, 'register_combined_name_string' ) );
		add_action( 'yaydp_after_saving_data', array( $this, 'register_pricing_table_strings' ) );
		add_filter( 'yaydp_translated_list_object_id', array( __CLASS__, 'get_list_translation_object_id' ), 10, 2 );
	}

	/**
	 * Registers the pricing table titles with Polylang.
	 * Triggered when admin saves settings so new text is immediately available for translation.
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
			pll_register_string( "pricing_table_{$key}", $title, 'yaypricing', true );
		}
	}

	/**
	 * Expands stored ids with their translations in every Polylang language,
	 * so a rule set up once matches the translated products and terms too.
	 *
	 * @param array  $list        Stored ids.
	 * @param string $object_type product | product_cat | product_tag ...
	 * @return array
	 */
	public static function get_list_translation_object_id( $list, $object_type = 'post' ) {
		$is_term = taxonomy_exists( $object_type );
		if ( ! function_exists( $is_term ? 'pll_get_term_translations' : 'pll_get_post_translations' ) ) {
			return $list;
		}

		$translated_list = array();
		foreach ( $list as $id ) {
			$translated_list[] = $id;
			$translations      = $is_term ? pll_get_term_translations( $id ) : pll_get_post_translations( $id );
			if ( is_array( $translations ) ) {
				$translated_list = array_merge( $translated_list, array_values( $translations ) );
			}
		}
		return array_values( array_unique( $translated_list ) );
	}

	/**
	 * Registers the combined discount name with Polylang.
	 * Triggered when admin saves settings so new text is immediately available for translation.
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
			pll_register_string( 'combined_discount_name', $name, 'yaypricing', true );
		}
	}

	/**
	 * Registers countdown start/end text strings with Polylang.
	 * Triggered when admin saves settings so new text is immediately available for translation.
	 *
	 * @param array $body Saved data from the REST save_page_data request.
	 */
	public function register_countdown_strings( $body ) {
		$settings   = $body['settings'] ?? array();
		$rule_types = array( 'product_pricing', 'cart_discount', 'checkout_fee' );

		foreach ( $rule_types as $type ) {
			$countdown = $settings[ $type ]['countdown_timer'] ?? array();

			if ( ! empty( $countdown['start_text'] ) ) {
				pll_register_string( "{$type}_countdown_start_text", $countdown['start_text'], 'yaypricing', true );
			}
			if ( ! empty( $countdown['end_text'] ) ) {
				pll_register_string( "{$type}_countdown_end_text", $countdown['end_text'], 'yaypricing', true );
			}
		}
	}

	/**
	 * Registers the offer description texts of product pricing rules with Polylang.
	 * Triggered when admin saves settings so new text is immediately available for translation.
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
				pll_register_string( "rule_{$rule_id}_{$field}", $content, 'yaypricing', true );
			}
		}
	}

	/**
	 * Registers the tooltip contents and names of every rule type with Polylang.
	 * Triggered when admin saves settings so new text is immediately available for translation.
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
					pll_register_string( "rule_{$rule_id}_tooltip_content", $content, 'yaypricing', true );
				}
				$name = $rule['name'] ?? '';
				if ( ! empty( $name ) ) {
					pll_register_string( "rule_{$rule_id}_name", $name, 'yaypricing', true );
				}
			}
		}
	}
}
