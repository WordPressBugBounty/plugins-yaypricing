<?php
/**
 * Handles the integration of Custom Post Type UI plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Woocommerce_Subscriptions_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return;
		}
		add_action( 'yaydp_register_conditions', array( $this, 'register_conditions' ) );
		add_action( 'yaydp_register_product_filters', array( $this, 'register_product_filters' ) );
	}

	/**
	 * Register this integration's condition types.
	 *
	 * @param \YAYDP\Condition\YAYDP_Condition_Registry $registry Registry.
	 */
	public function register_conditions( $registry ) {
		$registry->register( new YAYDP_Has_Switch_Subscription_Condition() );
	}

	/**
	 * Register the subscription status filter.
	 *
	 * @param \YAYDP\Product_Filter\YAYDP_Product_Filter_Registry $registry Registry.
	 */
	public function register_product_filters( $registry ) {
		$registry->register( new YAYDP_Subscription_Status_Product_Filter() );
	}
}
