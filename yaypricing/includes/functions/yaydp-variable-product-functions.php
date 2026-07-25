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
		$children_id = $product->get_children();
		$prices      = array();
		foreach ( $children_id as $id ) {
			$variation = \wc_get_product( $id );
			if ( empty( $variation ) ) {
				continue;
			}
			// Use the context-aware base price so other-source price plugins (e.g. Product Price
			// Based on Countries) apply via yaydp_other_source_product_base_price.
			$prices[ $id ] = floatval( \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $variation ) );
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
