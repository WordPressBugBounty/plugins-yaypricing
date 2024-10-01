<?php
/**
 * Abstract class for managing the use time
 *
 * @package YayPricing\UseTime
 *
 * @since 2.4
 */

namespace YAYDP\Abstracts;

/**
 * Declare class
 */
abstract class YAYDP_Use_Time {

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'woocommerce_before_checkout_process', array( $this, 'before_checkout_process' ), 10 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ), 10, 1 );
		add_filter( 'woocommerce_payment_successful_result', array( $this, 'after_payment_successful' ), 10, 2 );
		add_filter( 'woocommerce_checkout_no_payment_needed_redirect', array( $this, 'checkout_no_payment_needed_redirect' ), 10, 2 );
	}

	/**
	 * This function is called before the checkout process begins.
	 * It must be implemented by child class.
	 */
	abstract public function before_checkout_process();

	/**
	 * This function is responsible for processing the order after it has been checked out.
	 * It must be implemented by child class.
	 *
	 * @param int $order_id Given order id.
	 */
	abstract public function checkout_order_processed( $order_id );

	/**
	 * This function is called after a payment is successfully processed.
	 * It can be used to perform any additional actions or updates that need to be made to the system after a payment is completed.
	 * It must be implemented by child class.
	 *
	 * @param array $result Result of the payment.
	 * @param int   $order_id Given order id.
	 */
	abstract public function after_payment_successful( $result, $order_id );

	/**
	 * This function is responsible for redirecting the user to the appropriate page after a checkout process has been completed without any payment required.
	 * It must be implemented by child class.
	 *
	 * @param string    $url Result url.
	 * @param \WC_Order $order Given order.
	 */
	abstract public function checkout_no_payment_needed_redirect( $url, $order );
}
