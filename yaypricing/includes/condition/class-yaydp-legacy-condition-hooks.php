<?php
/**
 * Back-compat for pre-3.5.8 condition hooks. Removed in 3.6.0.
 *
 * The only file that knows the old third-party surface for conditions:
 *
 *   yaydp_extra_conditions           filter: admin entry { value, label, comparations, values }
 *   yaydp_check_{slug}_condition     filter: ( bool $result, array $condition, YAYDP_Condition_Context $ctx )
 *
 *   YAYDP\Helper\YAYDP_Condition_Helper            class: check_conditions(), check_list_conditions()
 *   YAYDP\Helper\YAYDP_Incomplete_Condition_Helper class: get_incomplete_conditions()
 *
 * Nothing else in the plugin references these names. Entries from the first
 * filter become YAYDP_Legacy_Filter_Condition types whose check() asks the
 * second; a slug nobody registers evaluates to false (fail-closed). The two
 * helper class names alias YAYDP_Legacy_Condition_Helper at the bottom, which
 * is why YayPricing::load_legacy_compat() requires this file eagerly.
 *
 * @package YayPricing\Condition
 * @since 3.5.8
 */

namespace YAYDP\Condition;

use YAYDP\Abstracts\YAYDP_Condition_Type;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Legacy_Condition_Hooks {

	/**
	 * Register a type for every `yaydp_extra_conditions` entry whose slug has
	 * no registration yet. Runs after `yaydp_register_conditions`.
	 *
	 * @param YAYDP_Condition_Registry $registry Registry.
	 */
	public static function register_filter_conditions( YAYDP_Condition_Registry $registry ) {
		/**
		 * Legacy admin registration of a condition type.
		 *
		 * @deprecated 3.5.8 Register a YAYDP_Condition_Type on `yaydp_register_conditions` instead.
		 * @param array $conditions Entries of { value, label, comparations, values }.
		 */
		$entries = apply_filters( 'yaydp_extra_conditions', array() );
		foreach ( (array) $entries as $entry ) {
			if ( empty( $entry['value'] ) || $registry->has( $entry['value'] ) ) {
				continue;
			}
			if ( defined( 'YAYDP_DEVELOPMENT' ) && YAYDP_DEVELOPMENT ) {
				_deprecated_hook( 'yaydp_extra_conditions', '3.5.8', 'yaydp_register_conditions', esc_html( "Condition '{$entry['value']}'" ) );
			}
			$registry->register( new YAYDP_Legacy_Filter_Condition( $entry ) );
		}
	}
}

/**
 * A condition registered through the legacy filters. Only this file
 * instantiates it (the autoloader does not map it).
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound -- quarantined together with the hooks it serves.
final class YAYDP_Legacy_Filter_Condition extends YAYDP_Condition_Type {

	/**
	 * The `yaydp_extra_conditions` entry.
	 *
	 * @var array
	 */
	private $entry;

	/**
	 * Wrap one legacy entry.
	 *
	 * @param array $entry Entry of { value, label, comparations, values }.
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

	public function editor() {
		return array(
			'kind'    => 'select',
			'options' => isset( $this->entry['values'] ) ? (array) $this->entry['values'] : array(),
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		/**
		 * Legacy evaluation hook for a condition registered via `yaydp_extra_conditions`.
		 *
		 * @deprecated 3.5.8 Implement YAYDP_Condition_Type::check() instead.
		 * @param bool                    $result    Match result (default false).
		 * @param array                   $condition Stored condition.
		 * @param YAYDP_Condition_Context $ctx       Evaluation context.
		 */
		return (bool) apply_filters( "yaydp_check_{$this->slug()}_condition", false, $condition, $ctx );
	}
}

