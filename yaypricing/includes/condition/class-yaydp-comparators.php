<?php
/**
 * Comparator vocabulary shared by condition types.
 *
 * The stored `condition.comparation` strings are the data contract. This class
 * gives each one a default label and offers the recurring sets as ready-made
 * ordered lists, plus the two predicate idioms every list/number type repeats.
 *
 * @package YayPricing\Condition
 * @since 3.5.8
 */

namespace YAYDP\Condition;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Comparators {

	const IN_LIST      = 'in_list';
	const NOT_IN_LIST  = 'not_in_list';
	const CONTAIN_ALL  = 'contain_all';
	const CONTAIN      = 'contain';
	const CONTAIN_ONLY = 'contain_only';
	const NOT_CONTAIN  = 'not_contain';
	const GREATER_THAN = 'greater_than';
	const LESS_THAN    = 'less_than';
	const GTE          = 'gte';
	const LTE          = 'lte';
	const EQUAL        = 'equal';
	const BEFORE       = 'before';
	const AFTER        = 'after';
	const ON           = 'on';
	const IN_RANGE     = 'in_range';

	/**
	 * Translated default label for a comparator slug.
	 *
	 * @param string $slug Comparator slug.
	 * @return string
	 */
	public static function label( $slug ) {
		$labels = array(
			self::IN_LIST      => __( 'In list', 'yaypricing' ),
			self::NOT_IN_LIST  => __( 'Not in list', 'yaypricing' ),
			self::CONTAIN_ALL  => __( 'Contains all of', 'yaypricing' ),
			self::CONTAIN      => __( 'Contains any of', 'yaypricing' ),
			self::CONTAIN_ONLY => __( 'Contains only', 'yaypricing' ),
			self::NOT_CONTAIN  => __( 'Does not contain', 'yaypricing' ),
			self::GREATER_THAN => __( 'Greater than', 'yaypricing' ),
			self::LESS_THAN    => __( 'Less than', 'yaypricing' ),
			self::GTE          => __( 'Greater than or equal to', 'yaypricing' ),
			self::LTE          => __( 'Less than or equal to', 'yaypricing' ),
			self::EQUAL        => __( 'Equal to', 'yaypricing' ),
			self::BEFORE       => __( 'Before', 'yaypricing' ),
			self::AFTER        => __( 'After', 'yaypricing' ),
			self::ON           => __( 'On', 'yaypricing' ),
			self::IN_RANGE     => __( 'In range', 'yaypricing' ),
		);
		return isset( $labels[ $slug ] ) ? $labels[ $slug ] : (string) $slug;
	}

	/**
	 * Ordered [ value, label ] list for the given slugs. Pass `slug => label`
	 * pairs (label already translated) to override individual labels.
	 *
	 * @param array $slugs Comparator slugs, optionally keyed with label overrides.
	 * @return array
	 */
	public static function pick( array $slugs ) {
		$list = array();
		foreach ( $slugs as $key => $value ) {
			$list[] = is_int( $key )
				? array(
					'value' => $value,
					'label' => self::label( $value ),
				)
				: array(
					'value' => $key,
					'label' => $value,
				);
		}
		return $list;
	}

	/** In list / not in list. */
	public static function in_list() {
		return self::pick( array( self::IN_LIST, self::NOT_IN_LIST ) );
	}

	/** Contain all / contain / not contain. */
	public static function contain() {
		return self::pick( array( self::CONTAIN_ALL, self::CONTAIN, self::NOT_CONTAIN ) );
	}

	/** Greater / less / gte / lte, in the order the admin lists them. */
	public static function numeric() {
		return self::pick( array( self::GREATER_THAN, self::LESS_THAN, self::GTE, self::LTE ) );
	}

	/**
	 * Before / after / on / in range. The range label differs between types.
	 *
	 * @param string|null $in_range_label Translated label for the range comparator.
	 */
	public static function date( $in_range_label = null ) {
		$range = is_null( $in_range_label ) ? self::IN_RANGE : array( self::IN_RANGE => $in_range_label );
		return self::pick( array_merge( array( self::BEFORE, self::AFTER, self::ON ), (array) $range ) );
	}

	/** Logged in / not logged in — stored as booleans. */
	public static function boolean() {
		return array(
			array(
				'value' => true,
				'label' => __( 'Logged in', 'yaypricing' ),
			),
			array(
				'value' => false,
				'label' => __( 'Not logged in', 'yaypricing' ),
			),
		);
	}

	/**
	 * Resolve an in-list membership against the comparator.
	 *
	 * @param bool  $in_list   Whether the subject is in the configured list.
	 * @param array $condition Stored condition.
	 * @return bool
	 */
	public static function matches_list( $in_list, array $condition ) {
		return self::IN_LIST === $condition['comparation'] ? $in_list : ! $in_list;
	}

	/**
	 * Resolve a contain-family comparator from the intersection of the subject
	 * with the configured list. Unknown comparators never match.
	 *
	 * @param array $intersection Elements of the list found on the subject.
	 * @param int   $list_count   Size the intersection must reach for contain_all.
	 * @param array $condition    Stored condition.
	 * @return bool
	 */
	public static function matches_contain( array $intersection, $list_count, array $condition ) {
		switch ( $condition['comparation'] ) {
			case self::CONTAIN_ALL:
				return count( $intersection ) === $list_count;
			case self::CONTAIN:
				return ! empty( $intersection );
			case self::NOT_CONTAIN:
				return empty( $intersection );
			default:
				return false;
		}
	}

	/**
	 * The wc_get_orders() date arguments for a date comparator: before / after /
	 * in_range (value = [from, to]) / on (default, exact creation date).
	 *
	 * @param array $condition Stored condition.
	 * @return array
	 */
	public static function order_date_args( array $condition ) {
		$value = $condition['value'];
		switch ( $condition['comparation'] ) {
			case self::BEFORE:
				return array( 'date_before' => $value );
			case self::AFTER:
				return array( 'date_after' => $value );
			case self::IN_RANGE:
				return array(
					'date_after'  => isset( $value[0] ) ? $value[0] : '',
					'date_before' => isset( $value[1] ) ? $value[1] : '',
				);
			default:
				return array( 'date_created' => $value );
		}
	}

	/**
	 * Compare a measured number against the condition's value and comparator.
	 *
	 * @param float|int $actual    Measured value.
	 * @param array     $condition Stored condition.
	 * @return bool
	 */
	public static function compare_numeric( $actual, array $condition ) {
		return \yaydp_compare_numeric( $actual, $condition['value'], $condition['comparation'] );
	}
}
