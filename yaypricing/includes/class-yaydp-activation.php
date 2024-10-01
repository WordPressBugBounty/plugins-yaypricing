<?php
/**
 * Do stuff when activate plugin
 *
 * @package YayPricing\Installations
 */

namespace YAYDP;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Activation class
 */
class YAYDP_Activation {

	/**
	 * Initialize function
	 */
	public static function initialize() {
		self::convert_old_data();
	}

	/**
	 * Convert old data to new data.
	 */
	public static function convert_old_data() {
		$old_rules = get_option( 'yaydp_rules', false );
		if ( false === $old_rules || false !== get_option( 'yaydp_product_pricing_rules' ) ) {
			return;
		}
		$old_rules             = get_option( 'yaydp_rules' );
		$product_pricing_rules = array();
		$cart_discount_rules   = array();
		$checkout_fee_rules    = array();
		$exclude_rules         = array();
		try {
			if ( isset( $old_rules['product_pricing']['rules'] ) ) {
				$product_pricing_rules = array_map(
					function( $item ) {
						return array(
							'id'                => $item['id'],
							'rule_id'           => $item['rule_id'],
							'name'              => $item['name'],
							'is_enabled'        => false,
							'type'              => isset( $item['type']['value'] ) ? $item['type']['value'] : 'simple_adjustment',
							'pricing'           => array(
								'item_get_type' => 'discount',
								'buy_quantity'  => 1,
								'get_quantity'  => 1,
								'type'          => isset( $item['pricing_type']['value'] ) ? $item['pricing_type']['value'] : 'fixed_discount',
								'value'         => $item['pricing_value'],
								'maximum_value' => ! empty( $item['maximum_value'] ) ? $item['maximum_value'] : null,
								'repeat'        => true,
							),
							'pricing_ranges'    => array_map(
								function( $range ) {
									return array(
										'from_quantity' => empty( $range['from_quantity'] ? 0 : $range['from_quantity'] ),
										'to_quantity'   => empty( $range['to_quantity'] ? null : $range['to_quantity'] ),
										'pricing'       => array(
											'value' => $range['pricing_value'],
											'type'  => isset( $range['pricing_type']['value'] ) ? $range['pricing_type']['value'] : 'fixed_discount',
											'maximum_value' => null,
										),
									);
								},
								$item['ranges']
							),
							'buy_products'      => array(
								'match_type' => 'any',
								'filters'    => array(),
							),
							'get_products'      => array(
								'match_type' => 'any',
								'filters'    => array(),
							),
							'conditions'        => array(
								'match_type' => 'any',
								'logics'     => array(),
							),
							'tooltip'           => array(
								'enable'  => '1' === $item['show_tooltip_on_cart'] ? true : false,
								'content' => isset( $item['tooltip_cart_text'] ) ? $item['tooltip_cart_text'] : '',
							),
							'offer_description' => array(
								'enable'                  => '1' === $item['show_offer_description'] ? true : false,
								'buy_product_description' => isset( $item['description_on_single_page'] ) ? $item['description_on_single_page'] : '',
								'get_product_description' => isset( $item['description_get_products_on_single_page'] ) ? $item['description_get_products_on_single_page'] : '',
							),
							'schedule'          => array(
								'enable' => '1' === $item['have_schedule'] ? true : false,
								'start'  => empty( $item['schedule_start_rule'] ) ? null : $item['schedule_start_rule'],
								'end'    => empty( $item['schedule_end_rule'] ) ? null : $item['schedule_end_rule'],
							),
							'maximum_uses'      => array(
								'enable' => false,
								'value'  => 1,
							),
							'use_time'          => 0,
						);
					},
					$old_rules['product_pricing']['rules']
				);
			}

			if ( isset( $old_rules['cart_discount']['rules'] ) ) {
				$cart_discount_rules = array_map(
					function( $item ) {
						return array(
							'id'           => $item['id'],
							'name'         => $item['name'],
							'is_enabled'   => false,
							'pricing'      => array(
								'type'          => isset( $item['pricing_type']['value'] ) ? $item['pricing_type']['value'] : 'fixed_discount',
								'value'         => $item['pricing_value'],
								'maximum_value' => ! empty( $item['maximum_value'] ) ? $item['maximum_value'] : null,
							),
							'conditions'   => array(
								'match_type' => 'any',
								'logics'     => array(),
							),
							'tooltip'      => array(
								'enable'  => '1' === $item['show_tooltip_on_cart'] ? true : false,
								'content' => isset( $item['tooltip_cart_text'] ) ? $item['tooltip_cart_text'] : '',
							),
							'schedule'     => array(
								'enable' => '1' === $item['have_schedule'] ? true : false,
								'start'  => empty( $item['schedule_start_rule'] ) ? null : $item['schedule_start_rule'],
								'end'    => empty( $item['schedule_end_rule'] ) ? null : $item['schedule_end_rule'],
							),
							'maximum_uses' => array(
								'enable' => false,
								'value'  => 1,
							),
							'use_time'     => 0,
						);
					},
					$old_rules['cart_discount']['rules']
				);
			}

			if ( isset( $old_rules['checkout_fee']['rules'] ) ) {
				$checkout_fee_rules = array_map(
					function( $item ) {
						return array(
							'id'           => $item['id'],
							'name'         => $item['name'],
							'is_enabled'   => false,
							'type'         => isset( $item['type']['value'] ) ? $item['type']['value'] : 'shipping_fee',
							'pricing'      => array(
								'type'          => isset( $item['pricing_type']['value'] ) ? $item['pricing_type']['value'] : 'fixed_discount',
								'value'         => $item['pricing_value'],
								'maximum_value' => ! empty( $item['maximum_value'] ) ? $item['maximum_value'] : null,
							),
							'conditions'   => array(
								'match_type' => 'any',
								'logics'     => array(),
							),
							'tooltip'      => array(
								'enable'  => '1' === $item['show_tooltip_on_cart'] ? true : false,
								'content' => isset( $item['tooltip_cart_text'] ) ? $item['tooltip_cart_text'] : '',
							),
							'schedule'     => array(
								'enable' => '1' === $item['have_schedule'] ? true : false,
								'start'  => empty( $item['schedule_start_rule'] ) ? null : $item['schedule_start_rule'],
								'end'    => empty( $item['schedule_end_rule'] ) ? null : $item['schedule_end_rule'],
							),
							'maximum_uses' => array(
								'enable' => false,
								'value'  => 1,
							),
							'use_time'     => 0,
						);
					},
					$old_rules['checkout_fee']['rules']
				);
			}
		} catch ( \Error $error ) {
			\YAYDP\YAYDP_Logger::log_exception_message( $error );
		} catch ( \Exception $exception ) {
			\YAYDP\YAYDP_Logger::log_exception_message( $exception );
		}

		update_option( 'yaydp_product_pricing_rules', $product_pricing_rules );
		update_option( 'yaydp_cart_discount_rules', $cart_discount_rules );
		update_option( 'yaydp_checkout_fee_rules', $checkout_fee_rules );
		update_option( 'yaydp_exclude_rules', $exclude_rules );

	}

}
