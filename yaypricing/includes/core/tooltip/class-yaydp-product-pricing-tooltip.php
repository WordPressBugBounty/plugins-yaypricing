<?php
/**
 * Handle product pricing tooltip
 *
 * @package YayPricing\Classes\Tooltip
 *
 * @since 2.4
 */

namespace YAYDP\Core\Tooltip;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Tooltip extends \YAYDP\Abstracts\YAYDP_Tooltip {

	/**
	 * Get tooltip content
	 * Replace all variables
	 *
	 * @override
	 */
	public function get_content() {
		$raw_content = parent::get_raw_content();
		if ( empty( $this->rule ) || empty( $this->modifier ) ) {
			return $raw_content;
		}
		$replaced_content = $this->replace_discount_value( $raw_content );
		$replaced_content = $this->replace_per_unit_amount( $replaced_content );
		$replaced_content = $this->replace_discounted_price( $replaced_content );
		return $replaced_content;
	}

	/**
	 * The modified cart item, or null for tooltips built without one.
	 *
	 * @return \YAYDP\Core\YAYDP_Cart_Item|null
	 */
	private function get_cart_item() {
		$item = $this->modifier->get_item();
		return $item instanceof \YAYDP\Core\YAYDP_Cart_Item ? $item : null;
	}

	/**
	 * Replace [discount_value] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_discount_value( $raw_content ) {
		$rule                     = $this->rule;
		$item                     = $this->get_cart_item();
		$formatted_discount_value = '';
		if ( $this->modifier->is_modify_extra_item() ) {
			$formatted_discount_value = '100%';
		} elseif ( ! is_null( $item ) ) {
			$item_quantity = \yaydp_is_bulk_pricing( $rule ) ? $item->get_bulk_quantity() : $item->get_quantity();
			// Re-price a pristine copy of the item at the quantity the rule evaluates.
			// custom_price carries the item's initial price (add-ons, composite parts…)
			// so fixed-amount rules are valued against the price actually charged.
			$origin_item    = new \YAYDP\Core\YAYDP_Cart_Item(
				array(
					'quantity'     => $item_quantity,
					'data'         => $item->get_product(),
					'key'          => $item->get_key(),
					'custom_price' => $item->get_initial_price(),
				)
			);
			$pricing_type   = $rule->get_pricing_type( $item_quantity );
			$discount_value = $rule->get_discount_value_per_item( $origin_item );
			if ( ! \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_percentage_adjustment( $pricing_type ) ) {
				$discount_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $discount_value );
			}
			$formatted_discount_value = \yaydp_format_discount_value( $discount_value, $pricing_type );
		}
		return str_replace( '[discount_value]', $formatted_discount_value, $raw_content );
	}

	/**
	 * Replace [discount_amount] and [fee_amount] variables.
	 * Both are the per-unit adjustment magnitude; the two names only differ by rule intent.
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_per_unit_amount( $raw_content ) {
		// abs() so a markup-direction modifier (negative discount_per_unit, e.g.
		// highest_item_price raising a cheaper item) renders the magnitude, never "-$X".
		// Identity for normal discounts where discount_per_unit is positive.
		$amount_per_unit  = abs( $this->modifier->get_discount_per_unit() );
		$amount_per_unit  = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $amount_per_unit );
		$formatted_amount = \wc_price( $amount_per_unit );
		return str_replace( array( '[discount_amount]', '[fee_amount]' ), $formatted_amount, $raw_content );
	}

	/**
	 * Replace [discounted_price] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_discounted_price( $raw_content ) {
		$item = $this->modifier->get_item();
		if ( is_null( $item ) ) {
			return $raw_content;
		}
		// Start from the price the cart item was actually charged (custom_price aware),
		// falling back to the catalog price when the modifier only holds a product.
		if ( $item instanceof \YAYDP\Core\YAYDP_Cart_Item ) {
			$base_price = $item->get_initial_price();
		} else {
			$base_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $item );
		}
		$discounted_price = max( 0, $base_price - $this->modifier->get_discount_per_unit() );
		$discounted_price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $discounted_price );
		return str_replace( '[discounted_price]', \wc_price( $discounted_price ), $raw_content );
	}

}
