<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Meowcrew;

use YAYDP\Helper\YAYDP_Pricing_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Role_Based_Pricing {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'MeowCrew\RoleAndCustomerBasedPricing\RoleAndCustomerBasedPricingPlugin' ) ) {
			return;
		}

		add_filter( 'yaydp_initial_product_price', array( $this, 'init_product_price' ), 100, 2 );
		add_filter( 'role_customer_specific_pricing/pricing/price_in_cart', array( $this, 'adjust_free_item_price' ), 100, 2 );
	}

	public function init_product_price( $price, $product ) {
		if ( empty( $product ) ) {
			return $price;
		}

		$settings                  = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		$is_based_on_regular_price = 'regular_price' === $settings->get_discount_base_on();
		$price_context             = 'original';
		$is_product_on_sale        = $product->is_on_sale();
		$product_sale_price        = $this->get_sale_price( $product, $price_context );
		$product_regular_price     = $this->get_regular_price( $product, $price_context );
		$sale_price                = $is_product_on_sale ? $product_sale_price : $product_regular_price;
		$product_price             = $is_based_on_regular_price ? $product_regular_price : $sale_price;
		$product_price             = YAYDP_Pricing_Helper::get_product_fixed_price( $product_price, $product );

		return $product_price;

	}

	public function get_regular_price( $product, $context = 'original' ) {

		if ( ! function_exists( '\MeowCrew\RoleAndCustomerBasedPricing\PricingRulesDispatcher::dispatchRule' ) ) {
			return 0;
		}
		$pricingRule = \MeowCrew\RoleAndCustomerBasedPricing\PricingRulesDispatcher::dispatchRule( $product->get_id() );

		if ( $pricingRule ) {

			if ( $pricingRule->getPriceType() === 'flat' && $pricingRule->getRegularPrice() ) {
				return $pricingRule->getRegularPrice();
			} elseif ( $pricingRule->getPriceType() !== 'percentage' || \MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainer::getInstance()->getSettings()->getPercentageBasedRulesBehavior() !== 'sale_price' ) {
				return $pricingRule->getPrice();
			}
		}

		return $product->get_regular_price( $context );

	}

	public function get_sale_price( $product, $context = 'original' ) {

		if ( ! function_exists( '\MeowCrew\RoleAndCustomerBasedPricing\PricingRulesDispatcher::dispatchRule' ) ) {
			return 0;
		}
		$pricingRule = \MeowCrew\RoleAndCustomerBasedPricing\PricingRulesDispatcher::dispatchRule( $product->get_id() );

		if ( $pricingRule ) {
			if ( $pricingRule->getPriceType() === 'flat' && $pricingRule->getSalePrice() ) {
				return $pricingRule->getSalePrice();
			} else {
				return $pricingRule->getPrice();
			}
		}

		return $product->get_sale_price( $context );

	}

	public function adjust_free_item_price( $price, $cart_item ) {
		if ( ! empty( $cart_item['is_extra'] ) ) {
			return 0;
		}
		return $price;
	}
}
