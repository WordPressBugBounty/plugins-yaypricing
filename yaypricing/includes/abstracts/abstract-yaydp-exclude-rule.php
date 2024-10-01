<?php
/**
 * Class represents a exclude rule for YAYDP.
 * It contains methods for setting and getting the rule's properties
 *
 * @package YayPricing\Abstract
 */

namespace YAYDP\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
abstract class YAYDP_Exclude_Rule extends YAYDP_Rule {

	/**
	 * Return excluded list
	 */
	public function get_excluded_list() {
		return ! empty( $this->data['excluded_rules'] ) ? $this->data['excluded_rules'] : array();
	}

}
