<?php
/**
 * Evaluation context handed to product filter types.
 *
 * Carries what the filter loop knows besides the product: the cart line being
 * evaluated (so variation attributes can be read off the cart item), the
 * price-criterion sub-filter narrowing the list, and session lookups so types
 * never touch globals directly.
 *
 * @package YayPricing\Product_Filter
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Product_Filter_Context {

	/**
	 * Cart item key when evaluating a cart line, else null.
	 *
	 * @var string|null
	 */
	private $item_key;

	/**
	 * The `sub_filter_product_price_criterion` filter of the same list, if any.
	 *
	 * @var array|null
	 */
	private $sub_filter;

	/**
	 * Build a context.
	 *
	 * @param string|null $item_key   Cart item key.
	 * @param array|null  $sub_filter Price-criterion sub-filter.
	 */
	public function __construct( $item_key = null, ?array $sub_filter = null ) {
		$this->item_key   = $item_key;
		$this->sub_filter = $sub_filter;
	}

	/**
	 * Same cart line, with the given sub-filter.
	 *
	 * @param array|null $sub_filter Sub-filter.
	 * @return self
	 */
	public function with_sub_filter( ?array $sub_filter = null ) {
		return new self( $this->item_key, $sub_filter );
	}

	/**
	 * Cart item key, or null.
	 *
	 * @return string|null
	 */
	public function item_key() {
		return $this->item_key;
	}

	/**
	 * Price-criterion sub-filter, or null.
	 *
	 * @return array|null
	 */
	public function sub_filter() {
		return $this->sub_filter;
	}

	/**
	 * Attributes chosen on the evaluated cart line (`attribute_pa_x` => slug), or [].
	 *
	 * @return array
	 */
	public function cart_item_variation() {
		$wc_cart = $this->wc_cart();
		if ( is_null( $this->item_key ) || ! $wc_cart ) {
			return array();
		}
		foreach ( $wc_cart->get_cart() as $cart_item ) {
			if ( $cart_item['key'] === $this->item_key && ! empty( $cart_item['variation'] ) ) {
				return (array) $cart_item['variation'];
			}
		}
		return array();
	}

	/**
	 * Live WooCommerce cart, or null when none is loaded.
	 *
	 * @return \WC_Cart|null
	 */
	public function wc_cart() {
		return ( function_exists( 'WC' ) && ! empty( \WC()->cart ) ) ? \WC()->cart : null;
	}

	/**
	 * Current user id (0 when logged out).
	 *
	 * @return int
	 */
	public function user_id() {
		return \get_current_user_id();
	}
}
