<?php
/**
 * Pricing type registry
 *
 * Single definition of every `pricing.type` value a rule can store. The stored
 * strings are the data contract and never change; this class only gives them
 * meaning:
 *
 *   unit            money | percent | null   how the value is entered/displayed
 *   sign            -1 discount | +1 fee | 0 sets the unit price outright
 *   allows_maximum  whether pricing.maximum_value caps the per-unit adjustment
 *   requires_group  needs the bundled group's highest unit price (Bundle only)
 *
 * resolve() turns (type, price, value, maximum) into the new unit price. It is
 * the only place that knows the arithmetic of a type.
 *
 * @package YayPricing\Pricing_Type
 */

namespace YAYDP\Pricing_Type;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Pricing_Type_Registry {

	const MONEY   = 'money';
	const PERCENT = 'percent';

	/**
	 * Metadata per stored value. Labels are the same source strings the admin
	 * app translates through class-yaydp-i18n.php.
	 */
	const TABLE = array(
		'fixed_discount'      => array( 'label' => 'Fixed Discount', 'unit' => self::MONEY, 'sign' => -1, 'allows_maximum' => false, 'requires_group' => false ),
		'percentage_discount' => array( 'label' => 'Percentage Discount', 'unit' => self::PERCENT, 'sign' => -1, 'allows_maximum' => true, 'requires_group' => false ),
		'flat_price'          => array( 'label' => 'Flat Price', 'unit' => self::MONEY, 'sign' => -1, 'allows_maximum' => false, 'requires_group' => false ),
		'fixed_product'       => array( 'label' => 'Fixed Discount per Individual Cart Item', 'unit' => self::MONEY, 'sign' => -1, 'allows_maximum' => true, 'requires_group' => false ),
		'fixed_item_price'    => array( 'label' => 'Price Per Item', 'unit' => self::MONEY, 'sign' => 0, 'allows_maximum' => false, 'requires_group' => false ),
		'highest_item_price'  => array( 'label' => 'Highest Item Price', 'unit' => self::PERCENT, 'sign' => 0, 'allows_maximum' => false, 'requires_group' => true ),
		'free'                => array( 'label' => 'Free', 'unit' => null, 'sign' => -1, 'allows_maximum' => false, 'requires_group' => false ),
		'fixed_fee'           => array( 'label' => 'Fixed Fee', 'unit' => self::MONEY, 'sign' => 1, 'allows_maximum' => false, 'requires_group' => false ),
		'percentage_fee'      => array( 'label' => 'Percentage Fee', 'unit' => self::PERCENT, 'sign' => 1, 'allows_maximum' => true, 'requires_group' => false ),
	);

	/**
	 * Which values each rule type offers, in dropdown order. Mirrors what the
	 * admin app shipped before the registry existed; nothing new is exposed.
	 */
	const AVAILABILITY = array(
		'simple_adjustment'   => array( 'fixed_discount', 'percentage_discount', 'flat_price' ),
		'bogo'                => array( 'fixed_discount', 'percentage_discount', 'flat_price' ),
		'buy_x_get_y'         => array( 'fixed_discount', 'percentage_discount', 'flat_price' ),
		'bulk_pricing'        => array( 'fixed_discount', 'percentage_discount', 'flat_price' ),
		'tiered_pricing'      => array( 'fixed_discount', 'percentage_discount', 'flat_price' ),
		'product_bundle'      => array( 'fixed_discount', 'percentage_discount', 'flat_price', 'highest_item_price', 'fixed_item_price', 'free' ),
		'product_fee'         => array( 'fixed_fee', 'percentage_fee' ),
		'cart_discount'       => array( 'fixed_discount', 'percentage_discount', 'fixed_product' ),
		'shipping_fee'        => array( 'fixed_discount', 'percentage_discount' ),
		'custom_fee'          => array( 'fixed_fee', 'percentage_fee' ),
		'custom_shipping_fee' => array( 'fixed_fee', 'percentage_fee' ),
	);

