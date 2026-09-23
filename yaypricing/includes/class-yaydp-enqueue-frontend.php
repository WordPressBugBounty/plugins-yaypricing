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
			// Fired while the Cart / Mini-Cart and Checkout blocks render, so the script
			// only loads on pages that actually contain those blocks.
			add_action( 'woocommerce_blocks_cart_enqueue_data', array( $this, 'enqueue_block_cart_tooltip' ) );
			add_action( 'woocommerce_blocks_checkout_enqueue_data', array( $this, 'enqueue_block_cart_tooltip' ) );
		}
	}

	/**
	 * Cart item price tooltip for the WooCommerce Cart, Checkout and Mini-Cart blocks.
	 * Depends on tooltip.js for the shared builder/behaviour.
	 */
	public function enqueue_block_cart_tooltip() {
		if ( ! wp_script_is( 'wc-blocks-checkout', 'registered' ) ) {
			return;
		}
		$this->enqueue_script( 'block-cart-tooltip', 'block-cart-tooltip.js', array( 'wc-blocks-checkout', 'wp-data', 'yaydp-frontend-tooltip' ) );
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

		$features = $this->required_frontend_features();
		if ( in_array( 'payment', $features, true ) ) {
			$this->enqueue_script( 'payment', 'payment.js', array( 'jquery' ) );
		}
		if ( in_array( 'shipping', $features, true ) ) {
			$this->enqueue_script( 'shipping', 'shipping.js', array( 'jquery' ) );
		}
		if ( in_array( 'billing_email', $features, true ) ) {
			$this->enqueue_script( 'billing-email', 'billing-email.js', array( 'jquery' ) );
		}

		/**
		 * Rule tooltip (cart item price, coupon and fee rows); block-cart-tooltip.js builds on it
		 */
		$this->enqueue_script( 'tooltip', 'tooltip.js' );
		$this->enqueue_style( 'tooltip', 'tooltip.css' );
		wp_localize_script(
			'yaydp-frontend-tooltip',
			'yaydp_tooltip_data',
			array(
				'label' => __( 'Discount details', 'yaypricing' ),
			)
		);

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
				'i18n'              => array(
					'days'                  => __( 'days', 'yaypricing' ),
					'hours'                 => __( 'hours', 'yaypricing' ),
					'minutes'               => __( 'mins', 'yaypricing' ),
					'seconds'               => __( 'secs', 'yaypricing' ),
					'choose_quantity_again' => __( 'Please choose your quantity again!', 'yaypricing' ),
				),
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

	/**
	 * Frontend features the running rules' condition types ask for
	 * ('payment', 'shipping', 'billing_email', …), one pass over all rules.
	 *
	 * @return string[]
	 */
	public function required_frontend_features() {
		$rules    = array_merge( \yaydp_get_running_product_pricing_rules(), \yaydp_get_running_cart_discount_rules(), \yaydp_get_running_checkout_fee_rules() );
		$registry = \YAYDP\Condition\YAYDP_Condition_Registry::instance();
		$features = array();
		foreach ( $rules as $rule ) {
			foreach ( $rule->get_conditions() as $condition ) {
				$type = $registry->get( isset( $condition['type'] ) ? $condition['type'] : '' );
				if ( $type ) {
					$features = array_merge( $features, $type->frontend_requirements() );
				}
			}
		}
		return array_values( array_unique( $features ) );
	}
}

new YAYDP_Enqueue_Frontend();
