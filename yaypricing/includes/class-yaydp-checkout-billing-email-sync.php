<?php
/**
 * Syncs the billing email typed at classic checkout into the session customer.
 *
 * WooCommerce's update_order_review AJAX handler copies posted billing address
 * fields onto WC()->customer but not the billing email (only apply_coupon does),
 * so conditions relying on WC()->customer->get_billing_email() — e.g.
 * billing_email_order_count — would never match for guests at checkout.
 * This listener fills that gap before cart totals are recalculated.
 *
 * @package YayPricing\Classes
 * @since 3.5.8
 */

namespace YAYDP;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Checkout_Billing_Email_Sync {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'sync_billing_email' ) );
	}

	/**
	 * Extracts billing_email from the posted checkout form data and sets it
	 * on the session customer, so it is available when totals recalculate.
	 *
	 * @param string $post_data URL-encoded checkout form data.
	 */
	public function sync_billing_email( $post_data ) {
		try {
			if ( empty( $post_data ) || ! function_exists( 'WC' ) || empty( \WC()->customer ) ) {
				return;
			}
			parse_str( (string) $post_data, $data );
			if ( empty( $data['billing_email'] ) || ! is_string( $data['billing_email'] ) ) {
				return;
			}
			$billing_email = \sanitize_email( $data['billing_email'] );
			if ( empty( $billing_email ) || ! \is_email( $billing_email ) ) {
				return;
			}
			\WC()->customer->set_billing_email( $billing_email );
		} catch ( \Exception $e ) {
			return;
		}
	}
}

new YAYDP_Checkout_Billing_Email_Sync();
