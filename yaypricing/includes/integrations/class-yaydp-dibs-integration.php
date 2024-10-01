<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_DIBS_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {

		add_filter(
			'dibs_easy_create_order_args',
			function( $request_args ) {
				if ( ! isset( $request_args['order']['items'] ) ) {
					return $request_args;
				}
				$gross = 0;
				foreach ( $request_args['order']['items'] as $item ) {
					$gross += ( $item['grossTotalAmount'] ?? 0 );
				}
				$amount = $request_args['order']['amount'] ?? 0;

				if ( abs( $amount - $gross ) <= 1 ) {
					$request_args['order']['amount'] = $gross;
				}
				return $request_args;
			},
			1000
		);
	}

}
