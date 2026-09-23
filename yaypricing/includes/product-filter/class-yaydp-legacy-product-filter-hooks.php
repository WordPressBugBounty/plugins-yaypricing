<?php
/**
 * Back-compat for pre-3.5.8 product filter hooks. Removed in 3.6.0.
 *
 * The only file that knows the old third-party surface for product filters:
 *
 *   yaydp_admin_product_filters                 filter: admin entry { value, label, comparations }
 *   yaydp_check_condition_by_{slug}             filter: ( bool $result, WC_Product $product, array $filter ) — despite
 *                                                the name this always evaluated a *product filter*, not a condition
 *   yaydp_get_matching_products_by_{slug}       filter: ( array $products, string $slug, array $values, string $comparation )
 *   yaydp_admin_custom_filter_{slug}_result     filter: ( array $rows, string $slug, string $search, int $page, int $limit )
 *
 * Nothing else in the plugin references these names. Entries from the first
 * filter become YAYDP_Legacy_Hook_Product_Filter types whose methods apply the
 * other three; a slug nobody registers evaluates to false (fail-closed). The
 * first filter now receives an empty array instead of the core list, so it can
 * add entries but no longer remove or relabel core ones.
 *
 * @package YayPricing\Product_Filter
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter;

use YAYDP\Abstracts\YAYDP_Product_Filter_Type;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Legacy_Product_Filter_Hooks {

	/**
	 * Register a type for every `yaydp_admin_product_filters` entry whose slug
	 * has no registration yet. Runs after the core types and before
	 * `yaydp_register_product_filters`, so a third-party slug beats the generic
	 * custom-taxonomy filter as it did before 3.5.8.
	 *
	 * @param YAYDP_Product_Filter_Registry $registry Registry.
	 */
	public static function register_filter_types( YAYDP_Product_Filter_Registry $registry ) {
		/**
		 * Legacy admin registration of a product filter type.
		 *
		 * @deprecated 3.5.8 Register a YAYDP_Product_Filter_Type on `yaydp_register_product_filters` instead.
		 * @param array $filters Entries of { value, label, comparations }.
		 */
		$entries = apply_filters( 'yaydp_admin_product_filters', array() );
		foreach ( (array) $entries as $entry ) {
			if ( empty( $entry['value'] ) || $registry->has( $entry['value'] ) ) {
				continue;
			}
			if ( defined( 'YAYDP_DEVELOPMENT' ) && YAYDP_DEVELOPMENT ) {
				_deprecated_hook( 'yaydp_admin_product_filters', '3.5.8', 'yaydp_register_product_filters', esc_html( "Product filter '{$entry['value']}'" ) );
			}
			$registry->register( new YAYDP_Legacy_Hook_Product_Filter( $entry ) );
		}
	}

	/**
	 * Admin remote search rows for a slug with no registered type.
	 *
	 * @param string $slug   Filter slug.
	 * @param string $search Search text.
	 * @param int    $page   Page.
	 * @param int    $limit  Page size.
	 * @return array
	 */
	public static function search_options( $slug, $search, $page, $limit ) {
		/**
		 * Legacy admin search for a product filter's values.
		 *
		 * @deprecated 3.5.8 Implement YAYDP_Product_Filter_Type::search_options() instead.
		 */
		return (array) apply_filters( "yaydp_admin_custom_filter_{$slug}_result", array(), $slug, $search, $page, $limit );
	}

	/**
	 * Matching products for a slug with no registered type.
	 *
	 * @param string $slug        Filter slug.
	 * @param array  $values      Stored values.
	 * @param string $comparation Comparator slug.
	 * @return array
	 */
	public static function matching_products( $slug, array $values, $comparation ) {
		/**
		 * Legacy matching-products lookup for a product filter.
		 *
		 * @deprecated 3.5.8 Implement YAYDP_Product_Filter_Type::matching_products() instead.
		 */
		return (array) apply_filters( "yaydp_get_matching_products_by_{$slug}", array(), $slug, $values, $comparation );
	}
}

/**
 * A product filter registered through the legacy filters. Only this file
 * instantiates it (the autoloader does not map it).
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound -- quarantined together with the hooks it serves.
final class YAYDP_Legacy_Hook_Product_Filter extends YAYDP_Product_Filter_Type {

	/**
	 * The `yaydp_admin_product_filters` entry.
	 *
	 * @var array
	 */
	private $entry;

	/**
	 * Wrap one legacy entry.
	 *
	 * @param array $entry Entry of { value, label, comparations }.
	 */
	public function __construct( array $entry ) {
		$this->entry = $entry;
	}

	public function slug() {
		return (string) $this->entry['value'];
	}

	public function label() {
		return isset( $this->entry['label'] ) ? (string) $this->entry['label'] : $this->slug();
	}

	public function comparators() {
		return isset( $this->entry['comparations'] ) ? (array) $this->entry['comparations'] : array();
	}

	/** Values were always picked through the custom-filter search endpoint. */
	public function editor() {
		return array(
			'kind'     => 'taxonomy',
			'taxonomy' => $this->slug(),
		);
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		/**
		 * Legacy evaluation hook for a product filter registered via `yaydp_admin_product_filters`.
		 *
		 * @deprecated 3.5.8 Implement YAYDP_Product_Filter_Type::check() instead.
		 * @param bool        $result  Match result (default false).
		 * @param \WC_Product $product Product under test.
		 * @param array       $filter  Stored filter.
		 */
		return (bool) apply_filters( "yaydp_check_condition_by_{$this->slug()}", false, $product, $filter );
	}

	public function search_options( $search, $page, $limit ) {
		return YAYDP_Legacy_Product_Filter_Hooks::search_options( $this->slug(), $search, $page, $limit );
	}

	public function matching_products( array $values, $comparation ) {
		return YAYDP_Legacy_Product_Filter_Hooks::matching_products( $this->slug(), $values, $comparation );
	}
}
