<?php
/**
 * Handling Offer description
 *
 * @package YayPricing\OfferDescription
 */

namespace YAYDP\Core\Offer_Description;

/**
 * Declare class
 */
class YAYDP_Offer_Description {

	/**
	 * Contains offer description data
	 */
	protected $data = null;

	/**
	 * Contains current rule
	 */
	protected $rule = null;

	/**
	 * Contains type of content
	 *
	 * @var string
	 */
	protected $content_type = 'buy_content';

	/**
	 * Contains product that offer description will display in
	 */
	protected $product = null;

	/**
	 * Constructor
	 *
	 * @param array $input Given data.
	 */
	public function __construct( $input ) {
		if ( isset( $input['data'] ) ) {
			$this->data = $input['data'];
		}
		if ( isset( $input['rule'] ) ) {
			$this->rule = $input['rule'];
		}
		if ( isset( $input['content_type'] ) ) {
			$this->content_type = $input['content_type'];
		}
		if ( isset( $input['product'] ) ) {
			$this->product = $input['product'];
		}
	}

	/**
	 * Check whether offer description can display
	 */
	public function can_display() {
		if ( ! $this->is_enabled() ) {
			return false;
		}
		if ( ! $this->show_when_match_conditions() ) {
			return true;
		}

		$rule = $this->rule;
		if ( empty( $rule ) ) {
			return false;
		}

		$cart = new \YAYDP\Core\YAYDP_Cart();

		return $rule->check_conditions( $cart );
	}

	/**
	 * Check whether offer description is enabled
	 */
	public function is_enabled() {
		return ! empty( $this->data['enable'] ) ? $this->data['enable'] : false;
	}

	/**
	 * Check whether offer description show when match conditions
	 */
	public function show_when_match_conditions() {
		return empty( $this->data['show_when_match_conditions'] ) ? false : $this->data['show_when_match_conditions'];
	}

	/**
	 * Retrives raw content ( before replace variables )
	 */
	public function get_raw_content() {
		if ( 'buy_content' !== $this->content_type ) {
			return empty( $this->data['get_product_description'] ) ? '' : $this->data['get_product_description'];
		}
		return empty( $this->data['buy_product_description'] ) ? '' : $this->data['buy_product_description'];
	}

	/**
	 * Retrives content after replaced variables
	 */
	public function get_content() {
		$raw_content      = $this->get_raw_content();
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
		$rule    = $this->rule;
		$product = $this->product;
		if ( empty( $rule ) || empty( $product ) ) {
			return $raw_content;
		}
		$formatted_discount_value = $this->get_formatted_discount_value();
		$replaced_content         = str_replace( '[discount_value]', $formatted_discount_value, $raw_content );

		return $replaced_content;

	}

