<?php
/**
 * This class is responsible for managing the pricing of products in the YAYDP system.
 * It contains methods for calculating prices based on various factors such as discounts, taxes, and fees
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\Core\Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Pricing_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		\YAYDP\YAYDP_Ajax::get_instance();
		\YAYDP\Core\Manager\YAYDP_Product_Pricing_Manager::get_instance();
		\YAYDP\Core\Manager\YAYDP_Cart_Discount_Manager::get_instance();
		\YAYDP\Core\Manager\YAYDP_Checkout_Fee_Manager::get_instance();
		\YAYDP\Core\Manager\YAYDP_WC_Coupon_Manager::get_instance();
		\YAYDP\Core\Shortcode\YAYDP_Shortcode_Handler::get_instance();
	}

}

new YAYDP_Pricing_Manager();
