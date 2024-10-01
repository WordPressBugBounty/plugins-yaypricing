<?php
/**
 * Defines the constants for encouraged notice settings
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
class YAYDP_Encouraged_Notice {

	/**
	 * Get default settings
	 *
	 * @param string $type Given type.
	 */
	public static function get_default( $type = 'product_pricing' ) {
		switch ( $type ) {
			case 'product_pricing':
				$text = 'You will get [discount_value] off [current_item] when [action]';
				break;
			case 'cart_discount':
				$text = 'You will get [discount_value] off cart total when [action]';
				break;
			case 'checkout_fee':
				$text = 'You will get [discount_value] off shipping fee when [action]';
				break;

			default:
				$text = '';
				break;
		}
		return array(
			'enable' => false,
			'text'   => $text,
		);
	}
}
