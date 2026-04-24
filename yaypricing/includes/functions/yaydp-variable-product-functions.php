<?php
/**
 * YayPricing Variable Product Functions
 *
 * Holds core functions for variable product.
 *
 * @package YayPricing\Functions
 *
 * @since 2.4
 */

if ( ! function_exists( 'yaydp_get_variable_product_prices' ) ) {

	/**
	 * Get the variable product prices for a given product.
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_variable_product_prices( $product ) {
		$children_id = $product->get_children();
		$prices      = array();
		foreach ( $children_id as $id ) {
			$product       = \wc_get_product( $id );
			$prices[ $id ] = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		}
		return $prices;
	}
}

if ( ! function_exists( 'yaydp_get_min_price_variation' ) ) {
	/**
	 * Get the variation whose minimum price in list product variations.
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_min_price_variation( $product ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return 0;
		}
		$prices = \yaydp_get_variable_product_prices( $product );
		if ( empty( $prices ) ) {
			return 0;
		}
		return array_search( min( $prices ), $prices, true );
	}
}


if ( ! function_exists( 'yaydp_get_variable_product_min_price' ) ) {
	/**
	 * Get the minimum price of a product variation based on the given variable product.
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_variable_product_min_price( $product ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return 0;
		}
		$prices = \yaydp_get_variable_product_prices( $product );
		if ( empty( $prices ) ) {
			return 0;
		}
		return min( $prices );
	}
}

if ( ! function_exists( 'yaydp_get_max_price_variation' ) ) {
	/**
	 * Get the variation whose maximum price in list product variations.
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_max_price_variation( $product ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return 0;
		}
		$prices = \yaydp_get_variable_product_prices( $product );
		if ( empty( $prices ) ) {
			return 0;
		}
		return array_search( max( $prices ), $prices, true );
	}
}

if ( ! function_exists( 'yaydp_get_variable_product_store_prices' ) ) {

	/**
	 * Get the variable product store prices (not affected by YayPricing) for a given product.
	 * Respects the discount base setting (regular_price vs sale_price).
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_variable_product_store_prices( $product ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return array();
		}
		$settings                  = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		$is_based_on_regular_price = 'regular_price' === $settings->get_discount_base_on();
		$children_id               = $product->get_children();
		$prices                     = array();
		foreach ( $children_id as $id ) {
			$variation = \wc_get_product( $id );
			if ( empty( $variation ) ) {
				continue;
			}
			$price_context         = 'original';
			$is_product_on_sale   = $variation->is_on_sale( $price_context );
			$product_sale_price    = $variation->get_sale_price( $price_context );
			$product_regular_price = $variation->get_regular_price( $price_context );
			$sale_price            = $is_product_on_sale ? $product_sale_price : $product_regular_price;
			$product_price         = $is_based_on_regular_price ? $product_regular_price : $sale_price;
			$product_price         = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_fixed_price( $product_price, $variation );
			$prices[ $id ]         = floatval( $product_price );
		}
		return $prices;
	}
}

if ( ! function_exists( 'yaydp_get_variable_product_store_min_price' ) ) {
	/**
	 * Get the minimum store price (not affected by YayPricing) of a product variation based on the given variable product.
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_variable_product_store_min_price( $product ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return 0;
		}
		$prices = \yaydp_get_variable_product_store_prices( $product );
		if ( empty( $prices ) ) {
			return 0;
		}
		// Filter out zero prices to avoid showing 0 as min price
		$prices = array_filter( $prices, function( $price ) {
			return floatval( $price ) > 0;
		} );
		if ( empty( $prices ) ) {
			return 0;
		}
		return min( $prices );
	}
}

if ( ! function_exists( 'yaydp_get_variable_product_store_max_price' ) ) {
	/**
	 * Get the maximum store price (not affected by YayPricing) of a product variation based on the given variable product.
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_variable_product_store_max_price( $product ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return 0;
		}
		$prices = \yaydp_get_variable_product_store_prices( $product );
		if ( empty( $prices ) ) {
			return 0;
		}
		// Filter out zero prices to avoid showing 0 as max price
		$prices = array_filter( $prices, function( $price ) {
			return floatval( $price ) > 0;
		} );
		if ( empty( $prices ) ) {
			return 0;
		}
		return max( $prices );
	}
}

if ( ! function_exists( 'yaydp_get_variable_product_max_price' ) ) {
	/**
	 * Get the maximum price of a product variation based on the given variable product.
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_variable_product_max_price( $product ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return 0;
		}
		$prices = \yaydp_get_variable_product_prices( $product );
		if ( empty( $prices ) ) {
			return 0;
		}
		return max( $prices );
	}
}

if ( ! function_exists( 'yaydp_get_product_varations' ) ) {

	/**
	 * Get all variations of a product
	 *
	 * @param \WC_Product $product Product.
	 */
	function yaydp_get_product_varations( $product ) {
		if ( ! \yaydp_is_variable_product( $product ) ) {
			return null;
		}
		$children_id = $product->get_children();
		return array_map(
			function( $id ) {
				return \wc_get_product( $id );
			},
			$children_id
		);
	}
}
