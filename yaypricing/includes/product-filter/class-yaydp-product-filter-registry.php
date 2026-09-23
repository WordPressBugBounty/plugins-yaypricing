<?php
/**
 * Product filter type registry.
 *
 * Single lookup for every product filter type the plugin (or an integration)
 * knows. Boots lazily on first use: core types are registered, then the
 * `yaydp_register_product_filters` action lets integrations add theirs, then
 * the legacy shim wraps pre-3.5.8 filter registrations. Evaluation asks the
 * type; a slug nobody registered never matches.
 *
 * @package YayPricing\Product_Filter
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter;

use YAYDP\Abstracts\YAYDP_Product_Filter_Type;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Product_Filter_Registry {

	/** Slug of the list-level price-criterion sub-filter. */
	const SUB_FILTER = 'sub_filter_product_price_criterion';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Registered types keyed by slug, in registration order.
	 *
	 * @var YAYDP_Product_Filter_Type[]
	 */
	private $types = array();

	/**
	 * Whether core + integration registration has run.
	 *
	 * @var bool
	 */
	private $booted = false;

	private function __construct() {}

	/**
	 * Shared instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register core types, then integrations, then legacy filter entries.
	 * Runs once on first use — never in a constructor, so integrations that
	 * discover their taxonomies on `init` still get their turn.
	 */
	private function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;
		YAYDP_Core_Product_Filter_Types::register_all( $this );
		// Third-party legacy entries claim their slugs first (core stays protected by the
		// has() check inside), so the generic custom-taxonomy filter still yields to them.
		YAYDP_Legacy_Product_Filter_Hooks::register_filter_types( $this );
		/**
		 * Register additional product filter types.
		 *
		 * @since 3.5.8
		 * @param YAYDP_Product_Filter_Registry $registry Registry to call register() on.
		 */
		do_action( 'yaydp_register_product_filters', $this );
	}

	/**
	 * Add a type. A duplicate slug replaces the earlier registration and is
	 * flagged with _doing_it_wrong() so it surfaces under WP_DEBUG.
	 *
	 * @param YAYDP_Product_Filter_Type $type Type instance.
	 * @return self
	 */
	public function register( YAYDP_Product_Filter_Type $type ) {
		$slug = $type->slug();
		if ( isset( $this->types[ $slug ] ) ) {
			_doing_it_wrong( __METHOD__, esc_html( "Product filter type '{$slug}' is already registered; the later registration wins." ), '3.5.8' );
		}
		$this->types[ $slug ] = $type;
		return $this;
	}

	/**
	 * Whether a type is registered for the slug.
	 *
	 * @param string $slug Filter slug.
	 * @return bool
	 */
	public function has( $slug ) {
		$this->boot();
		return isset( $this->types[ $slug ] );
	}

	/**
	 * Type registered for the slug, if any.
	 *
	 * @param string $slug Filter slug.
	 * @return YAYDP_Product_Filter_Type|null
	 */
	public function get( $slug ) {
		$this->boot();
		return isset( $this->types[ $slug ] ) ? $this->types[ $slug ] : null;
	}

	/**
	 * Every registered type keyed by slug.
	 *
	 * @return YAYDP_Product_Filter_Type[]
	 */
	public function all() {
		$this->boot();
		return $this->types;
	}

	/**
	 * Evaluate one filter. An unregistered slug never matches.
	 *
	 * @param array                        $filter  Stored filter.
	 * @param \WC_Product                  $product Product under test.
	 * @param YAYDP_Product_Filter_Context $ctx     Context.
	 * @return bool
	 */
	public function evaluate( array $filter, $product, YAYDP_Product_Filter_Context $ctx ) {
		$type = $this->get( isset( $filter['type'] ) ? $filter['type'] : '' );
		return $type ? (bool) $type->check( $product, $filter, $ctx ) : false;
	}

	/**
	 * Evaluate a filter list with its match type against one product.
	 *
	 * Semantics are the historical ones: the running result starts false
	 * (an empty list matches nothing), `any` stops at the first true, `all` at
	 * the first false; a price-criterion sub-filter in the list narrows the
	 * product/category/variation filters instead of matching on its own.
	 *
	 * @param array                        $filters    Stored filters.
	 * @param \WC_Product                  $product    Product under test.
	 * @param string                       $match_type 'any' | 'all'.
	 * @param YAYDP_Product_Filter_Context $ctx        Context (sub-filter is filled in here).
	 * @return bool
	 */
	public function evaluate_list( $filters, $product, $match_type, YAYDP_Product_Filter_Context $ctx ) {
		$filters    = (array) $filters;
		$sub_filter = null;
		foreach ( $filters as $filter ) {
			if ( isset( $filter['type'] ) && self::SUB_FILTER === $filter['type'] ) {
				$sub_filter = (array) $filter;
			}
		}
		$ctx   = $ctx->with_sub_filter( $sub_filter );
		$check = false;
		foreach ( $filters as $filter ) {
			$check = $this->evaluate( (array) $filter, $product, $ctx );
			if ( 'any' === $match_type ) {
				if ( $check ) {
					break;
				}
			} elseif ( ! $check ) {
				break;
			}
		}
		return $check;
	}

	/**
	 * Everything the admin app needs to list, edit and default a product
	 * filter: per type its label, comparators, editor descriptor and default.
	 *
	 * @return array { types: { slug: {...} } } in dropdown order.
	 */
	public function for_localize() {
		$types = array();
		foreach ( $this->all() as $slug => $type ) {
			$editor = $type->editor();
			if ( isset( $editor['options'] ) ) {
				$editor['options'] = array_values( (array) $editor['options'] );
			}
			$types[ $slug ] = array(
				'label'       => $type->label(),
				'comparators' => array_values( (array) $type->comparators() ),
				'editor'      => $editor,
				'default'     => $type->default_value(),
			);
		}
		return array( 'types' => $types );
	}
}
