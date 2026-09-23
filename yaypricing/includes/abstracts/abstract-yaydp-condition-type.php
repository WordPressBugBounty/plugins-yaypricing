<?php
/**
 * Base class for a rule condition type.
 *
 * One subclass per stored `conditions.logics[].type` value. The subclass owns
 * everything the plugin needs to know about that type: how the admin lists it
 * (label, group, families, comparators, editor, default) and how the engine
 * evaluates it (check). The slug is the stored value and never changes.
 *
 * @package YayPricing\Abstracts
 * @since 3.5.8
 */

namespace YAYDP\Abstracts;

use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

abstract class YAYDP_Condition_Type {

	/**
	 * Key this type emits in encouraged-notice payloads. Frozen: the notice
	 * renderers and third-party notice filters key on it. '' = no notice.
	 */
	const INCOMPLETE_KEY = '';

	/**
	 * Stored value of `condition.type`. Frozen: existing rules reference it.
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
	 * Optional help text shown next to the label in the admin dropdown.
	 *
	 * @return string|null
	 */
	public function tooltip() {
		return null;
	}

	/**
	 * Dropdown group slug (see YAYDP_Condition_Registry::groups()). Unknown
	 * slugs are normalised to the "others" group when the type is registered.
	 *
	 * @return string
	 */
	public function group() {
		return YAYDP_Condition_Registry::GROUP_OTHERS;
	}

	/**
	 * Rule families this type is offered to. Defaults to every family; core
	 * types opt out of the ones they never supported (e.g. exclude).
	 *
	 * @return string[]
	 */
	public function families() {
		return YAYDP_Condition_Registry::FAMILIES;
	}

	/**
	 * Ordered comparator list: array of [ 'value' => mixed, 'label' => string ].
	 * Values are the stored `condition.comparation` strings (booleans for
	 * logged_customer). Build them with YAYDP_Comparators.
	 *
	 * @return array
	 */
	abstract public function comparators();

	/**
	 * Value-editor descriptor for the admin: [ 'kind' => string, ...extra ].
	 * Kinds: none | number | days | date | source | select | combined.
	 *
	 * @return array
	 */
	public function editor() {
		return array( 'kind' => 'none' );
	}

	/**
	 * Default `condition.value` for a freshly added condition. `null` lets the
	 * admin derive one from the editor (first source option, today's date).
	 *
	 * @return mixed
	 */
	public function default_value() {
		return null;
	}

	/**
	 * Evaluate the condition against the current cart/customer.
	 *
	 * @param array                   $condition Stored condition (type, comparation, value).
	 * @param YAYDP_Condition_Context $ctx       Evaluation context.
	 * @return bool
	 */
	abstract public function check( array $condition, YAYDP_Condition_Context $ctx );

	/**
	 * Encouraged-notice payload: null when the type has no notice (the
	 * condition is then evaluated normally), an empty array when satisfied or
	 * nothing useful to say, else [ 'type' => INCOMPLETE_KEY, 'missing_value' => … ].
	 *
	 * @param array                   $condition Stored condition.
	 * @param YAYDP_Condition_Context $ctx       Evaluation context.
	 * @return array|null
	 */
	public function incomplete( array $condition, YAYDP_Condition_Context $ctx ) {
		return null;
	}

	/**
	 * Display order among incomplete notices (lower first).
	 *
	 * @return int
	 */
	public function incomplete_priority() {
		return 999;
	}

	/**
	 * Notice payload for a numeric condition the shopper can still reach:
	 * how much more is needed for a greater-than / at-least comparator.
	 *
	 * @param float|int $actual    Measured value.
	 * @param array     $condition Stored condition.
	 * @return array Payload, or empty when satisfied / not reachable by adding more.
	 */
	protected function shortfall( $actual, array $condition ) {
		if ( \YAYDP\Condition\YAYDP_Comparators::compare_numeric( $actual, $condition ) ) {
			return array();
		}
		if ( ! \yaydp_is_greater_than_comparison( $condition['comparation'] ) && ! \yaydp_is_gte_comparison( $condition['comparation'] ) ) {
			return array();
		}
		return array(
			'type'          => static::INCOMPLETE_KEY,
			'missing_value' => $condition['value'] - $actual + 1,
		);
	}

	/**
	 * Frontend features a rule using this type needs enqueued
	 * (e.g. 'payment', 'shipping', 'billing_email').
	 *
	 * @return string[]
	 */
	public function frontend_requirements() {
		return array();
	}
}
