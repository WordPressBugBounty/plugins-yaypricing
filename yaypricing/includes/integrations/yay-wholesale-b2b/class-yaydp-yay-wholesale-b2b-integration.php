<?php
/**
 * Integration with yay-wholesale-b2b-pro.
 *
 * Swaps the Pricing Table base price for the wholesale-adjusted price when
 * a wholesale role is active for the current user. Scope is limited to the
 * Pricing Table render path — cart/checkout/single-product pricing remain
 * governed by yay-wholesale-b2b-pro's own WC filters.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Yay_Wholesale_B2B;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Yay_Wholesale_B2B_Integration {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_filter( 'yaydp_pricing_table_base_price', array( $this, 'apply_wholesale_base_price' ), 10, 2 );
	}

	/**
	 * Replace retail base with wholesale-adjusted base.
	 *
	 * Mirrors what yay-wholesale-b2b-pro renders on shop/product pages
	 * (`ShopPricingHelper::get_wholesale_price_for_display`) so the Pricing
	 * Table stays consistent with the displayed unit price. Result is in
	 * store base currency — YayPricing's currency conversion still runs
	 * afterwards.
	 *
	 * @param float       $price   Retail base price in store base currency.
	 * @param \WC_Product $product Product being rendered in Pricing Table.
	 *
	 * @return float
	 */
	public function apply_wholesale_base_price( $price, $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return $price;
		}
		if ( ! class_exists( '\YayWholesaleB2B\Helpers\PricingHelpers\ShopPricingHelper' ) ) {
			return $price;
		}
		$role = \YayWholesaleB2B\Helpers\CustomerHelper::get_current_user_wholesale_role();
		if ( empty( $role ) ) {
			return $price;
		}
		return (float) \YayWholesaleB2B\Helpers\PricingHelpers\ShopPricingHelper::get_wholesale_price_for_display( $product, $role );
	}
}
