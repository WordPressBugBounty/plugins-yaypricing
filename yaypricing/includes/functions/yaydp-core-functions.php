<?php
/**
 * YayPricing core functions
 *
 * Declare global functions
 *
 * @package YayPricing\Functions
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'yaydp_is_rest_api_request' ) ) {

	/**
	 * Check if the request is a REST API request
	 *
	 * @return bool Returns true if current request is REST API request
	 */
	function yaydp_is_rest_api_request() {
		if ( empty( filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL ) ) ) {
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL ), $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return $is_rest_api_request;
	}
}

if ( ! function_exists( 'yaydp_is_request' ) ) {

	/**
	 * Checks if the current request type matches the given types.
	 *
	 * @param string $request_type The request type to check against.
	 * @return bool Returns true if the current request type matches the given types, false otherwise
	 */
	function yaydp_is_request( $request_type ) {
		if ( 'frontend' == $request_type ) {
			if ( defined( 'DOING_CRON' ) ) {
				return false;
			}
			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
				return true;
			}
			if ( ! is_admin() && yaydp_is_rest_api_request() ) {
				return true;
			}
			return false;
		}
		switch ( $request_type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
		}
	}
}

if ( ! function_exists( 'yaydp_get_stock_quantity' ) ) {

	/**
	 * Retrieves the current stock quantity for a given product
	 *
	 * @param \WC_Product $product Given product.
	 * @return float Returns INT MAX if in stock and quantity is null, INT MIN if out of stock, otherwise returns the stock
	 */
	function yaydp_get_stock_quantity( $product ) {
		$stock_quantity = $product->get_stock_quantity();
		$is_in_stock    = $product->is_in_stock();
		if ( is_null( $stock_quantity ) && $is_in_stock ) {
			return PHP_INT_MAX;
		}
		if ( ! $is_in_stock ) {
			return 0;
		}
		return floatval( $stock_quantity );

	}
}

if ( ! function_exists( 'yaydp_get_shipping_fee' ) ) {

	/**
	 * Returns total shipping fee
	 *
	 * @return float
	 */
	function yaydp_get_shipping_fee() {
		$total = WC()->cart->get_shipping_total();

		return \YAYDP\Helper\YAYDP_Pricing_Helper::reverse_price( $total );
	}
}

if ( ! function_exists( 'yaydp_current_frontend_page' ) ) {

	/**
	 * Get current frontend page. Not work with AJAX.
	 * Returns other when not match specific pages.
	 *
	 * @since 2.1
	 *
	 * @return string
	 */
	function yaydp_current_frontend_page() {
		$current_page = 'other';
		if ( \is_shop() ) {
			$current_page = 'shop';
		}
		if ( \is_product() ) {
			$current_page = 'product';
		}
		if ( \is_cart() ) {
			$current_page = 'cart';
		}
		if ( \is_checkout() ) {
			$current_page = 'checkout';
		}
		return $current_page;
	}
}

if ( ! function_exists( 'yaydp_is_variable_product' ) ) {

	/**
	 * Check if given product is variable product.
	 *
	 * @since 2.1
	 * @param \WC_Product $product Given product.
	 *
	 * @return array
	 */
	function yaydp_is_variable_product( $product ) {
		return 'variable' === $product->get_type() || 'variable-subscription' === $product->get_type();
	}
}

if ( ! function_exists( 'yaydp_is_grouped_product' ) ) {

	/**
	 * Check if given product is grouped product.
	 *
	 * @since 2.1
	 * @param \WC_Product $product Given product.
	 *
	 * @return array
	 */
	function yaydp_is_grouped_product( $product ) {
		return 'grouped' === $product->get_type();
	}
}

if ( ! function_exists( 'yaydp_is_variation_product' ) ) {

	/**
	 * Check if given product is variable product.
	 *
	 * @since 2.1
	 * @param \WC_Product $product Given product.
	 *
	 * @return array
	 */
	function yaydp_is_variation_product( $product ) {
		return 'variation' === $product->get_type() || 'subscription_variation' === $product->get_type();
	}
}

