<?php
/**
 * Handle checkout fee adjustment
 *
 * @package YayPricing\SingleAdjustment
 *
 * @since 2.4
 */

namespace YAYDP\Core\Single_Adjustment;

/**
 * Declare class
 */
class YAYDP_Checkout_Fee_Adjustment extends \YAYDP\Abstracts\YAYDP_Adjustment {

	/**
	 * Contains current checking cart
	 *
	 * @var null|\YAYDP\Core\YAYDP_Cart
	 */
	protected $cart = null;

	/**
	 * Rule IDs applied directly to shipping in the latest calculation cycle.
	 * Needed because these rules do not create a WC cart fee, so use_time
	 * tracking cannot rely on \WC()->cart->get_fees().
	 *
	 * @var int[]
	 */
	protected static $applied_to_shipping_rule_ids = array();

	/**
	 * Reset the tracked apply_to_shipping rule IDs at the start of each
	 * checkout fee calculation cycle.
	 */
	public static function reset_applied_to_shipping_rule_ids() {
		self::$applied_to_shipping_rule_ids = array();
	}

	/**
	 * Get rule IDs applied directly to shipping.
	 *
	 * @return int[]
	 */
	public static function get_applied_to_shipping_rule_ids() {
		return self::$applied_to_shipping_rule_ids;
	}

	/**
	 * Constructor
	 *
	 * @override
	 *
	 * @param array                  $data Given data.
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function __construct( $data, $cart ) {
		parent::__construct( $data );
		$this->cart = $cart;
	}

	/**
	 * Calculate total discount amount that the rule can affect per order.
	 *
	 * @override
	 */
	public function get_total_discount_amount_per_order() {
		$total = $this->rule->get_total_discount_amount( $this->cart );
		return $total;
	}

	/**
	 * Check conditions of the current adjustment after other adjustments are applied
	 *
	 * @override
	 */
	public function check_conditions() {
		return $this->rule->check_conditions( $this->cart );
	}

	/**
	 * Retrieves cart
	 */
	public function get_cart() {
		return $this->cart;
	}

	/**
	 * Create fee based on rule data
	 */
	public function create_fee() {
		if ( empty( $this->rule->get_data()['apply_to_shipping']['enable'] ) ) {
			remove_filter( 'woocommerce_shipping_packages', array( $this->rule, 'adjust_shipping' ) );
			// Defer fee creation until after WooCommerce has calculated shipping,
			// otherwise WC()->cart->get_shipping_total() returns 0 and add_fee() bails out.
			add_action( 'woocommerce_cart_calculate_fees', function( $cart ) {
				$cart_fees = $cart->get_fees();
				if ( empty( $cart_fees[ $this->rule->get_id() ] ) ) {
					$this->rule->add_fee();
				}
			}, 20 );
			add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', function( $taxes, $fee ) {
				
				if ( $fee->object->id !== $this->rule->get_id() ) {
					return $taxes;
				}

				if ( $fee->object->amount < 0 && ! $fee->object->taxable ) {
					return [];
				}

				return $taxes;

			}, 100, 2 );
		} else {
			add_filter( 'woocommerce_shipping_packages', array( $this->rule, 'adjust_shipping' ) );
			$rule_id = $this->rule->get_id();
			if ( ! in_array( $rule_id, self::$applied_to_shipping_rule_ids, true ) ) {
				self::$applied_to_shipping_rule_ids[] = $rule_id;
			}
		}
	}

}
