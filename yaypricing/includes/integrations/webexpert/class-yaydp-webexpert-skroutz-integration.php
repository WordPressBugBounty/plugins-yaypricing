<?php
/**
 * WebExpert Skroutz XML Feed and YayPricing compatibility.
 *
 * Replaces the `price_with_vat` value in the generated Skroutz XML feed
 * with the YayPricing discounted price (including VAT).
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Webexpert;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAYDP_Webexpert_Skroutz_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor. Hooks into the feed price filter only when the
	 * WebExpert Skroutz XML Feed plugin is active.
	 */
	protected function __construct() {
		if ( ! class_exists( 'WESkroutzXML' ) ) {
			return;
		}

		add_filter( 'webexpert_skroutz_xml_custom_pricing', array( $this, 'get_discounted_price' ), 10, 2 );
	}

	/**
	 * Return the YayPricing discounted price (VAT included) for the feed.
	 *
	 * The incoming `$price` is already VAT-inclusive and rounded, so the
	 * discounted price is converted to a VAT-inclusive value and rounded
	 * to the same precision for consistency.
	 *
	 * @param float                $price   VAT-inclusive price computed by the feed.
	 * @param \WC_Product|mixed     $product Product (or variation) being exported.
	 *
	 * @return float
	 */
	public function get_discounted_price( $price, $product ) {
		if ( empty( $product ) || ! ( $product instanceof \WC_Product ) ) {
			return $price;
		}

		$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_discounted_price = $product_sale->get_min_max_discounted_price();

		if ( is_null( $min_max_discounted_price ) ) {
			return $price;
		}

		$discounted_price = $min_max_discounted_price['min'];
		$discounted_price = \wc_get_price_including_tax( $product, array( 'price' => $discounted_price ) );

		return round( (float) $discounted_price, \wc_get_price_decimals() );
	}
}