if ( ! function_exists( 'yaydp_get_cart_total_weight' ) ) {

	/**
	 * Returns total weight of current cart
	 * Excludes free item
	 *
	 * @since 2.1
	 * @return float
	 */
	function yaydp_get_cart_total_weight() {
		$total = 0;
		foreach ( \WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['is_extra'] ) ) {
				continue;
			}
			$item_quantity  = empty( $cart_item['quantity'] ) ? 0 : $cart_item['quantity'];
			$product        = $cart_item['data'];
			$product_weight = empty( $product->get_weight() ) ? 0 : $product->get_weight();
			$total         += $product_weight * $item_quantity;
		}
		return $total;
	}
}

if ( ! function_exists( 'yaydp_get_current_quantity_in_cart' ) ) {

	/**
	 * Get current quantity of product in cart ( include extra item )
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 * @param \WC_Product            $product Counting product.
	 *
	 * @since 2.4
	 */
	function yaydp_get_current_quantity_in_cart( \YAYDP\Core\YAYDP_Cart $cart, $product ) {
		$product_id       = $product->get_id();
		$current_quantity = 0;
		foreach ( $cart->get_items_include_extra() as $item ) {
			$item_product    = $item->get_product();
			$item_product_id = $item_product->get_id();
			if ( $product_id === $item_product_id ) {
				$current_quantity += $item->get_quantity();
			}
		}
		return $current_quantity;
	}
}

if ( ! function_exists( 'yaydp_serialize_cart_data' ) ) {

	/**
	 * Serialize data
	 *
	 * @param array $data Serializing data.
	 *
	 * @since 2.4
	 */
	function yaydp_serialize_cart_data( $data ) {
		return maybe_serialize( $data );
	}
}

if ( ! function_exists( 'yaydp_unserialize_cart_data' ) ) {

	/**
	 * Unserialize data
	 *
	 * @param array $data Unserializing data.
	 *
	 * @since 2.4
	 */
	function yaydp_unserialize_cart_data( $data ) {
		return maybe_unserialize( $data );
	}
}

if ( ! function_exists( 'yaydp_format_discount_value' ) ) {

	/**
	 * Format pricing value.
	 * If add % if is percentage.
	 * WC format if is fixed.
	 *
	 * @param float  $value Pricing value.
	 * @param string $type Pricing type.
	 */
	function yaydp_format_discount_value( $value, $type = 'fixed_discount' ) {
		if ( \yaydp_is_percentage_pricing_type( $type ) ) {
			return "$value%";
		}
		return \wc_price( $value );
	}
}

if ( ! function_exists( 'yaydp_is_extra_cart_item_wc' ) ) {

	/**
	 * Check is cart item a extra
	 *
	 * @param array $cart_item Checking cart item.
	 */
	function yaydp_is_extra_wc_cart_item( $cart_item ) {
		return ! empty( $cart_item['is_extra'] );
	}
}

if ( ! function_exists( 'yaydp_prepare_html' ) ) {

	/**
	 * Prepare the HTML content before passing it to the sanitize function to ensure that it is in the correct format and structure.
	 * This can include removing any unnecessary tags or attributes, fixing any broken HTML, and ensuring that the content is properly encoded.
	 *
	 * @param string $output Given html.
	 *
	 * @return string
	 */
	function yaydp_prepare_html( $output ) {
		$output = \YAYDP\Helper\YAYDP_Helper::replace_rgb_to_hex( $output );
		return $output;
	}
}

