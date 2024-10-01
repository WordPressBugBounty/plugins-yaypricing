<?php
/**
 * YayPricing core functions
 *
 * Declare global functions
 *
 * @package YayPricing\Functions
 */

defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'yaydp_is_greater_than_comparison' ) ) {
	/**
	 * Compares if a comparison is "greater than"
	 *
	 * @param string $comparison Given comparison.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_is_greater_than_comparison( $comparison ) {
		return 'greater_than' === $comparison;
	}
}

if ( ! function_exists( 'yaydp_is_less_than_comparison' ) ) {
	/**
	 * Compares if a comparison is "less than"
	 *
	 * @param string $comparison Given comparison.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_is_less_than_comparison( $comparison ) {
		return 'less_than' === $comparison;
	}
}

if ( ! function_exists( 'yaydp_is_gte_comparison' ) ) {
	/**
	 * Compares if a comparison is "greater than or equal"
	 *
	 * @param string $comparison Given comparison.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_is_gte_comparison( $comparison ) {
		return 'gte' === $comparison;
	}
}

if ( ! function_exists( 'yaydp_is_lte_comparison' ) ) {
	/**
	 * Compares if a comparison is "less than or equal"
	 *
	 * @param string $comparison Given comparison.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_is_lte_comparison( $comparison ) {
		return 'lte' === $comparison;
	}
}

if ( ! function_exists( 'yaydp_compare_greater_than' ) ) {
	/**
	 * Compares if a value is greater than another value
	 *
	 * @param float $value1 The first value to compare.
	 * @param float $value2 The second value to compare.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_compare_greater_than( $value1, $value2 ) {
		return floatval( $value1 ) > floatval( $value2 );
	}
}

if ( ! function_exists( 'yaydp_compare_less_than' ) ) {
	/**
	 * Compares if a value is less than another value
	 *
	 * @param float $value1 The first value to compare.
	 * @param float $value2 The second value to compare.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_compare_less_than( $value1, $value2 ) {
		return floatval( $value1 ) < floatval( $value2 );
	}
}

if ( ! function_exists( 'yaydp_compare_gte' ) ) {
	/**
	 * Compares if a value is greater than or equal to another value
	 *
	 * @param float $value1 The first value to compare.
	 * @param float $value2 The second value to compare.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_compare_gte( $value1, $value2 ) {
		return floatval( $value1 ) >= floatval( $value2 );
	}
}

if ( ! function_exists( 'yaydp_compare_lte' ) ) {
	/**
	 * Compares if a value is less than or equal to another value
	 *
	 * @param float $value1 The first value to compare.
	 * @param float $value2 The second value to compare.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_compare_lte( $value1, $value2 ) {
		return floatval( $value1 ) <= floatval( $value2 );
	}
}

if ( ! function_exists( 'yaydp_compare_equal' ) ) {
	/**
	 * Compares if a value is less than or equal to another value
	 *
	 * @param float $value1 The first value to compare.
	 * @param float $value2 The second value to compare.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_compare_equal( $value1, $value2 ) {
		return floatval( $value1 ) === floatval( $value2 );
	}
}

if ( ! function_exists( 'yaydp_compare_numeric' ) ) {
	/**
	 * Compares two numeric values base on given comparison
	 *
	 * @param float  $value1 The first value to compare.
	 * @param float  $value2 The second value to compare.
	 * @param string $comparison Given comparison.
	 *
	 * @since 2.3
	 * @return bool
	 */
	function yaydp_compare_numeric( $value1, $value2, $comparison = 'equal' ) {
		$value1 = floatval( $value1 );
		$value2 = floatval( $value2 );
		switch ( $comparison ) {
			case 'greater_than':
				return $value1 > $value2;
			case 'less_than':
				return $value1 < $value2;
			case 'gte':
				return $value1 >= $value2;
			case 'lte':
				return $value1 <= $value2;
			default:
				return $value1 === $value2;
		}
	}
}
