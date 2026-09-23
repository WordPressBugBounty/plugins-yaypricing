<?php
/**
 * Used to handle asynchronous requests and responses between the client and server.
 *
 * The free edition registers no frontend AJAX endpoints; the class stays so the
 * pricing manager bootstrap is identical to the pro edition.
 *
 * @package YayPricing\Ajax
 */

namespace YAYDP;

/**
 * YAYDP_Ajax class
 */
class YAYDP_Ajax {

	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
	}
}
