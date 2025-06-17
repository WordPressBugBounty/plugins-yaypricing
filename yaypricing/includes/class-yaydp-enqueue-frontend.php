<?php
/**
 * Enqueue scripts and styles in frontend pages
 *
 * @package YayPricing\Classes
 * @version 1.0.0
 */

namespace YAYDP;

defined( 'ABSPATH' ) || exit;

/**
 * Class YAYDP_Enqueue_Frontend
 */
class YAYDP_Enqueue_Frontend {

	/**
	 * Constructor for the class. Load data when class initialize
	 */
	public function __construct() {
		if ( \yaydp_is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		/**
		 * Enqueue WP dashicon
		 */
		wp_enqueue_style( 'dashicons' );

		/**
		 * Script handle change variation
		 */
		if ( \is_product() ) {
			$this->enqueue_script( 'variation-selection', 'variation-selection.js', array( 'jquery' ) );
			$this->enqueue_style( 'pricing-table', 'pricing-table.css' );
			$this->enqueue_script( 'pricing-table', 'pricing-table.js', array( 'jquery' ) );
		}

		if ( $this->has_payment_condition() ) {
			$this->enqueue_script( 'payment', 'payment.js', array( 'jquery' ) );
		}

		if ( $this->has_shipping_condition() ) {
			$this->enqueue_script( 'shipping', 'shipping.js', array( 'jquery' ) );
		}

		/**
		 * Main script
		 */
		$this->enqueue_script( 'index', 'index.js', array( 'jquery' ) );
		$this->enqueue_style( 'index', 'index.css' );
		wp_localize_script(
			'yaydp-frontend-index',
			'yaydp_frontend_data',
			array(
				'nonce'             => wp_create_nonce( 'yaydp_frontend_nonce' ),
				'admin_ajax'        => admin_url( 'admin-ajax.php' ),
				'current_page'      => \yaydp_current_frontend_page(),
				'discount_based_on' => \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->get_discount_base_on(),
				'currency_settings' => \Automattic\WooCommerce\Internal\Admin\Settings::get_currency_settings(),
			)
		);
	}

	/**
	 * Enqueue javascript
	 *
	 * @param string $key Handle key.
	 * @param string $src File path.
	 * @param array  $dependences Dependences.
	 */
	public function enqueue_script( $key, $src, $dependences = array() ) {
		wp_enqueue_script(
			"yaydp-frontend-$key",
			YAYDP_PLUGIN_URL . "assets/js/$src",
			$dependences,
			YAYDP_VERSION,
			true
		);
		wp_enqueue_script( 'accounting' );
	}

	/**
	 * Enqueue css
	 *
	 * @param string $key Handle key.
	 * @param string $src File path.
	 * @param array  $dependences Dependences.
	 */
	public function enqueue_style( $key, $src, $dependences = array() ) {
		wp_enqueue_style(
			"yaydp-frontend-$key",
			YAYDP_PLUGIN_URL . "assets/css/$src",
			$dependences,
			YAYDP_VERSION
		);

	}

	public function has_payment_condition() {
		$product_pricing_rules = \yaydp_get_running_product_pricing_rules();
		$cart_discount_rules   = \yaydp_get_running_cart_discount_rules();
		$checkout_fee_rules    = \yaydp_get_running_checkout_fee_rules();
		$rules                 = array_merge( $product_pricing_rules, $cart_discount_rules, $checkout_fee_rules );
		foreach ( $rules as $rule ) {
			$conditions = $rule->get_conditions();
			foreach ( $conditions as $condition ) {
				if ( 'payment_method' === $condition['type'] ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if there is a shipping condition
	 *
	 * @return boolean
	 * @since 3.5.2
	 */
	public function has_shipping_condition() {
		$product_pricing_rules = \yaydp_get_running_product_pricing_rules();
		$cart_discount_rules   = \yaydp_get_running_cart_discount_rules();
		$checkout_fee_rules    = \yaydp_get_running_checkout_fee_rules();
		$rules                 = array_merge( $product_pricing_rules, $cart_discount_rules, $checkout_fee_rules );
		foreach ( $rules as $rule ) {
			$conditions = $rule->get_conditions();
			foreach ( $conditions as $condition ) {
				if ( 'shipping_method' === $condition['type'] ) {
					return true;
				}
			}
		}
		return false;
	}

}

new YAYDP_Enqueue_Frontend();
