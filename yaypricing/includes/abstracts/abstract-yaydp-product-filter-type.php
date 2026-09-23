<?php
/**
 * Base class for a product filter type.
 *
 * One subclass per stored `filters[].type` value of a rule's buy/get product
 * lists (and of product collections / exclusions). A product filter is a
 * per-product predicate; rule-level predicates over the cart or customer are
 * condition types (YAYDP_Condition_Type). The slug is the stored value and
 * never changes.
 *
 * @package YayPricing\Abstracts
 * @since 3.5.8
 */

namespace YAYDP\Abstracts;

use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

abstract class YAYDP_Product_Filter_Type {

	/**
	 * Stored value of `filter.type`. Frozen: existing rules reference it.
	 *
	 * @return string
	 */
	abstract public function slug();

	/**
	 * Dropdown label, translated at call time.
	 *
	 * @return string
	 */
	abstract public function label();

	/**
	 * Ordered comparator list: array of [ 'value' => string, 'label' => string ].
	 * Empty when the filter has no comparator (all_product).
	 *
	 * @return array
	 */
	abstract public function comparators();

	/**
	 * Value-editor descriptor for the admin: [ 'kind' => string, ...extra ].
	 * Kinds: none | number | source | taxonomy.
	 *
	 * @return array
	 */
	public function editor() {
		return array( 'kind' => 'none' );
	}

	/**
	 * Default `filter.value` for a freshly added filter. `null` lets the admin
	 * derive one from the editor (first source option).
	 *
	 * @return mixed
	 */
	public function default_value() {
		return null;
	}

	/**
	 * Whether the product passes the filter.
	 *
	 * @param \WC_Product                  $product Product under test.
	 * @param array                        $filter  Stored filter (type, comparation, value).
	 * @param YAYDP_Product_Filter_Context $ctx     Evaluation context.
	 * @return bool
	 */
	abstract public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx );

	/**
	 * Admin remote search for the value picker, or null when the editor lists
	 * its options from a localised collection instead.
	 *
	 * @param string $search Search text.
	 * @param int    $page   1-based page.
	 * @param int    $limit  Page size.
	 * @return array|null Rows of { id, name, slug }.
	 */
	public function search_options( $search, $page, $limit ) {
		return null;
	}

	/**
	 * Products matching a stored value/comparator, for the rule assistant and
	 * previews; null leaves the lookup to YAYDP_Matching_Products_Helper.
	 *
	 * @param array  $values      Stored values (ids/slugs).
	 * @param string $comparation Comparator slug.
	 * @return \WC_Product[]|null
	 */
	public function matching_products( array $values, $comparation ) {
		return null;
	}
}
