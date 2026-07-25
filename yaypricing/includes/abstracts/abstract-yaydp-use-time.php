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
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'handle_store_api_order_processed' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ), 10, 1 );
	}

	/**
	 * Bridge for the Block (Store API) checkout, which passes an order object
	 * instead of an order id. Normalizes to id and delegates to checkout_order_processed.
	 *
	 * @param \WC_Order $order Given order.
	 */
	public function handle_store_api_order_processed( $order ) {
		if ( $order instanceof \WC_Order ) {
			$this->checkout_order_processed( $order->get_id() );
		}
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
	 * Called when an order transitions to the completed status.
	 * Implementations should increment use_time on rules applied to this order.
	 * It must be implemented by child class.
	 *
	 * @param int $order_id Given order id.
	 */
	abstract public function on_order_completed( $order_id );
}