	/**
	 * Metadata row for a stored value, or null for unknown/empty strings.
	 *
	 * @param string $type Stored pricing type.
	 * @return array|null
	 */
	public static function get( $type ) {
		return isset( self::TABLE[ $type ] ) ? self::TABLE[ $type ] : null;
	}

	public static function is_known( $type ) {
		return isset( self::TABLE[ $type ] );
	}

	/** 'money' | 'percent' | null (free). Unknown types read as money. */
	public static function unit( $type ) {
		$row = self::get( $type );
		return $row ? $row['unit'] : self::MONEY;
	}

	/** -1 discount, +1 fee, 0 sets the price. Unknown types read as discount. */
	public static function sign( $type ) {
		$row = self::get( $type );
		return $row ? $row['sign'] : -1;
	}

	public static function allows_maximum( $type ) {
		$row = self::get( $type );
		return $row ? $row['allows_maximum'] : false;
	}

	public static function requires_group( $type ) {
		$row = self::get( $type );
		return $row ? $row['requires_group'] : false;
	}

	/** Translated dropdown label; falls back to the raw value for unknown types. */
	public static function label( $type ) {
		$row = self::get( $type );
		// Labels are registered in class-yaydp-i18n.php; translate the literal key.
		return $row ? __( $row['label'], 'yaypricing' ) : (string) $type; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
	}

	/**
	 * Values a rule type may store, in dropdown order. Unknown rule types get
	 * the plain discount trio, matching the admin's historical default list.
	 *
	 * @param string $rule_type Rule type slug (product, cart or checkout).
	 * @return string[]
	 */
	public static function for_rule_type( $rule_type ) {
		return isset( self::AVAILABILITY[ $rule_type ] ) ? self::AVAILABILITY[ $rule_type ] : self::AVAILABILITY['simple_adjustment'];
	}

	/**
	 * Per-type arithmetic, computed once from each type's natural quantity.
	 *
	 * Discount/fee types are defined by an *amount* (fixed value or percentage
	 * of price); set-price types are defined by a *target* unit price. Each
	 * case computes its own primitive directly — never by round-tripping the
	 * other one through a subtraction, which would drift in floating point and
	 * flip cents — and derives the other with the single subtraction callers
	 * have always done themselves. The primitive is returned uncast so numeric
	 * strings from stored rules flow through exactly as before; only the derived
	 * quantity is cast (PHP 8 refuses arithmetic on '' and the legacy helper
	 * never computed it for fixed types).
	 *
	 * @return array{magnitude: float|int|string, target: float|int|string}
	 *   magnitude = legacy positive amount (value, % of price, or price − target)
	 *   target    = resulting unit price, NOT floored at zero.
	 */
	private static function parts( $type, $price, $value, $maximum, array $context ) {
		$cap = ( self::allows_maximum( $type ) && ! is_null( $maximum ) ) ? $maximum : PHP_INT_MAX;

		switch ( $type ) {
			case 'fixed_discount':
			case 'fixed_product':
				$magnitude = min( $cap, $value );
				return array( 'magnitude' => $magnitude, 'target' => (float) $price - (float) $magnitude );
			case 'percentage_discount':
				$magnitude = min( $cap, $price * $value / 100 );
				return array( 'magnitude' => $magnitude, 'target' => (float) $price - (float) $magnitude );
			case 'fixed_fee':
				return array( 'magnitude' => $value, 'target' => (float) $price + (float) $value );
			case 'percentage_fee':
				$magnitude = min( $cap, $price * $value / 100 );
				return array( 'magnitude' => $magnitude, 'target' => (float) $price + (float) $magnitude );
			case 'flat_price':
				// Never marks up: a flat price above the current price leaves it unchanged.
				$target = min( $price, $value );
				return array( 'magnitude' => (float) $price - (float) $target, 'target' => $target );
			case 'fixed_item_price':
				return array( 'magnitude' => (float) $price - (float) $value, 'target' => $value );
			case 'highest_item_price':
				$base = isset( $context['group_max_price'] ) ? $context['group_max_price'] : $price;
				// Same operation order as the original bundle math, so targets match to the last bit.
				$target = $base * ( (float) $value / 100 );
				return array( 'magnitude' => (float) $price - $target, 'target' => $target );
			case 'free':
				return array( 'magnitude' => $price, 'target' => 0 );
			default:
				// Unknown / legacy value: the old helper echoed the raw value back and the price stayed put.
				return array( 'magnitude' => $value, 'target' => $price );
		}
	}

