<?php
/**
 * Handle YayPricing Modifier
 * It can be a cart modifier, item modifier,...
 *
 * @package YayPricing\Abstracts
 */

namespace YAYDP\Abstracts;

/**
 * Declare class
 */
abstract class YAYDP_Modifier {

	/**
	 * Contains the rule that create this modifier
	 */
	protected $rule = null;

	/**
	 * Constructor
	 *
	 * @param array $data Given data.
	 */
	public function __construct( $data ) {
		$this->rule = isset( $data['rule'] ) ? $data['rule'] : null;
	}

	/**
	 * Returns rule
	 */
	public function get_rule() {
		return $this->rule;
	}
}
