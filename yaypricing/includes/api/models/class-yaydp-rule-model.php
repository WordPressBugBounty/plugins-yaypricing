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
			'product_pricing' => self::normalize_rules( get_option( 'yaydp_product_pricing_rules', array() ) ),
			'cart_discount'   => self::normalize_rules( get_option( 'yaydp_cart_discount_rules', array() ) ),
			'checkout_fee'    => self::normalize_rules( get_option( 'yaydp_checkout_fee_rules', array() ) ),
			'exclude'         => self::normalize_rules( get_option( 'yaydp_exclude_rules', array() ) ),
		);
		return $rules;
	}

	/**
	 * Normalize a stored rules option into a sequentially-indexed array.
	 *
	 * Options that were saved with non-sequential keys (e.g. after a rule was
	 * removed) get encoded by json_encode as a JS object instead of an array,
	 * which breaks `.filter()` on the front-end. Re-indexing guarantees a JSON
	 * array is always returned.
	 *
	 * @param mixed $rules Stored option value.
	 * @return array
	 */
	private static function normalize_rules( $rules ) {
		return is_array( $rules ) ? array_values( $rules ) : array();
	}

}
