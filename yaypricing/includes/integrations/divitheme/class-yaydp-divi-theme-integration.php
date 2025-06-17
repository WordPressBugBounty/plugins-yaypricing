<?php
/**
 * Handles the integration of Divi theme with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\DiviTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Divi_Theme_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_filter( 'et_pb_module_content', array( $this, 'apply_yaydp_rules' ), 100, 4 );
	}

	public function apply_yaydp_rules( $content, $module, $attrs, $render_slug ) {
		if ( 'et_pb_wc_cart_products' === $render_slug ) {
			do_action( 'woocommerce_before_calculate_totals', \WC()->cart );
		}
		return $content;
	}
}