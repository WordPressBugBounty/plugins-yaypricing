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
			$cart_fees = \WC()->cart->get_fees();
			if ( empty( $cart_fees[ $this->rule->get_id() ] ) ) {
				$this->rule->add_fee();
			}
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
		}
	}

}
