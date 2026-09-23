<?php
/**
 * Condition type registry.
 *
 * Single lookup for every rule condition type the plugin (or an integration)
 * knows. Boots lazily on first use: core types are registered, then the
 * `yaydp_register_conditions` action lets integrations add theirs, then the
 * legacy shim wraps pre-3.5.8 filter registrations. Evaluation asks the type;
 * a slug nobody registered never matches.
 *
 * @package YayPricing\Condition
 * @since 3.5.8
 */

namespace YAYDP\Condition;

use YAYDP\Abstracts\YAYDP_Condition_Type;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Condition_Registry {

	const FAMILY_PRODUCT_PRICING = 'product_pricing';
	const FAMILY_CART_DISCOUNT   = 'cart_discount';
	const FAMILY_CHECKOUT_FEE    = 'checkout_fee';
	const FAMILY_EXCLUDE         = 'exclude';
	const FAMILY_COMBINED_ITEMS  = 'combined_items';

	/** Rule families a type is offered to by default. */
	const FAMILIES = array(
		self::FAMILY_PRODUCT_PRICING,
		self::FAMILY_CART_DISCOUNT,
		self::FAMILY_CHECKOUT_FEE,
		self::FAMILY_EXCLUDE,
	);

	const GROUP_CART             = 'cart';
	const GROUP_CART_ITEMS       = 'cart_items';
	const GROUP_CUSTOMER         = 'customer';
	const GROUP_PURCHASE_HISTORY = 'purchase_history';
	const GROUP_OTHERS           = 'others';
	const GROUP_COMBINATION      = 'combination';
	const GROUP_ITEMS_CART       = 'items_cart';
	const GROUP_ITEMS_HISTORY    = 'items_history';
	const GROUP_ITEMS_COMBINED   = 'items_combined';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Registered types keyed by slug, in registration order.
	 *
	 * @var YAYDP_Condition_Type[]
	 */
	private $types = array();

	/**
	 * Whether core + integration registration has run.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Slugs registered by the plugin itself (before integrations ran).
	 *
	 * @var string[]
	 */
	private $core_slugs = array();

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
	 * Register core types, then let integrations register theirs. Runs once,
	 * on first use — never in a constructor, so integrations loaded later in
	 * YayPricing::includes() still get their turn.
	 */
	private function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;
		YAYDP_Core_Condition_Types::register_all( $this );
		$this->core_slugs = array_keys( $this->types );
		/**
		 * Register additional condition types.
		 *
		 * @since 3.5.8
		 * @param YAYDP_Condition_Registry $registry Registry to call register() on.
		 */
		do_action( 'yaydp_register_conditions', $this );
		YAYDP_Legacy_Condition_Hooks::register_filter_conditions( $this );
	}

	/**
	 * Whether the slug is one of the plugin's own types (not an integration's).
	 *
	 * @param string $slug Condition slug.
	 * @return bool
	 */
	public function is_core( $slug ) {
		$this->boot();
		return in_array( $slug, $this->core_slugs, true );
	}

	/**
	 * Dropdown groups in display order: slug => translated label.
	 *
	 * @return array
	 */
	public static function groups() {
		return array(
			self::GROUP_CART             => __( 'Cart', 'yaypricing' ),
			self::GROUP_CART_ITEMS       => __( 'Cart items', 'yaypricing' ),
			self::GROUP_CUSTOMER         => __( 'Customer', 'yaypricing' ),
			self::GROUP_PURCHASE_HISTORY => __( 'Purchase history', 'yaypricing' ),
			self::GROUP_OTHERS           => __( 'Checkout', 'yaypricing' ),
			self::GROUP_COMBINATION      => __( 'Combination', 'yaypricing' ),
			self::GROUP_ITEMS_CART       => __( 'Cart items', 'yaypricing' ),
			self::GROUP_ITEMS_HISTORY    => __( 'Order history items', 'yaypricing' ),
			self::GROUP_ITEMS_COMBINED   => __( 'Item totals', 'yaypricing' ),
		);
	}

	/**
	 * Add a type. A duplicate slug replaces the earlier registration (core
	 * registers first, so an integration can override a core type) and is
	 * flagged with _doing_it_wrong() so it surfaces under WP_DEBUG without
	 * taking the storefront down mid-request.
	 *
	 * @param YAYDP_Condition_Type $type Type instance.
	 * @return self
	 */
	public function register( YAYDP_Condition_Type $type ) {
		$slug = $type->slug();
		if ( isset( $this->types[ $slug ] ) ) {
			_doing_it_wrong( __METHOD__, esc_html( "Condition type '{$slug}' is already registered; the later registration wins." ), '3.5.8' );
		}
		$this->types[ $slug ] = $type;
		return $this;
	}

	/**
	 * Whether a type is registered for the slug.
	 *
	 * @param string $slug Condition slug.
	 * @return bool
	 */
	public function has( $slug ) {
		$this->boot();
		return isset( $this->types[ $slug ] );
	}

	/**
	 * Type registered for the slug, if any.
	 *
	 * @param string $slug Condition slug.
	 * @return YAYDP_Condition_Type|null
	 */
	public function get( $slug ) {
		$this->boot();
		return isset( $this->types[ $slug ] ) ? $this->types[ $slug ] : null;
	}

	/**
	 * Every registered type keyed by slug.
	 *
	 * @return YAYDP_Condition_Type[]
	 */
	public function all() {
		$this->boot();
		return $this->types;
	}

	/**
	 * Types offered to a rule family, in registration order.
	 *
	 * @param string $family Family slug.
	 * @return YAYDP_Condition_Type[] keyed by slug.
	 */
	public function for_family( $family ) {
		return array_filter(
			$this->all(),
			function ( $type ) use ( $family ) {
				return in_array( $family, $type->families(), true );
			}
		);
	}

	/**
	 * Group slug a type is listed under, normalised to a known group.
	 *
	 * @param YAYDP_Condition_Type $type Type.
	 * @return string
	 */
	public function group_of( YAYDP_Condition_Type $type ) {
		$group = $type->group();
		return isset( self::groups()[ $group ] ) ? $group : self::GROUP_OTHERS;
	}

	/**
	 * Evaluate one condition. An unregistered slug (its plugin is gone) never
	 * matches, so a rule does not silently start applying to everyone.
	 *
	 * @param array                   $condition Stored condition.
	 * @param YAYDP_Condition_Context $ctx       Context.
	 * @return bool
	 */
	public function evaluate( array $condition, YAYDP_Condition_Context $ctx ) {
		$type = $this->type_for( $condition );
		return $type ? (bool) $type->check( $condition, $ctx ) : false;
	}

	/**
	 * Type that evaluates a stored condition at rule level.
	 *
	 * @param array $condition Stored condition.
	 * @return YAYDP_Condition_Type|null
	 */
	private function type_for( array $condition ) {
		return $this->get( isset( $condition['type'] ) ? $condition['type'] : '' );
	}

	/**
	 * Evaluate a condition list with the rule's match type.
	 *
	 * Semantics are the historical ones: the running result starts true (so an
	 * empty list passes), `any` stops at the first true, `all` at the first false.
	 *
	 * Malformed stored data (a non-array list or element) is tolerated the way
	 * the old loop tolerated it: it evaluates as an unknown condition → false.
	 *
	 * @param array                   $conditions Stored conditions.
	 * @param string                  $match_type 'any' | 'all'.
	 * @param YAYDP_Condition_Context $ctx        Context.
	 * @return bool
	 */
	public function evaluate_list( $conditions, $match_type, YAYDP_Condition_Context $ctx ) {
		$check = true;
		foreach ( (array) $conditions as $condition ) {
			$check = $this->evaluate( (array) $condition, $ctx );
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
	 * Everything the admin app needs to list, edit and default a condition:
	 * dropdown groups in order, and per type its label, tooltip, group,
	 * families, comparators, editor descriptor and default value.
	 *
	 * @return array { groups: [ { value, label } ], types: { slug: {...} } }
	 */
	public function for_localize() {
		$groups = array();
		foreach ( self::groups() as $slug => $label ) {
			$groups[] = array(
				'value' => $slug,
				'label' => $label,
			);
		}
		$types = array();
		foreach ( $this->all() as $slug => $type ) {
			// Lists must serialise as JSON arrays even when a type built them with keys.
			$editor = $type->editor();
			if ( isset( $editor['options'] ) ) {
				$editor['options'] = array_values( (array) $editor['options'] );
			}
			$types[ $slug ] = array(
				'label'       => $type->label(),
				'tooltip'     => $type->tooltip(),
				'group'       => $this->group_of( $type ),
				'families'    => array_values( $type->families() ),
				'comparators' => array_values( (array) $type->comparators() ),
				'editor'      => $editor,
				'default'     => $type->default_value(),
			);
		}
		return array(
			'groups' => $groups,
			'types'  => $types,
		);
	}

	/**
	 * Encouraged-notice payloads for a rule's conditions, in display order.
	 * Types without a notice must still pass; when they don't, there is nothing
	 * to encourage and the result is empty.
	 *
	 * @param array                   $conditions Stored conditions.
	 * @param string                  $match_type 'any' | 'all'.
	 * @param YAYDP_Condition_Context $ctx        Context.
	 * @return array
	 */
	public function incomplete_for( $conditions, $match_type, YAYDP_Condition_Context $ctx ) {
		$notices   = array();
		$remaining = array();
		foreach ( (array) $conditions as $condition ) {
			$condition = (array) $condition;
			$type      = $this->type_for( $condition );
			$notice    = $type ? $type->incomplete( $condition, $ctx ) : null;
			if ( is_null( $notice ) ) {
				$remaining[] = $condition;
			} elseif ( ! empty( $notice ) ) {
				$notices[] = $notice;
			}
		}
		if ( ! $this->evaluate_list( $remaining, $match_type, $ctx ) ) {
			return array();
		}
		$priorities = $this->incomplete_priorities();
		usort(
			$notices,
			function ( $a, $b ) use ( $priorities ) {
				$pa = isset( $priorities[ $a['type'] ] ) ? $priorities[ $a['type'] ] : 999;
				$pb = isset( $priorities[ $b['type'] ] ) ? $priorities[ $b['type'] ] : 999;
				return $pa <=> $pb;
			}
		);
		return $notices;
	}

	/**
	 * Notice key => display priority, for every type that names a key.
	 *
	 * @return array
	 */
	private function incomplete_priorities() {
		$priorities = array();
		foreach ( $this->all() as $type ) {
			if ( '' !== $type::INCOMPLETE_KEY ) {
				$priorities[ $type::INCOMPLETE_KEY ] = $type->incomplete_priority();
			}
		}
		return $priorities;
	}
}
