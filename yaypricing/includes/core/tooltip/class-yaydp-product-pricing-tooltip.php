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
		$raw_content      = parent::get_raw_content();
		$replaced_content = $this->replace_discount_value( $raw_content );
		$replaced_content = $this->replace_discount_amount( $replaced_content );
		$replaced_content = $this->replace_discounted_price( $replaced_content );
		return $replaced_content;
	}

	/**
	 * Replace [discount_value] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_discount_value( $raw_content ) {
		$rule                     = $this->modifier->get_rule();
		$item                     = $this->modifier->get_item();
		$discount_value           = 0;
		$formatted_discount_value = '';
		if ( $this->modifier->is_modify_extra_item() ) {
			$formatted_discount_value = '100%';
		} else {
			if ( ! is_null( $item ) ) {
				$item_quantity    = \yaydp_is_bulk_pricing( $rule ) ? $item->get_bulk_quantity() : $item->get_quantity();
				$origin_item_data = array(
					'quantity' => $item_quantity,
					'data'     => $item->get_product(),
					'key'      => $item->get_key(),
				);
				$origin_item      = new \YAYDP\Core\YAYDP_Cart_Item( $origin_item_data );
				$pricing_type     = $rule->get_pricing_type( $item_quantity );
				$discount_value   = $rule->get_discount_value_per_item( $origin_item );
				if ( ! \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
					$discount_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $discount_value );
				}
				$formatted_discount_value = \yaydp_format_discount_value( $discount_value, $pricing_type );
			}
		}
		$replaced_content = str_replace( '[discount_value]', $formatted_discount_value, $raw_content );

		return $replaced_content;

	}

	/**
	 * Replace [discount_amount] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_discount_amount( $raw_content ) {
		$discount_amount_per_unit  = $this->modifier->get_discount_per_unit();
		$discount_amount_per_unit  = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $discount_amount_per_unit );
		$formatted_discount_amount = \wc_price( $discount_amount_per_unit );
		$replaced_content          = str_replace( '[discount_amount]', $formatted_discount_amount, $raw_content );
		return $replaced_content;
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
		if ( $item instanceof \YAYDP\Core\YAYDP_Cart_Item ) {
			$product = $item->get_product();
		} else {
			$product = $item;
		}
		$discount_amount_per_unit   = $this->modifier->get_discount_per_unit();
		$product_price              = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		$discounted_price           = max( 0, $product_price - $discount_amount_per_unit );
		$discounted_price           = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $discounted_price );
		$formatted_discounted_price = \wc_price( $discounted_price );
		$replaced_content           = str_replace( '[discounted_price]', $formatted_discounted_price, $raw_content );

		return $replaced_content;
	}

}