	/**
	 * Replace [discount_amount] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_discount_amount( $raw_content ) {
		$rule    = $this->rule;
		$product = $this->product;
		if ( empty( $rule ) || empty( $product ) ) {
			return $raw_content;
		}
		$formatted_discount_amount = $this->get_formatted_discount_amount();
		$replaced_content          = str_replace( '[discount_amount]', $formatted_discount_amount, $raw_content );
		return $replaced_content;

	}

	/**
	 * Replace [discounted_price] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	private function replace_discounted_price( $raw_content ) {
		$rule    = $this->rule;
		$product = $this->product;
		if ( empty( $rule ) || empty( $product ) ) {
			return $raw_content;
		}
		$formatted_discounted_price = $this->get_formatted_discounted_price();
		$replaced_content           = str_replace( '[discounted_price]', $formatted_discounted_price, $raw_content );
		return $replaced_content;

	}

	/**
	 * Discount value
	 */
	private function get_formatted_discount_value_for_free_item() {
		$discount_value = '100';
		return \yaydp_format_discount_value( $discount_value, 'percentage_discount' );
	}
	/**
	 * Discount value
	 */
	private function get_formatted_discount_value_for_bulk_pricing() {
		// TODO: fix for variable.
		$rule    = $this->rule;
		$product = $this->product;
		if ( empty( $rule ) || empty( $product ) ) {
			return '';
		}
		$discount_values = array();
		foreach ( $rule->get_ranges() as $range ) {
			$range_instance    = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range( $range );
			$min_quantity      = $range_instance->get_min_quantity();
			$max_quantity      = $range_instance->get_max_quantity();
			$origin_item       = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, $min_quantity );
			$discount_value    = $rule->get_discount_value_per_item( $origin_item );
			$pricing_type      = $rule->get_pricing_type( $min_quantity );
			$pricing_value     = $rule->get_pricing_value( $min_quantity );
			$maximum_value     = $rule->get_maximum_adjustment_amount( $min_quantity );
			$discount_values[] = array(
				'min_quantity' => $min_quantity,
				'max_quantity' => empty( $max_quantity ) ? __( 'unlimited', 'yaypricing' ) : $max_quantity,
				'value'        => $discount_value,
				'pricing_type' => $pricing_type,
				'formula'      => \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discount_value_formula( $pricing_type, $pricing_value, $maximum_value ),
			);
		}
		$formatted_discount_values = array_map(
			function( $item ) {
				$formatted_discount_value = \yaydp_get_formatted_pricing_value( $item['value'], $item['pricing_type'] );
				return sprintf( __( 'from %1$s to %2$s: <span data-variable="discount_value" data-formula="%3$s">%4$s</span>', 'yaypricing' ), $item['min_quantity'], $item['max_quantity'], $item['formula'], $formatted_discount_value );
			},
			$discount_values
		);
		return implode( ' | ', $formatted_discount_values );
	}
	/**
	 * Discount value
	 */
	private function get_formatted_discount_value_for_other_cases() {
		$product = $this->product;
		$rule    = $this->rule;
		if ( empty( $product ) || empty( $rule ) ) {
			return '';
		}
		$origin_item              = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, 1 );
		$discount_value           = $rule->get_discount_value_per_item( $origin_item );
		$pricing_type             = $rule->get_pricing_type();
		$pricing_value            = $rule->get_pricing_value();
		$maximum_value            = $rule->get_maximum_adjustment_amount();
		$formatted_discount_value = \yaydp_format_discount_value( $discount_value, $rule->get_pricing_type() );
		$formula                  = \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discount_value_formula( $pricing_type, $pricing_value, $maximum_value );
		return sprintf( __( '<span data-variable="discount_value" data-formula="%1$s">%2$s</span>', 'yaypricing' ), $formula, $formatted_discount_value );
	}
	/**
	 * Discount value
	 */
	private function get_formatted_discount_value() {
		$rule = $this->rule;
		if ( empty( $rule ) ) {
			return '';
		}
		$is_bogo_or_buy_x_get_y = \yaydp_is_buy_x_get_y( $rule ) || \yaydp_is_bogo( $rule );
		if ( $is_bogo_or_buy_x_get_y && $rule->is_get_free_item() ) {
			return $this->get_formatted_discount_value_for_free_item();
		} elseif ( \yaydp_is_bulk_pricing( $rule ) ) {
			return $this->get_formatted_discount_value_for_bulk_pricing();
		} else {
			return $this->get_formatted_discount_value_for_other_cases();
		}
	}

	/**
	 * Discount_amount
	 */
	private function get_formatted_discount_amount_for_free_item() {
		$product = $this->product;
		if ( empty( $product ) ) {
			return '';
		}
		$discount_value            = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		$formula                   = \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discount_amount_formula( 'percentage_discount', 100, PHP_INT_MAX );
		$formatted_discount_amount = \wc_price( $discount_value );
		return sprintf( __( '<span data-variable="discount_amount" data-formula="%1$s">%2$s</span>', 'yaypricing' ), $formula, $formatted_discount_amount );
	}
	/**
	 * Discount_amount
	 */
	private function get_formatted_discount_amount_for_bulk_pricing() {
		$rule    = $this->rule;
		$product = $this->product;
		if ( empty( $rule ) || empty( $product ) ) {
			return '';
		}
		$discount_values = array();
		foreach ( $rule->get_ranges() as $range ) {
			$range_instance    = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range( $range );
			$min_quantity      = $range_instance->get_min_quantity();
			$max_quantity      = $range_instance->get_max_quantity();
			$origin_item       = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, $min_quantity );
			$discount_amount   = $rule->get_discount_amount_per_item( $origin_item );
			$pricing_type      = $rule->get_pricing_type( $min_quantity );
			$pricing_value     = $rule->get_pricing_value( $min_quantity );
			$maximum_value     = $rule->get_maximum_adjustment_amount( $min_quantity );
			$discount_values[] = array(
				'min_quantity' => $min_quantity,
				'max_quantity' => empty( $max_quantity ) ? __( 'unlimited', 'yaypricing' ) : $max_quantity,
				'value'        => $discount_amount,
				'formula'      => \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discount_amount_formula( $pricing_type, $pricing_value, $maximum_value ),
			);
		}
		$formatted_discount_values = array_map(
			function( $item ) {
				$formatted_discount_amount = \wc_price( $item['value'] );
				return sprintf( __( 'from %1$s to %2$s: <span data-variable="discount_amount" data-formula="%3$s">%4$s</span>', 'yaypricing' ), $item['min_quantity'], $item['max_quantity'], $item['formula'], $formatted_discount_amount );
			},
			$discount_values
		);
		return implode( ' | ', $formatted_discount_values );
	}
	/**
	 * Discount_amount
	 */
	private function get_formatted_discount_amount_for_other_cases() {
		$rule    = $this->rule;
		$product = $this->product;
		if ( empty( $rule ) || empty( $product ) ) {
			return '';
		}
		$origin_item               = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, 1 );
		$discount_amount           = $rule->get_discount_amount_per_item( $origin_item );
		$pricing_type              = $rule->get_pricing_type();
		$pricing_value             = $rule->get_pricing_value();
		$maximum_value             = $rule->get_maximum_adjustment_amount();
		$formula                   = \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discount_amount_formula( $pricing_type, $pricing_value, $maximum_value );
		$formatted_discount_amount = \wc_price( $discount_amount );
		return sprintf( __( '<span data-variable="discount_amount" data-formula="%1$s">%2$s</span>', 'yaypricing' ), $formula, $formatted_discount_amount );
	}
	/**
	 * Discount_amount
	 */
	private function get_formatted_discount_amount() {
		$rule = $this->rule;
		if ( empty( $rule ) ) {
			return '';
		}
		$is_bogo_or_buy_x_get_y = \yaydp_is_buy_x_get_y( $rule ) || \yaydp_is_bogo( $rule );
		if ( $is_bogo_or_buy_x_get_y && $rule->is_get_free_item() ) {
			return $this->get_formatted_discount_amount_for_free_item();
		} elseif ( \yaydp_is_bulk_pricing( $rule ) ) {
			return $this->get_formatted_discount_amount_for_bulk_pricing();
		} else {
			return $this->get_formatted_discount_amount_for_other_cases();
		}
	}

	/**
	 * Discounted_price
	 */
	private function get_formatted_discounted_price_for_free_item() {
		return \wc_price( 0 );
	}
	/**
	 * Discounted_price
	 */
	private function get_formatted_discounted_price_for_bulk_pricing() {
		$rule    = $this->rule;
		$product = $this->product;
		if ( empty( $rule ) || empty( $product ) ) {
			return '';
		}
		$discounted_prices = array();
		foreach ( $rule->get_ranges() as $range ) {
			$range_instance      = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range( $range );
			$min_quantity        = $range_instance->get_min_quantity();
			$max_quantity        = $range_instance->get_max_quantity();
			$origin_item         = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, $min_quantity );
			$discount_amount     = $rule->get_discount_amount_per_item( $origin_item );
			$pricing_type        = $rule->get_pricing_type( $min_quantity );
			$pricing_value       = $rule->get_pricing_value( $min_quantity );
			$maximum_value       = $rule->get_maximum_adjustment_amount( $min_quantity );
			$discounted_prices[] = array(
				'min_quantity' => $min_quantity,
				'max_quantity' => empty( $max_quantity ) ? __( 'unlimited', 'yaypricing' ) : $max_quantity,
				'value'        => max( 0, $origin_item->get_price() - $discount_amount ),
				'formula'      => \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discounted_price_formula( $pricing_type, $pricing_value, $maximum_value ),
			);
		}
		$formatted_discounted_prices = array_map(
			function( $item ) {
				$formatted_discounted_price = \wc_price( $item['value'] );
				return sprintf( __( 'from %1$s to %2$s: <span data-variable="discounted_price" data-formula="%3$s">%4$s</span>', 'yaypricing' ), $item['min_quantity'], $item['max_quantity'], $item['formula'], $formatted_discounted_price );
			},
			$discounted_prices
		);
		return implode( ' | ', $formatted_discounted_prices );
	}
	/**
	 * Discounted_price
	 */
	private function get_formatted_discounted_price_for_other_cases() {
		$rule    = $this->rule;
		$product = $this->product;
		if ( empty( $rule ) || empty( $product ) ) {
			return '';
		}
		$origin_item                = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, 1 );
		$discount_amount            = $rule->get_discount_amount_per_item( $origin_item );
		$discounted_price           = max( 0, $origin_item->get_price() - $discount_amount );
		$pricing_type               = $rule->get_pricing_type();
		$pricing_value              = $rule->get_pricing_value();
		$maximum_value              = $rule->get_maximum_adjustment_amount();
		$formula                    = \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_discounted_price_formula( $pricing_type, $pricing_value, $maximum_value );
		$formatted_discounted_price = \wc_price( $discounted_price );
		return sprintf( __( '<span data-variable="discounted_price" data-formula="%1$s">%2$s</span>', 'yaypricing' ), $formula, $formatted_discounted_price );
	}
	/**
	 * Discounted_price
	 */
	private function get_formatted_discounted_price() {
		$rule = $this->rule;
		if ( empty( $rule ) ) {
			return '';
		}
		$is_bogo_or_buy_x_get_y = \yaydp_is_buy_x_get_y( $rule ) || \yaydp_is_bogo( $rule );
		if ( $is_bogo_or_buy_x_get_y && $rule->is_get_free_item() ) {
			return $this->get_formatted_discounted_price_for_free_item();
		} elseif ( \yaydp_is_bulk_pricing( $rule ) ) {
			return $this->get_formatted_discounted_price_for_bulk_pricing();
		} else {
			return $this->get_formatted_discounted_price_for_other_cases();
		}
	}

}
