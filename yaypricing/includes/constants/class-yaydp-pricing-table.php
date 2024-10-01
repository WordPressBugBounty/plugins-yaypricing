<?php
/**
 * Defines the constants for pricing table settings
 *
 * @package YayPricing\Constants
 */

namespace YAYDP\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Pricing_Table {

	/**
	 * Get default settings
	 */
	public static function get_default() {
		return array(
			'position'       => 'before_add_to_cart_button',
			'table_title'    => 'Quantity discounts',
			'quantity_title' => 'Quantity',
			'discount_title' => 'Discount',
			'price_title'    => 'Price',
			'border_color'   => '#e6e6e6',
			'border_style'   => 'solid',
		);
	}

	/**
	 * Get sample pricing table data
	 */
	public static function get_sample_data() {
		return array(
			array( '3-6', '30%', \wc_price( 10.5 ) ),
			array( '7-12', '50%', \wc_price( 7.5 ) ),
			array( '13-Unlimited', '70%', \wc_price( 4.5 ) ),
		);
	}
}
