<?php
/**
 * Product filter type: products_on_sale_wc
 *
 * @package YayPricing\Product_Filter\Type
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter\Type;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Filter_Products_On_Sale_Wc extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'products_on_sale_wc';
	}

	public function label() {
		return __( 'On sale (WooCommerce)', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::pick(
			array(
				'on_sale'     => __( 'On sale', 'yaypricing' ),
				'not_on_sale' => __(
					'Not on sale',
					'yaypricing'
				),
			)
		);
	}

	public function editor() {
		return array( 'kind' => 'none' );
	}

	public function default_value() {
		return array();
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		return 'on_sale' === $filter['comparation'] ? $product->is_on_sale() : ! $product->is_on_sale();
	}
}
