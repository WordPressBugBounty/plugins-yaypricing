<?php
/**
 * Handle pricing table functionality
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\NoName;

/**
 * YAYDP_Ajax class
 */
class YAYDP_Pricing_Table {

	/**
	 * Contains current product that belong to table
	 *
	 * @var \WC_Product
	 */
	protected $product;

	/**
	 * Contains rule that belong to table
	 *
	 * @var \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Pricing
	 */
	protected $rule;

	/**
	 * Contains settings
	 *
	 * @var \YAYDP\Settings\YAYDP_Product_Pricing_Settings
	 */
	protected $settings;

	/**
	 * Constructor
	 *
	 * @param \WC_Product                                         $product initial product.
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Pricing $rule initial product.
	 */
	public function __construct( $product, $rule ) {
		if ( \yaydp_is_variable_product( $product ) || \yaydp_is_grouped_product( $product ) ) {
			$product = \wc_get_product( $product->get_children()[0] );
		}
		$this->product  = $product;
		$this->rule     = $rule;
		$this->settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
	}

	/**
	 * Returns table title
	 */
	public function get_table_title() {
		return $this->settings->get_pricing_table_title();
	}

	/**
	 * Returns pricing table columns
	 */
	public function get_pricing_table_columns_order() {
		return $this->settings->get_pricing_table_columns_order();
	}

	/**
	 * Returns quantity title
	 */
	public function get_quantity_title() {
		return $this->settings->get_pricing_table_quantity_title();
	}

	/**
	 * Returns discount title
	 */
	public function get_discount_title() {
		return $this->settings->get_pricing_table_discount_title();
	}

	/**
	 * Returns price title
	 */
	public function get_price_title() {
		return $this->settings->get_pricing_table_price_title();
	}

	/**
	 * Returns border color
	 */
	public function get_border_color() {
		return $this->settings->get_pricing_table_border_color();
	}
	/**
	 * Returns border style
	 */
	public function get_border_style() {
		return $this->settings->get_pricing_table_border_style();
	}

	/**
	 * Returns quantity text
	 *
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range $range Given range.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_quantity_text( $range ) {
		$min_quantity  = $range->get_min_quantity();
		$max_quantity  = $range->get_max_quantity();
		$from_text     = empty( $min_quantity ) ? 0 : $min_quantity;
		$to_text       = empty( $max_quantity ) ? __( 'Unlimited', 'yaypricing' ) : $max_quantity;
		$quantities = [ $from_text ];
		if ( apply_filters( 'yaydp_pricing_table_show_max_quantity', true ) ) {
			$quantities[] = $to_text;
		}
		$quantity_text = implode( '-', array_unique( $quantities ) );
		return apply_filters( 'yaydp_pricing_table_quantity_text', $quantity_text, $min_quantity, $max_quantity );
	}

	/**
	 * Returns discount text
	 *
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range $range Given range.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_discount_text( $range ) {
		$pricing_type   = $range->get_pricing_type();
		$origin_item    = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $this->product, $range->get_min_quantity() );
		$discount_value = $this->rule->get_discount_value_per_item( $origin_item );
		if ( ! \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
			$discount_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $discount_value );
		}
		return apply_filters( 'yaydp_pricing_table_discount_text', \yaydp_get_formatted_pricing_value( $discount_value, $pricing_type ), $this->product, $discount_value );
	}

	/**
	 * Returns formula for discounted price
	 *
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range $range Given range.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_discounted_price_formula( $range ) {
		$pricing_type  = $range->get_pricing_type();
		$pricing_value = $range->get_pricing_value();
		$maximum       = $range->get_maximum_adjustment_amount();
		return \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discounted_price_formula( $pricing_type, $pricing_value, $maximum );
	}

	/**
	 * Returns formula for discount value
	 *
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range $range Given range.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_discount_value_formula( $range ) {
		$pricing_type  = $range->get_pricing_type();
		$pricing_value = $range->get_pricing_value();
		$maximum       = $range->get_maximum_adjustment_amount();
		return \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discount_value_formula( $pricing_type, $pricing_value, $maximum );
	}

	/**
	 * Returns discounted price text
	 *
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range $range Given range.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_discounted_price_text( $range ) {
		$origin_item      = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $this->product, $range->get_min_quantity() );
		$discount_amount  = $this->rule->get_discount_amount_per_item( $origin_item );
		$discounted_price = max( 0, $origin_item->get_price() - $discount_amount );
		$discounted_price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $discounted_price );
		$discounted_price = \wc_get_price_to_display( $this->product, array( 'price' => $discounted_price ) );
		return apply_filters( 'yaydp_pricing_table_discounted_price_text', \wc_price( $discounted_price ), $this->product, $discount_amount );
	}
}
