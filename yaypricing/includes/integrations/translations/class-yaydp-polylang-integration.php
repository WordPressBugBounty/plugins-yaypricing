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
