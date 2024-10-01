<?php
/**
 * Manage Encouragement
 *
 * @package YayPricing\Abstracts
 *
 * @since 2.4
 */

namespace YAYDP\Abstracts;

/**
 * Declare class
 */
abstract class YAYDP_Encouragement {

	/**
	 * Get encouraged notice content
	 */
	abstract public function get_content();

	/**
	 * Get encouraged notice raw content ( before replacing variables )
	 */
	abstract public function get_raw_content();
}
