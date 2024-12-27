<?php
/**
 * Handle product pricing adjustment
 *
 * @package YayPricing\SingleAdjustment
 *
 * @since 2.4
 */

namespace YAYDP\Core\Single_Adjustment;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Adjustment extends \YAYDP\Abstracts\YAYDP_Adjustment {

	/**
	 * Contains Discountable items
	 * It only has value if this adjustment is for Simple Adjustment rule or Bulk Pricing rule
	 *
	 * @var array
	 */
	protected $discountable_items = array();

	/**
	 * Contains acceptable bought cases
	 * It only has value if this adjustment is for BOGO rule or Buy X Get Y rule
	 *
	 * @var array
	 */
	protected $bought_cases = array();

	/**
	 * Contains acceptable receive cases
	 * It only has value if this adjustment is for BOGO rule or Buy X Get Y rule
	 *
	 * @var array
	 */
	protected $receive_cases = array();

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
		$this->discountable_items = isset( $data['discountable_items'] ) ? $data['discountable_items'] : array();
		$this->bought_cases       = isset( $data['bought_cases'] ) ? $data['bought_cases'] : array();
		$this->receive_cases      = isset( $data['receive_cases'] ) ? $data['receive_cases'] : array();
		$this->cart               = $cart;
	}

	/**
	 * Calculate total discount amount that the rule can affect per order.
	 *
	 * @override
	 */
	public function get_total_discount_amount_per_order() {
		$total = 0;
		if ( \yaydp_is_bogo( $this->rule ) || \yaydp_is_buy_x_get_y( $this->rule ) ) {
			$total = $this->rule->get_total_discount_amount( $this );
		} else {
			foreach ( $this->discountable_items as $item ) {
				$discount_per_item = $this->rule->get_discount_amount_per_item( $item );
				$item_quantity     = $item->get_quantity();
				$total            += $discount_per_item * $item_quantity;
			}
		}
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
	 * Apply this adjustment to the cart
	 */
	public function apply_to_cart() {

		$rule_data                    = $this->rule->get_data();
		$is_applying_to_first_product = $rule_data['apply_to_first_matching_product'] ?? false;
		if ( $is_applying_to_first_product ) {
			$this->discountable_items = array_slice( $this->discountable_items, 0, 1 );
			/**
			 * No need to check with buy x get y or bogo
			 *
			 * @deprecated
			 * @since 3.4.2
			 */
			// if ( ! empty( $this->receive_cases['case'] ) ) {
			// 	foreach ( $this->receive_cases['case'] as $index => $case ) {
			// 		$this->receive_cases['case'][ $index ]['items'] = array_slice( $this->receive_cases['case'][ $index ]['items'] ?? array(), 0, 1 );
			// 	}
			// }
		}

		if ( \yaydp_product_pricing_is_applied_to_non_discount_product() ) {
			if ( isset( $this->receive_cases['case'] ) ) {
				$this->receive_cases['case'] = array_map(
					function( $case ) {
						$case['items'] = array_filter(
							$case['items'],
							function( $item ) {
								$item_product = null;
								if ( $item instanceof \YAYDP\Core\YAYDP_Cart_Item ) {
									$item_product = $item->get_product();
								}
								if ( is_numeric( $item ) || is_string( $item ) ) {
									$item_product = \wc_get_product( $item );
								}
								return $item_product && ! \YAYDP\Core\Discounted_Products\YAYDP_Discounted_Products::get_instance()->is_discounted( $item_product );
							}
						);
						return $case;
					},
					$this->receive_cases['case']
				);
			}
		}

		if ( \yaydp_is_bogo( $this->rule ) || \yaydp_is_buy_x_get_y( $this->rule ) ) {
			$this->rule->discount_items( $this );
		} elseif ( \yaydp_is_product_bundle( $this->rule ) ) {
			$this->rule->discount_for_product_bundle_item( $this );
		} elseif ( \yaydp_is_tiered_pricing( $this->rule ) ) {
			$this->rule->discount_item( $this );
		} else {
			foreach ( $this->discountable_items as $item ) {
				$this->rule->discount_item( $item );
			}
		}
	}

	/**
	 * Retrieves discountable items
	 */
	public function get_discountable_items() {
		return $this->discountable_items;
	}

	/**
	 * Retrieves bought cases
	 */
	public function get_bought_cases() {
		return $this->bought_cases;
	}

	/**
	 * Retrieves receive cases
	 */
	public function get_receive_cases() {
		return $this->receive_cases;
	}

	/**
	 * Retrieves cart
	 */
	public function get_cart() {
		return $this->cart;
	}

}
