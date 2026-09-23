<?php
/**
 * Product filter type: product_price
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Product_Price extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'product_price';
	}

	public function label() {
		return __( 'Product price', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::numeric();
	}

	public function editor() {
		return array(
			'kind'   => 'number',
			'suffix' => 'currency',
		);
	}

	public function default_value() {
		return 100;
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		if ( \yaydp_is_variable_product( $product ) ) {
			$sale    = $product->get_variation_sale_price( 'min' );
			$regular = $product->get_variation_regular_price( 'min' );
			$price   = ( 'regular_price' === $settings->get_discount_base_on() || empty( $sale ) ) ? $regular : $sale;
		} else {
			$price = (float) \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		}
		return YAYDP_Comparators::compare_numeric( $price, $filter );
	}
}
