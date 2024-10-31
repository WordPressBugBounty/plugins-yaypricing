<?php
/**
 * Managing product pricing adjustments
 *
 * @package YayPricing\Abstracts
 *
 * @since 2.4
 */

namespace YAYDP\Core\Adjustments;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Adjustments extends \YAYDP\Abstracts\YAYDP_Adjustments {

	/**
	 * Contains current cart
	 */
	protected $cart = null;

	/**
	 * Constructor
	 *
	 * @param \YAYDP\Core\YAYDP_Cart $cart Cart.
	 */
	public function __construct( $cart ) {
		$this->cart = $cart;
	}

	/**
	 * Collect adjustment
	 *
	 * @overrideget_shop_coupon_data
	 */
	public function collect() {
		$running_rules = \yaydp_get_running_product_pricing_rules();
		foreach ( $running_rules as $rule ) {
			$adjustment = $rule->create_possible_adjustment_from_cart( $this->cart );
			if ( ! empty( $adjustment ) ) {
				$adjustment_instance = new \YAYDP\Core\Single_Adjustment\YAYDP_Product_Pricing_Adjustment( $adjustment, $this->cart );
				if ( ! $adjustment_instance->check_conditions() ) {
					continue;
				}

				$this->marks_discounted_products( $adjustment );
				parent::add_adjustment( $adjustment_instance );
			}
		}

		if ( empty( $this->adjustments ) ) {
			return;
		}

		if ( \yaydp_product_pricing_is_applied_to_maximum_amount_per_order() ) {
			parent::sort_by_desc_amount();
		}

		if ( \yaydp_product_pricing_is_applied_to_minimum_amount_per_order() ) {
			parent::sort_by_asc_amount();
		}

	}

	/**
	 * Apply adjustments
	 *
	 * @override
	 */
	public function apply() {
		foreach ( $this->adjustments as $adjustment ) {
			if ( ! $adjustment->check_conditions() ) {
				continue;
			}

			$adjustment->apply_to_cart();

			if ( \yaydp_product_pricing_is_applied_first_rules() ) {
				break;
			}

			if ( \yaydp_product_pricing_is_applied_to_maximum_amount_per_order() ) {
				break;
			}

			if ( \yaydp_product_pricing_is_applied_to_minimum_amount_per_order() ) {
				break;
			}
		}
	}

	/**
	 * Get cart
	 */
	public function get_cart() {
		return $this->cart;
	}

	public function marks_discounted_products( $adjustment ) {

		if ( ! \yaydp_product_pricing_is_applied_to_non_discount_product() ) {
			return;
		}

		$discountable_items = $adjustment['discountable_items'] ?? array();

		foreach ( $discountable_items as $item ) {
			$product = $item->get_product();
			\YAYDP\Core\Discounted_Products\YAYDP_Discounted_Products::get_instance()->add_product( $product );
		}
	}

}
