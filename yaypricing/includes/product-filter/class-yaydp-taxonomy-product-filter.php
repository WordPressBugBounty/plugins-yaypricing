<?php
/**
 * Product filter over one product taxonomy (brands, custom taxonomies…).
 *
 * One instance per taxonomy replaces the five near-identical taxonomy
 * integrations: it lists the terms for the admin picker, matches a product
 * through its terms (and their ancestors, and its parent product), and
 * answers the assistant's "which products" query.
 *
 * @package YayPricing\Product_Filter
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter;

use YAYDP\Condition\YAYDP_Comparators;

defined( 'ABSPATH' ) || exit;

class YAYDP_Taxonomy_Product_Filter extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	/**
	 * Taxonomy slug (also the stored filter slug).
	 *
	 * @var string
	 */
	private $taxonomy;

	/**
	 * Dropdown label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Whether the admin picker lists empty terms too.
	 *
	 * @var bool
	 */
	private $include_empty_terms;

	/**
	 * Describe the taxonomy.
	 *
	 * @param string $taxonomy            Taxonomy slug.
	 * @param string $label               Translated dropdown label.
	 * @param bool   $include_empty_terms List terms with no products in the picker.
	 */
	public function __construct( $taxonomy, $label, $include_empty_terms = false ) {
		$this->taxonomy            = $taxonomy;
		$this->label               = $label;
		$this->include_empty_terms = $include_empty_terms;
	}

	public function slug() {
		return $this->taxonomy;
	}

	public function label() {
		return $this->label;
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'     => 'taxonomy',
			'taxonomy' => $this->taxonomy,
		);
	}

	/**
	 * The product (or its parent) has a listed term or a descendant of one.
	 */
	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$ids = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		return YAYDP_Comparators::matches_list( $this->has_term( $product, $ids ), $filter );
	}

	/**
	 * Whether the product, an ancestor of one of its terms, or its parent carries a listed term.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $ids     Term ids.
	 * @return bool
	 */
	private function has_term( $product, array $ids ) {
		$terms = \get_the_terms( $product->get_id(), $this->taxonomy );
		foreach ( $terms ? $terms : array() as $term ) {
			if ( in_array( $term->term_id, $ids ) || array_intersect( get_ancestors( $term->term_id, $this->taxonomy ), $ids ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				return true;
			}
		}
		$parent_id = $product->get_parent_id();
		return ! empty( $parent_id ) && $this->has_term( \wc_get_product( $parent_id ), $ids );
	}

	/** Terms matching the search, labelled with their ancestor path. */
	public function search_options( $search, $page, $limit ) {
		$args = array(
			'number'     => $limit + 1,
			'offset'     => ( $page - 1 ) * $limit,
			'order'      => 'ASC',
			'orderby'    => 'name',
			'taxonomy'   => $this->taxonomy,
			'name__like' => $search,
		);
		if ( $this->include_empty_terms ) {
			$args['hide_empty'] = false;
		}
		return array_map(
			function ( $term ) {
				$path   = '';
				$cursor = $term;
				while ( ! empty( $cursor->parent ) ) {
					$parent = get_term( $cursor->parent );
					if ( is_null( $parent ) || is_wp_error( $parent ) ) {
						break;
					}
					$path  .= $parent->name . ' ⇒ ';
					$cursor = $parent;
				}
				return array(
					'id'   => $term->term_id,
					'name' => $path . $term->name,
					'slug' => $term->slug,
				);
			},
			array_values( \get_categories( $args ) )
		);
	}

	public function matching_products( array $values, $comparation ) {
		if ( empty( $values ) ) {
			return array();
		}
		return \wc_get_products(
			array(
				'limit'     => -1,
				'order'     => 'ASC',
				'orderby'   => 'title',
				'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $this->taxonomy,
						'terms'    => $values,
						'operator' => 'in_list' === $comparation ? 'IN' : 'NOT IN',
					),
				),
			)
		);
	}
}
