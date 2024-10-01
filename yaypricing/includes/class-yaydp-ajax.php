<?php
/**
 * Used to handle asynchronous requests and responses between the client and server.
 *
 * @package YayPricing\Ajax
 */

namespace YAYDP;

/**
 * YAYDP_Ajax class
 */
class YAYDP_Ajax {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'wp_ajax_yaydp-update-encouraged-notice', array( $this, 'update_encouraged_notice' ) );
		add_action( 'wp_ajax_nopriv_yaydp-update-encouraged-notice', array( $this, 'update_encouraged_notice' ) );
	}

	/**
	 * This function is responsible for displaying a notice to the user encouraging customer to buy more
	 */
	public function update_encouraged_notice() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'yaydp_frontend_nonce' ) ) {
			return wp_send_json_error( array( 'mess' => __( 'Verify nonce failed', 'yaypricing' ) ) );

		}
		try {
			ob_start();
			do_action( 'yaydp_bottom_product_pricing_encouraged_section' );
			do_action( 'yaydp_bottom_cart_discount_encouraged_section' );
			do_action( 'yaydp_bottom_checkout_fee_encouraged_section' );
			$html = ob_get_contents();
			ob_end_clean();
			wp_send_json_success(
				array(
					'notices_html' => $html,
				)
			);
		} catch ( \Error $error ) {
			\YAYDP\YAYDP_Logger::log_exception_message( $error, true );
		} catch ( \Exception $exception ) {
			\YAYDP\YAYDP_Logger::log_exception_message( $exception, true );
		}

	}
}
