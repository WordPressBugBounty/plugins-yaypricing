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
			'columns_order'        => array(
				'quantity_title',
				'discount_title',
				'price_title',
			),
			'border_color'   => '#e6e6e6',
			'border_style'   => 'solid',
			'layout'         => 'table',
		);
	}

	/**
	 * Get the pricing table layouts, the single source of valid "layout" values
	 *
	 * @since 3.6.2
	 */
	public static function get_layouts() {
		return array( 'table', 'options', 'blocks', 'dropdown', 'tooltip' );
	}

	/**
	 * Get sample pricing table data
	 */
	public static function get_sample_data() {
		return array(
			// Quantity, discount, price, then (for the non-table layouts) tier label, regular price, saving percent and the total of 8 items.
			array( '3-6', '30%', \wc_price( 10.5 ), '3 - 6', \wc_price( 15 ), 30, \wc_price( 84 ) ),
			array( '7-12', '50%', \wc_price( 7.5 ), '7 - 12', \wc_price( 15 ), 50, \wc_price( 60 ) ),
			array( '13-Unlimited', '70%', \wc_price( 4.5 ), '13+', \wc_price( 15 ), 70, \wc_price( 36 ) ),
		);
	}
}
