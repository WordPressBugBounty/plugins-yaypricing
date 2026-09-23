<?php
/**
 * The YAYDP_Shortcode_Handler class handles the shortcodes used by the YAYDP plugin
 *
 * The free edition ships no shortcodes; the singleton stays so the pricing
 * manager bootstrap is identical to the pro edition.
 *
 * @since 2.4
 *
 * @package YayPricing\Shortcode
 */

namespace YAYDP\Core\Shortcode;

/**
 * Declare
 */
class YAYDP_Shortcode_Handler {

	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
	}
}
