<?php
/**
 * Abstract factory class
 *
 * @package YayPricing\Factory
 */

namespace YAYDP\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
abstract class YAYDP_Rule_Factory {

	/**
	 * Get rule instance based on given data
	 *
	 * @param array $rule_data Given data.
	 */
	abstract public static function get_rule( $rule_data );

}
