<?php
/**
 * The YAYDP_Adjustment abstract class is an abstract class that defines the basic structure and functionality for adjusting the price
 *
 * @package YayPricing\Abstracts
 *
 * @since 2.4
 */

namespace YAYDP\Abstracts;

/**
 * Declare class
 */
abstract class YAYDP_Adjustment {

	/**
	 * Contains rule
	 */
	protected $rule = null;

	/**
	 * Constructor
	 *
	 * @param array $data Given data.
	 */
	public function __construct( $data ) {
		$this->rule = $data['rule'];
	}

	/**
	 * Retrieves rule
	 */
	public function get_rule() {
		return $this->rule;
	}

	/**
	 * Calculate total discount amount that the rule can affect per order.
	 * It must be implemented by the child class.
	 */
	abstract public function get_total_discount_amount_per_order();

	/**
	 * Check conditions of the current adjustment after other adjustments are applied
	 * It must be implemented by the child class.
	 */
	abstract public function check_conditions();
}