	/**
	 * New unit price for one unit currently priced $price.
	 *
	 * Contract: the target is NOT floored at zero. A fixed discount larger than
	 * the price yields a negative target; callers that write a cart price floor
	 * it with max( 0, … ) exactly as they always have, while consumers that
	 * record the discount amount keep the full, unclamped amount the legacy
	 * helper produced. Negative pricing values (possible via formulas or
	 * imports) keep their sign, so a "−5 discount" still raises the price.
	 *
	 * @param string     $type    Stored pricing type.
	 * @param float      $price   Current unit price (or a pooled total for bundle math).
	 * @param float      $value   pricing.value.
	 * @param float|null $maximum pricing.maximum_value; null / PHP_INT_MAX = unlimited.
	 *                            Only honoured when the type allows a maximum.
	 * @param array      $context Optional. 'group_max_price' for highest_item_price.
	 * @return float|int|string Numeric; not cast so stored numeric strings pass through unchanged.
	 */
	public static function resolve( $type, $price, $value, $maximum = null, array $context = array() ) {
		return self::parts( $type, $price, $value, $maximum, $context )['target'];
	}

	/**
	 * Amount by which one unit moves: positive for discounts, negative for
	 * fees and markups, unclamped (see resolve()). Computed directly per type,
	 * never as price − resolve().
	 */
	public static function adjustment_per_unit( $type, $price, $value, $maximum = null, array $context = array() ) {
		$parts = self::parts( $type, $price, $value, $maximum, $context );
		return 1 === self::sign( $type ) ? -$parts['magnitude'] : $parts['magnitude'];
	}

	/**
	 * Legacy "adjustment amount" as YAYDP_Pricing_Helper::calculate_adjustment_amount()
	 * has always returned it: the positive magnitude for discounts and fees, the
	 * *target price* for flat_price, and the raw value for set-price / unknown
	 * types (which the old switch never really handled). Kept so the helper stays
	 * byte-identical; new code should use resolve() / adjustment_per_unit().
	 */
	public static function legacy_adjustment_amount( $type, $price, $value, $maximum = null ) {
		if ( ! self::is_known( $type ) || 0 === self::sign( $type ) ) {
			return $value;
		}
		$parts = self::parts( $type, $price, $value, $maximum, array() );
		return 'flat_price' === $type ? $parts['target'] : $parts['magnitude'];
	}

	/**
	 * Discount one unit of an item receives, with the caps every rule applies:
	 * amount discounts never exceed the unit price, a flat price is already the
	 * drop to its target, fees add (negative). Set-price types are not priced
	 * per item — the bundle engine reprices them as a group — but display
	 * consumers still ask; they get the historical figure, min( price, value ).
	 */
	public static function discount_per_item( $type, $price, $value, $maximum = null, array $context = array() ) {
		if ( 0 === self::sign( $type ) ) {
			return min( $price, $value );
		}
		$adjustment = self::adjustment_per_unit( $type, $price, $value, $maximum, $context );
		return self::is_amount_discount( $type ) ? min( $price, $adjustment ) : $adjustment;
	}

	/**
	 * Types whose value is a money amount to add or subtract (fixed discount /
	 * product / fee, price per item). Excludes flat_price, whose money value is a
	 * target, and unknown types.
	 */
	public static function is_money_amount( $type ) {
		return self::is_known( $type ) && self::MONEY === self::unit( $type ) && 'flat_price' !== $type;
	}