if ( ! function_exists( 'yaydp_sort_array' ) ) {
	/**
	 * Sorts an array by given $order
	 *
	 * @param array  $array Array to be sorted.
	 * @param string $order Given order.
	 *
	 * @since 2.4
	 */
	function yaydp_sort_array( &$array, $order = 'asc' ) {
		usort(
			$array,
			function( $a, $b ) use ( $order ) {
				return 'asc' === $order ? $a <=> $b : $b <=> $a;
			}
		);
	}
}

if ( ! function_exists( 'yaydp_check_wc_hpos' ) ) {
	/**
	 * Sorts an array by given $order
	 *
	 * @since 2.4.3
	 */
	function yaydp_check_wc_hpos() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
			if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				return true;
			}
		}
		return false;
	}
}
if ( ! function_exists( 'yaydp_get_free_chosen_products' ) ) {
	function yaydp_get_free_chosen_products( $rule_id ) {
		$free_chosen_products = array();
		$session              = isset( \WC()->session ) ? \WC()->session : false;
		if ( $session ) {
			$free_chosen_products = WC()->session->get( 'yaydp_free_chosen_products_' . $rule_id );
		}
		if ( ! is_array( $free_chosen_products ) ) {
			WC()->session->set( 'yaydp_free_chosen_products_' . $rule_id, array() );
			$free_chosen_products = array();
		}
		return $free_chosen_products;
	}
}
if ( ! function_exists( 'yaydp_set_free_chosen_products' ) ) {
	function yaydp_set_free_chosen_products( $rule_id, $products ) {
		$session = isset( \WC()->session ) ? \WC()->session : false;
		if ( $session ) {
			WC()->session->set( 'yaydp_free_chosen_products_' . $rule_id, $products );
		}
	}
}
if ( ! function_exists( 'yaydp_get_product' ) ) {
	function yaydp_get_product( $id ) {
		static $yaydp_cached_products = array();

		if ( ! isset( $yaydp_cached_products[ $id ] ) ) {
			$yaydp_cached_products[ $id ] = \wc_get_product( $id );
		}

		return $yaydp_cached_products[ $id ];
	}
}

if ( ! function_exists( 'yaydp_get_timezone_offset' ) ) {
	function yaydp_get_timezone_offset() {
		$time = new \DateTime( 'now', wp_timezone() );
		return $time->format( 'P' );
	}
}

if ( ! function_exists( 'yaydp_get_saved_amount' ) ) {
	function yaydp_get_saved_amount() {
		global $yaydp_cart;
		$yaydp_cart                  = new \YAYDP\Core\YAYDP_Cart();
		$product_pricing_adjustments = new \YAYDP\Core\Adjustments\YAYDP_Product_Pricing_Adjustments( $yaydp_cart );
		$product_pricing_adjustments->do_stuff();
		$saved_amount = $yaydp_cart->get_cart_origin_total( false ) - $yaydp_cart->get_cart_subtotal( false );

		if ( empty( $saved_amount ) ) {
			return 0;
		}

		return $saved_amount;
	}
}


/**
 * Clear cache function
 *
 * @since 3.4.2
 */
if ( ! function_exists( 'yaydp_clear_cache' ) ) {
	function yaydp_clear_cache() {
		do_action( 'yaydp_clear_cache' );
	}
}

/**
 * Check if cart page has cart block
 *
 * @since 3.4.2
 */
if ( ! function_exists( 'yaydp_has_cart_block' ) ) {
	function yaydp_has_cart_block() {
		$cart_page_id   = \wc_get_page_id( 'cart' );
		$has_block_cart = $cart_page_id && has_block( 'woocommerce/cart', $cart_page_id );
		return $has_block_cart;
	}
}

/**
 * Check if checkout page has checkout block
 *
 * @since 3.4.2
 */
if ( ! function_exists( 'yaydp_has_checkout_block' ) ) {
	function yaydp_has_checkout_block() {
		$post = get_post( get_option( 'woocommerce_checkout_page_id' ) );
		return strpos( $post->post_content, '<!-- wp:woocommerce/checkout' ) !== false;
	}
}
