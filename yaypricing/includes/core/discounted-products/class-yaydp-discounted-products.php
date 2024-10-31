<?php

/**
 * Manage Admin Order
 *
 * @since 3.4
 * @package YayPricing\Admin_Order
 */

namespace YAYDP\Core\Discounted_Products;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAYDP_Discounted_Products {
	use \YAYDP\Traits\YAYDP_Singleton;

	private $product_ids = array();

	private function __construct() {}

	public function add_product( $product ) {
		if ( $product && $product instanceof \WC_Product ) {
			$this->product_ids[] = $product->get_id();
		}
	}

	public function get_products() {
		return array_unique( $this->product_ids, SORT_NUMERIC );
	}

	public function is_discounted( $product ) {
		if ( ! $product || ! ( $product instanceof \WC_Product ) ) {
			return true;
		}
		$product_id = $product->get_id();
		return in_array( $product_id, $this->get_products() );
	}

	public function clear_products() {
		$this->product_ids = array();
	}
}
