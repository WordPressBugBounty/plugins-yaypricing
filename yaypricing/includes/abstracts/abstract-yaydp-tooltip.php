<?php
/**
 * Abstract class for manage tooltips
 *
 * @package YayPricing\Tooltip
 *
 * @since 2.4
 */

namespace YAYDP\Abstracts;

/**
 * Declare class
 */
abstract class YAYDP_Tooltip {

	/**
	 * Contains tooltip data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Contains modifier that create this tooltip
	 * It can be \YAYDP\Core\Single_Modifier
	 * It can be \YAYDP\Abstract\Rule
	 */
	protected $modifier = null;

	/**
	 * Constructor
	 *
	 * @param array $data Tooltip data.
	 */
	public function __construct( $data, $modifier ) {
		$this->data     = ! empty( $data ) ? $data : array();
		$this->modifier = ! empty( $modifier ) ? $modifier : null;
	}

	/**
	 * Retrieve tooltip data
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Retrieve tooltip modifier
	 */
	public function get_modifier() {
		return $this->modifier;
	}

	/**
	 * Check whether this tooltip is enabled
	 */
	public function is_enabled() {
		return empty( $this->data['enable'] ) ? false : $this->data['enable'];
	}

	/**
	 * Retrieve tooltip raw content
	 * This content has not replaced variables yet
	 */
	public function get_raw_content() {
		return empty( $this->data['content'] ) ? '' : $this->data['content'];
	}

	/**
	 * Abstract function for getting tooltip content ( replaced variables )
	 * It must be implemented by child class.
	 */
	abstract public function get_content();
}