	/**
	 * Discount types whose amount is capped by the unit price (a $50 discount on
	 * a $10 item takes $10). Fees add, and flat_price is already a drop *to* a
	 * target, so neither is capped. Unknown types behave like a fixed discount,
	 * as the legacy code path did.
	 */
	public static function is_amount_discount( $type ) {
		return -1 === self::sign( $type ) && 'flat_price' !== $type;
	}

	/**
	 * Value a formula must evaluate to for "no change" when it cannot be
	 * evaluated: flat_price sets the price to min( price, value ) so an
	 * unreachable ceiling is neutral; every amount type is neutral at 0.
	 * Set-price types have no constant neutral value (it would be the current
	 * price) and never use formulas.
	 */
	public static function formula_neutral_value( $type ) {
		return 'flat_price' === $type ? PHP_INT_MAX : 0;
	}

	/**
	 * Min/max sale-display triple in the shape storefront consumers read.
	 */
	public static function display_bounds( $type, $value, $maximum ) {
		return array(
			'pricing_value' => $value,
			'pricing_type'  => $type,
			'maximum'       => $maximum,
		);
	}

	/** The "nothing guaranteed" triple used when conditions gate a rule. */
	public static function zero_bounds() {
		return self::display_bounds( 'fixed_discount', 0, 0 );
	}

	/**
	 * JavaScript expression (x = variation price) used by the variable-product
	 * frontend to recompute a rule's figures per variation.
	 *
	 * @param string $kind    'value' | 'amount' | 'discounted_price'.
	 * @param string $type    Stored pricing type.
	 * @param mixed  $value   Pricing value, already converted for display.
	 * @param mixed  $maximum Maximum, already converted for display (may be '' for unlimited money types
	 *                        — preserved verbatim from the previous builders).
	 */
	public static function js_formula( $kind, $type, $value, $maximum ) {
		$operator = 1 === self::sign( $type ) ? '+' : '-';
		if ( 'free' === $type ) {
			return 'discounted_price' === $kind ? '0' : ( 'amount' === $kind ? 'x' : '' );
		}
		if ( self::is_percentage_adjustment( $type ) ) {
			if ( 'value' === $kind ) {
				return '';
			}
			$amount = "Math.min( x * $value / 100, $maximum  )";
			return 'amount' === $kind ? $amount : "x $operator $amount";
		}
		if ( 'flat_price' === $type ) {
			return 'discounted_price' === $kind ? "Math.min( $value, x )" : "x - Math.min( $value, x )";
		}
		$amount = "Math.min( $value, $maximum )";
		return 'discounted_price' === $kind ? "x $operator $amount" : $amount;
	}

	/**
	 * Human string for a pricing value: "Free", "12.5%" or a WooCommerce price.
	 */
	public static function format_value( $value, $type ) {
		if ( 'free' === $type ) {
			return __( 'Free', 'yaypricing' );
		}
		if ( self::is_percentage_adjustment( $type ) ) {
			return "$value%";
		}
		// Note: highest_item_price is percent-denominated but has always been
		// rendered as a price here; kept as-is (display follow-up, not a refactor concern).
		return \wc_price( $value );
	}

	/**
	 * Percentage discount or fee — a percent applied to the current price.
	 * highest_item_price is percent-denominated too, but of the group's top
	 * price, so it is deliberately excluded (matches the historical predicate).
	 */
	public static function is_percentage_adjustment( $type ) {
		return self::is_known( $type ) && self::PERCENT === self::unit( $type ) && 0 !== self::sign( $type );
	}

	/**
	 * Payload for the admin app: every row (with translated label) plus the
	 * per-rule-type availability lists.
	 */
	public static function all_for_localize() {
		$types = array();
		foreach ( self::TABLE as $value => $row ) {
			$types[ $value ] = array_merge( $row, array( 'label' => self::label( $value ) ) );
		}
		return array(
			'types'        => $types,
			'availability' => self::AVAILABILITY,
		);
	}
}
