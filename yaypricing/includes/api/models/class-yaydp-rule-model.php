<?php
/**
 * This class represents the model for the YAYDP rule
 *
 * @package YayPricing\Models
 */

namespace YAYDP\API\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Rule_Model {

	/**
	 * Get all rules in database
	 */
	public static function get_all() {
		$rules = array(
			'product_pricing' => get_option( 'yaydp_product_pricing_rules', array() ),
			'cart_discount'   => get_option( 'yaydp_cart_discount_rules', array() ),
			'checkout_fee'    => get_option( 'yaydp_checkout_fee_rules', array() ),
			'exclude'         => get_option( 'yaydp_exclude_rules', array() ),
		);
		return $rules;
	}

}