/**
 * The three entry points of the pre-3.5.8 `YAYDP\Helper\YAYDP_Condition_Helper`
 * and `YAYDP_Incomplete_Condition_Helper`, kept for third-party callers. The
 * per-slug `check_*` / `get_incomplete_*` predicates are gone; their bodies now
 * live on the condition types. Both old class names alias this one (see the
 * class_alias() calls below), so the file must be loaded before any caller runs.
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound -- quarantined together with the hooks it serves.
final class YAYDP_Legacy_Condition_Helper {

	/**
	 * Whether the cart satisfies the rule's conditions.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Condition_Registry::instance()->evaluate_list().
	 * @param \YAYDP\Core\YAYDP_Cart      $cart Cart.
	 * @param \YAYDP\Abstracts\YAYDP_Rule $rule Rule.
	 * @return bool
	 */
	public static function check_conditions( $cart, $rule ) {
		_deprecated_function( 'YAYDP\Helper\YAYDP_Condition_Helper::check_conditions', '3.5.8', 'YAYDP_Condition_Registry::evaluate_list()' );
		return YAYDP_Condition_Registry::instance()->evaluate_list(
			$rule->get_conditions(),
			$rule->get_condition_match_type(),
			YAYDP_Condition_Context::from_rule( $cart, $rule )
		);
	}

	/**
	 * Evaluate a stored condition list against cart items.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Condition_Registry::instance()->evaluate_list().
	 * @param array                            $conditions Stored conditions.
	 * @param string                           $match_type 'any' | 'all'.
	 * @param array                            $cart_items Cart items.
	 * @param \YAYDP\Abstracts\YAYDP_Rule|null $rule       Rule, if any.
	 * @param \YAYDP\Core\YAYDP_Cart|null      $cart       Cart, if any.
	 * @return bool
	 */
	public static function check_list_conditions( $conditions, $match_type, $cart_items, $rule = null, $cart = null ) {
		_deprecated_function( 'YAYDP\Helper\YAYDP_Condition_Helper::check_list_conditions', '3.5.8', 'YAYDP_Condition_Registry::evaluate_list()' );
		return YAYDP_Condition_Registry::instance()->evaluate_list(
			$conditions,
			$match_type,
			YAYDP_Condition_Context::from_items( (array) $cart_items, $rule, $cart )
		);
	}

	/**
	 * Conditions the cart does not meet yet, for encouragement notices.
	 *
	 * @deprecated 3.5.8 Use YAYDP_Condition_Registry::instance()->incomplete_for().
	 * @param \YAYDP\Core\YAYDP_Cart      $cart Cart.
	 * @param \YAYDP\Abstracts\YAYDP_Rule $rule Rule.
	 * @return array
	 */
	public static function get_incomplete_conditions( $cart, $rule ) {
		_deprecated_function( 'YAYDP\Helper\YAYDP_Incomplete_Condition_Helper::get_incomplete_conditions', '3.5.8', 'YAYDP_Condition_Registry::incomplete_for()' );
		return YAYDP_Condition_Registry::instance()->incomplete_for(
			$rule->get_conditions(),
			$rule->get_condition_match_type(),
			YAYDP_Condition_Context::from_rule( $cart, $rule )
		);
	}

	/**
	 * The per-slug predicates (check_cart_subtotal_price(), get_incomplete_cart_quantity(), …)
	 * moved onto the condition types and have no forwarder; fail loudly with the reason
	 * rather than "undefined method".
	 *
	 * @param string $name Method name.
	 * @param array  $args Arguments.
	 * @throws \BadMethodCallException Always.
	 */
	public static function __callStatic( $name, $args ) {
		throw new \BadMethodCallException( esc_html( "YAYDP_Condition_Helper::{$name}() was removed in 3.5.8; evaluate through YAYDP_Condition_Registry or the condition type in includes/condition/type/." ) );
	}
}

class_alias( YAYDP_Legacy_Condition_Helper::class, 'YAYDP\Helper\YAYDP_Condition_Helper' );
class_alias( YAYDP_Legacy_Condition_Helper::class, 'YAYDP\Helper\YAYDP_Incomplete_Condition_Helper' );
