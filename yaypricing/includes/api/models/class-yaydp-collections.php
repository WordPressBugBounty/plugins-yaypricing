<?php
/**
 * The picker collections of the settings page, in one table.
 *
 * Each entry names a JS picker source and how it is served:
 *   route   REST slug under yaydp/v1/page-data/ (null = no route, seed only)
 *   getter  callable( string $search, int $page, int $limit ): array of rows
 *   seed    whether the first page is part of the batched page-data/seeds reply
 *
 * The REST controller registers its routes from this table, the seeds route
 * iterates it, and integrations add their own entries through the
 * `yaydp_collections` filter. Every getter returns at most `limit + 1` rows:
 * the extra row tells the picker that a further page exists.
 *
 * @package YayPricing\Models
 * @since 3.5.8
 */

namespace YAYDP\API\Models;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Collections {

	/**
	 * Collections keyed by picker source, integrations included.
	 *
	 * @return array<string, array{route: ?string, getter: callable, seed: bool}>
	 */
	public static function all() {
		$model = YAYDP_Data_Model::class;
		$core  = array(
			'products'                     => array(
				'route'  => 'products',
				'getter' => array( $model, 'get_products' ),
				'seed'   => true,
			),
			'product_variations'           => array(
				'route'  => 'variations',
				'getter' => array( $model, 'get_variations' ),
				'seed'   => true,
			),
			'product_categories'           => array(
				'route'  => 'categories',
				'getter' => array( $model, 'get_categories' ),
				'seed'   => true,
			),
			'product_attributes'           => array(
				'route'  => 'attributes',
				'getter' => array( $model, 'get_attributes' ),
				'seed'   => true,
			),
			'product_specific_attributes'  => array(
				'route'  => 'specific-attributes',
				'getter' => array( $model, 'get_product_specific_attributes' ),
				'seed'   => true,
			),
			'product_attribute_taxonomies' => array(
				'route'  => null,
				'getter' => array( $model, 'get_attribute_taxonomies' ),
				'seed'   => true,
			),
			'product_tags'                 => array(
				'route'  => 'tags',
				'getter' => array( $model, 'get_tags' ),
				'seed'   => true,
			),
			'customer_roles'               => array(
				'route'  => 'customer-roles',
				'getter' => array( $model, 'get_customer_roles' ),
				'seed'   => true,
			),
			'customers'                    => array(
				'route'  => 'customers',
				'getter' => array( $model, 'get_customers' ),
				'seed'   => true,
			),
			'payment_methods'              => array(
				'route'  => 'payment-methods',
				'getter' => array( $model, 'get_payment_methods' ),
				'seed'   => true,
			),
			'shipping_methods'             => array(
				'route'  => null,
				'getter' => array( $model, 'get_shipping_methods' ),
				'seed'   => true,
			),
			'shipping_classes'             => array(
				'route'  => null,
				'getter' => array( $model, 'get_shipping_classes' ),
				'seed'   => true,
			),
			'coupons'                      => array(
				'route'  => 'coupons',
				'getter' => array( $model, 'get_coupons' ),
				'seed'   => true,
			),
			// Region trees are fetched whole by the admin (utils/regions.js), never seeded.
			'shipping_regions'             => array(
				'route'  => 'shipping-regions',
				'getter' => array( $model, 'get_shipping_regions' ),
				'seed'   => false,
			),
			'billing_regions'              => array(
				'route'  => 'billing-regions',
				'getter' => array( $model, 'get_billing_regions' ),
				'seed'   => false,
			),
		);

		/**
		 * Add or replace picker collections.
		 *
		 * @since 3.5.8
		 * @param array $collections source => { route, getter, seed }.
		 */
		$all = apply_filters( 'yaydp_collections', $core );

		return array_filter(
			(array) $all,
			function ( $entry ) {
				return is_array( $entry ) && isset( $entry['getter'] ) && is_callable( $entry['getter'] );
			}
		);
	}

	/**
	 * Seed page of every seed collection, keyed by source: the first page for
	 * routed collections, the whole list for route-less ones (the admin has no
	 * other way to search those, so it filters the seed itself).
	 *
	 * @return array<string, array>
	 */
	public static function seeds() {
		$seeds = array();
		foreach ( self::all() as $source => $entry ) {
			if ( ! empty( $entry['seed'] ) ) {
				$limit            = empty( $entry['route'] ) ? PHP_INT_MAX - 1 : YAYDP_SEARCH_LIMIT;
				$seeds[ $source ] = call_user_func( $entry['getter'], '', 1, $limit );
			}
		}
		return $seeds;
	}

	/**
	 * Search + window an in-memory list the way the paged getters do:
	 * `$matcher( $row, $search )` decides membership, the window is
	 * `limit + 1` rows from `( page - 1 ) * limit` (pass a huge limit for the
	 * whole list).
	 *
	 * @param array    $rows    Full list.
	 * @param string   $search  Search text ('' keeps everything).
	 * @param int      $page    1-based page.
	 * @param int      $limit   Page size.
	 * @param callable $matcher function( array $row, string $search ): bool.
	 * @return array
	 */
	public static function paginate( array $rows, $search, $page, $limit, callable $matcher ) {
		$search = (string) $search;
		// empty(): a search of "0" keeps the whole list, as the getters always did.
		if ( ! empty( $search ) ) {
			$rows = array_filter(
				$rows,
				function ( $row ) use ( $matcher, $search ) {
					return (bool) $matcher( $row, $search );
				}
			);
		}
		$page  = max( 1, (int) $page );
		$limit = max( 1, (int) $limit );
		return array_values( array_slice( array_values( $rows ), ( $page - 1 ) * $limit, $limit + 1 ) );
	}

	/**
	 * Matcher shared by the small in-memory lists: case-insensitive "contains"
	 * on the row name or id, the same test the admin's client-side filter used.
	 *
	 * @param array  $row    Row with name / id.
	 * @param string $search Search text.
	 * @return bool
	 */
	public static function name_or_id_contains( array $row, $search ) {
		$needle = strtolower( $search );
		foreach ( array( 'name', 'id' ) as $field ) {
			if ( isset( $row[ $field ] ) && false !== strpos( strtolower( (string) $row[ $field ] ), $needle ) ) {
				return true;
			}
		}
		return false;
	}
}
