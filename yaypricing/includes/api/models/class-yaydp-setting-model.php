<?php
/**
 * This class represents the model for the YAYDP settings
 *
 * @package YayPricing\Models
 */

namespace YAYDP\API\Models;

/**
 * Declare class
 */
class YAYDP_Setting_Model {

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public static function get_all() {
		$settings = get_option( 'yaydp_core_settings' );
		if ( empty( $settings ) ) {
			$settings = array(
				'product_pricing' => array(
					'disable_when_on_sale'               => false,
					'discount_base_on'                   => 'regular_price',
					'show_regular_price'                 => true,
					'show_original_subtotal_price'       => true,
					'show_sale_tag'                      => true,
					'show_discounted_price'              => false,
					'show_order_saving_amount'           => false,
					'order_saving_amount_position'       => 'after_order_total',
					'show_discounted_with_regular_price' => true,
					'how_to_apply'                       => 'all',
					'countdown_timer'                    => \YAYDP\Constants\YAYDP_Countdown_Timer::get_default(),
					'encouraged_notice'                  => \YAYDP\Constants\YAYDP_Encouraged_Notice::get_default( 'product_pricing' ),
					'pricing_table'                      => \YAYDP\Constants\YAYDP_Pricing_Table::get_default(),
					'on_sale_products'                   => array(
						'rules' => array(),
					),
				),
				'cart_discount'   => array(
					'is_combined'       => false,
					'use_id_as_code'    => false,
					'how_to_apply'      => 'all',
					'countdown_timer'   => \YAYDP\Constants\YAYDP_Countdown_Timer::get_default(),
					'encouraged_notice' => \YAYDP\Constants\YAYDP_Encouraged_Notice::get_default( 'cart_discount' ),
				),
				'checkout_fee'    => array(
					'how_to_apply'      => 'all',
					'countdown_timer'   => \YAYDP\Constants\YAYDP_Countdown_Timer::get_default(),
					'encouraged_notice' => \YAYDP\Constants\YAYDP_Encouraged_Notice::get_default( 'checkout_fee' ),
					'include_tax'       => false,
				),
				'general'         => array(
					'sync_with_coupon_individual_use_only' => true,
					'show_original_price_and_saved_amount' => false,
				),
			);
		}
		return $settings;
	}
}
