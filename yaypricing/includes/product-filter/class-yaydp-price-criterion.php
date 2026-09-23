<?php
/**
 * "Highest / second-highest / … lowest price" rankings shared by the price
 * criterion filters.
 *
 * @package YayPricing\Product_Filter
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Price_Criterion {

	/**
	 * Index into a list sorted by price descending for a criterion slug, or
	 * null when the criterion is unknown or the list is too short.
	 *
	 * @param string $comparation Criterion slug (highest_price, second_lowest_price, …).
	 * @param int    $count       List length.
	 * @return int|null
	 */
	public static function rank_index( $comparation, $count ) {
		$ranks = array(
			'highest_price'        => 0,
			'second_highest_price' => 1,
			'third_highest_price'  => 2,
			'lowest_price'         => $count - 1,
			'second_lowest_price'  => 1,
			'third_lowest_price'   => 2,
		);
		if ( ! isset( $ranks[ $comparation ] ) || $count < 1 ) {
			return null;
		}
		return $ranks[ $comparation ] < $count ? $ranks[ $comparation ] : null;
	}

	/**
	 * Sort products by YayPricing price, highest first.
	 *
	 * @param \WC_Product[] $products Products.
	 * @return \WC_Product[]
	 */
	private static function by_price_desc( array $products ) {
		usort(
			$products,
			function ( $a, $b ) {
				return \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $b ) - \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $a );
			}
		);
		return $products;
	}

	/**
	 * Keep only the product id at the criterion's rank among the given ids.
	 *
	 * @param int[]      $ids        Product ids.
	 * @param array|null $sub_filter Price-criterion sub-filter.
	 * @return int[]
	 */
	public static function narrow_product_ids( array $ids, ?array $sub_filter = null ) {
		if ( ! $sub_filter || empty( $ids ) ) {
			return $ids;
		}
		$products = self::by_price_desc( array_map( 'wc_get_product', $ids ) );
		$index    = self::rank_index( $sub_filter['comparation'], count( $products ) );
		return is_null( $index ) ? $ids : array( $products[ $index ]->get_id() );
	}

	/**
	 * Keep the category ids only if the product is the one at the criterion's
	 * rank among all products in those categories.
	 *
	 * @param int[]       $ids        Category ids.
	 * @param \WC_Product $product    Product under test.
	 * @param array|null  $sub_filter Price-criterion sub-filter.
	 * @return int[]
	 */
	public static function narrow_category_ids( array $ids, $product, ?array $sub_filter = null ) {
		if ( ! $sub_filter || empty( $ids ) ) {
			return $ids;
		}
		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $ids,
						'operator' => 'IN',
					),
				),
			)
		);
		if ( ! $query->have_posts() ) {
			return array();
		}
		$products = self::by_price_desc( array_map( 'wc_get_product', wp_list_pluck( $query->get_posts(), 'ID' ) ) );
		$index    = self::rank_index( $sub_filter['comparation'], count( $products ) );
		return ( ! is_null( $index ) && $products[ $index ]->get_id() === $product->get_id() ) ? $ids : array();
	}

	/**
	 * Whether the product sits at the criterion's rank among the cart's lines.
	 *
	 * @param \WC_Product                  $product     Product under test.
	 * @param string                       $comparation Criterion slug.
	 * @param YAYDP_Product_Filter_Context $ctx         Context.
	 * @return bool
	 */
	public static function cart_item_matches( $product, $comparation, YAYDP_Product_Filter_Context $ctx ) {
		$wc_cart = $ctx->wc_cart();
		if ( ! $wc_cart ) {
			return false;
		}
		$lines = array();
		foreach ( $wc_cart->get_cart() as $cart_item ) {
			if ( $cart_item['data'] instanceof \WC_Product ) {
				$lines[] = $cart_item['data'];
			}
		}
		if ( empty( $lines ) ) {
			return false;
		}
		$lines = self::by_price_desc( $lines );
		$index = self::rank_index( $comparation, count( $lines ) );
		return ! is_null( $index ) && $lines[ $index ]->get_id() === $product->get_id();
	}
}
