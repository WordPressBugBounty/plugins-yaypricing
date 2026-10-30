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
		return $this->translate_title( $this->settings->get_pricing_table_title(), 'table_title' );
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
		return $this->translate_title( $this->settings->get_pricing_table_quantity_title(), 'quantity_title' );
	}

	/**
	 * Returns discount title
	 */
	public function get_discount_title() {
		return $this->translate_title( $this->settings->get_pricing_table_discount_title(), 'discount_title' );
	}

	/**
	 * Returns price title
	 */
	public function get_price_title() {
		return $this->translate_title( $this->settings->get_pricing_table_price_title(), 'price_title' );
	}

	/**
	 * Translates a table title through WPML, Polylang or the plugin text domain (Loco Translate).
	 *
	 * The default titles ship in the .pot, and WPML/Polylang never register them
	 * because there is no merchant-entered string to register. Translate them
	 * through the text domain instead.
	 *
	 * @param string $title Title from settings.
	 * @param string $key   Settings key, e.g. 'table_title'.
	 *
	 * @return string
	 */
	private function translate_title( $title, $key ) {
		$defaults = \YAYDP\Constants\YAYDP_Pricing_Table::get_default();
		if ( isset( $defaults[ $key ] ) && $defaults[ $key ] === $title ) {
			$translated_defaults = array(
				'table_title'    => __( 'Quantity discounts', 'yaypricing' ),
				'quantity_title' => __( 'Quantity', 'yaypricing' ),
				'discount_title' => __( 'Discount', 'yaypricing' ),
				'price_title'    => __( 'Price', 'yaypricing' ),
			);
			return $translated_defaults[ $key ];
		}
		return \YAYDP\Helper\YAYDP_Helper::translate_user_string( $title, 'yaypricing', "pricing_table_{$key}" );
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
		$origin_item    = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $this->product, $range->get_min_quantity(), $this->get_table_base_price() );
		$discount_value = $this->rule->get_discount_value_per_item( $origin_item );
		if ( ! \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_percentage_adjustment( $pricing_type ) ) {
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
		list( $discounted_price, $discount_amount ) = $this->compute_discounted_price( $range );
		return $this->format_discounted_price( $discounted_price, $discount_amount );
	}

	/**
	 * Formats a displayed discounted price through the "yaydp_pricing_table_discounted_price_text" filter
	 *
	 * @param float $discounted_price Displayed discounted price.
	 * @param float $discount_amount  Discount amount per item.
	 *
	 * @return string
	 */
	private function format_discounted_price( $discounted_price, $discount_amount ) {
		return apply_filters( 'yaydp_pricing_table_discounted_price_text', \wc_price( $discounted_price ), $this->product, $discount_amount );
	}

	/**
	 * Returns the displayed discounted price of a range and the discount amount behind it
	 *
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range $range Given range.
	 *
	 * @return array [ displayed price, discount amount per item ]
	 */
	private function compute_discounted_price( $range ) {
		$origin_item      = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $this->product, $range->get_min_quantity(), $this->get_table_base_price() );
		$discount_amount  = $this->rule->get_discount_amount_per_item( $origin_item );
		$discounted_price = max( 0, $origin_item->get_price() - $discount_amount );
		$discounted_price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $discounted_price );
		$discounted_price = \wc_get_price_to_display( $this->product, array( 'price' => $discounted_price ) );
		return array( (float) $discounted_price, $discount_amount );
	}

	/**
	 * Returns the short tier label used by the non-table layouts: "5 - 9", "5" or "50+"
	 *
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range $range Given range.
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_tier_label( $range ) {
		$min_quantity = $range->get_min_quantity();
		$max_quantity = $range->get_max_quantity();
		$min_quantity = empty( $min_quantity ) ? 0 : $min_quantity;
		if ( empty( $max_quantity ) ) {
			/* translators: %s: minimum quantity of the tier */
			$label = sprintf( __( '%s+', 'yaypricing' ), $min_quantity );
		} elseif ( (float) $min_quantity === (float) $max_quantity ) {
			$label = (string) $min_quantity;
		} else {
			$label = $min_quantity . ' - ' . $max_quantity;
		}
		return apply_filters( 'yaydp_pricing_table_tier_label', $label, $min_quantity, $max_quantity );
	}

	/**
	 * Returns one entry per range, holding everything the layout templates render
	 *
	 * @since 3.6.2
	 *
	 * @return array
	 */
	public function get_rows() {
		$base_price = $this->get_base_display_price();
		$rows       = array();
		foreach ( $this->rule->get_ranges() as $range ) {
			$range_instance           = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range( $range );
			list( $price, $discount ) = $this->compute_discounted_price( $range_instance );
			$rows[]                   = array(
				'range'            => $range_instance,
				'min'              => $range['from_quantity'],
				'max'              => $range['to_quantity'],
				'quantity_text'    => $this->get_quantity_text( $range_instance ),
				'tier_label'       => $this->get_tier_label( $range_instance ),
				'discount_text'    => $this->get_discount_text( $range_instance ),
				'discount_formula' => $this->get_discount_value_formula( $range_instance ),
				'price_text'       => $this->format_discounted_price( $price, $discount ),
				'price_formula'    => $this->get_discounted_price_formula( $range_instance ),
				'price'            => $price,
				'saving_percent'   => $base_price > 0 ? max( 0, round( ( $base_price - $price ) / $base_price * 100, 1 ) ) : 0,
			);
		}
		return $rows;
	}

	/**
	 * Returns the html of one table cell for the given column
	 *
	 * @param array  $row    Entry of get_rows().
	 * @param string $column Column key from the columns order.
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_table_cell( $row, $column ) {
		$style = 'border-color: ' . esc_attr( $this->get_border_color() ) . ';';
		switch ( $column ) {
			case 'quantity_title':
				return '<td style="' . $style . '">' . esc_html( $row['quantity_text'] ) . '</td>';
			case 'discount_title':
				return '<td style="' . $style . '" data-variable="discount_value" data-formula="' . esc_attr( $row['discount_formula'] ) . '">' . wp_kses_post( $row['discount_text'] ) . '</td>';
			case 'price_title':
				return '<td style="' . $style . '" data-variable="discounted_price" data-formula="' . esc_attr( $row['price_formula'] ) . '">' . wp_kses_post( $row['price_text'] ) . '</td>';
		}
		return '';
	}

	/**
	 * Returns the title of the given column
	 *
	 * @param string $column Column key from the columns order.
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_column_title( $column ) {
		switch ( $column ) {
			case 'quantity_title':
				return $this->get_quantity_title();
			case 'discount_title':
				return $this->get_discount_title();
			case 'price_title':
				return $this->get_price_title();
		}
		return '';
	}

	/**
	 * Returns the layout of the table, filterable per product and rule
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_layout() {
		$layout = apply_filters( 'yaydp_pricing_table_layout', $this->settings->get_pricing_table_layout(), $this->product, $this->rule );
		return in_array( $layout, \YAYDP\Constants\YAYDP_Pricing_Table::get_layouts(), true ) ? $layout : 'table';
	}

	/**
	 * Returns the template file of a layout; "table" when the layout template is missing
	 *
	 * @param string $layout Layout key.
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public static function get_template_name( $layout ) {
		if ( 'table' === $layout ) {
			return 'yaydp-pricing-table.php';
		}
		$template_name = "pricing-table/yaydp-pricing-{$layout}.php";
		$located       = \wc_locate_template( $template_name, '', YAYDP_PLUGIN_PATH . 'includes/templates/' );
		return file_exists( $located ) ? $template_name : 'yaydp-pricing-table.php';
	}

	/**
	 * Renders the pricing table of a rule for a product, in the configured layout
	 *
	 * @param \WC_Product                                         $product Product.
	 * @param \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Pricing $rule    Bulk or tiered rule.
	 *
	 * @since 3.6.2
	 */
	public static function render( $product, $rule ) {
		$pricing_table = new self( $product, $rule );
		\wc_get_template(
			self::get_template_name( $pricing_table->get_layout() ),
			array(
				'rule'          => $rule,
				'product'       => $product,
				'pricing_table' => $pricing_table,
			),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
	}

	/**
	 * Returns the escaped attributes of a layout wrapper, keeping the data contract of variation-selection.js
	 *
	 * @param \WC_Product $product Product given to the template.
	 * @param string      $layout  Layout key.
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_wrapper_attributes( $product, $layout ) {
		return sprintf(
			'class="yaydp-pricing-table-wrapper yaydp-layout-%1$s" data-applicable-variations="%2$s" data-base-price="%3$s" style="--yaydp-pt-border: %4$s;"',
			esc_attr( $layout ),
			esc_attr( implode( ',', $this->get_applicable_variations( $product ) ) ),
			esc_attr( $this->get_base_display_price() ),
			esc_attr( $this->get_border_color() )
		);
	}

	/**
	 * Returns the escaped data attributes of one tier for pricing-table.js
	 *
	 * @param array $row Entry of get_rows().
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_tier_attributes( $row ) {
		return sprintf(
			'data-min-value="%1$s" data-max-value="%2$s" data-price="%3$s" data-price-formula="%4$s" data-label="%5$s"',
			esc_attr( $row['min'] ),
			esc_attr( $row['max'] ),
			esc_attr( $row['price'] ),
			esc_attr( $row['price_formula'] ),
			esc_attr( $row['tier_label'] )
		);
	}

	/**
	 * Returns the "Save X%" html of a tier; hidden when the tier saves nothing
	 *
	 * @param array $row Entry of get_rows().
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_saving_html( $row ) {
		$percent = '<span class="yaydp-pricing-tier__saving-value">' . esc_html( $row['saving_percent'] . '%' ) . '</span>';
		/* translators: %s: saving percent, e.g. 10% */
		$text = sprintf( esc_html__( 'Save %s', 'yaypricing' ), $percent );
		$text = apply_filters( 'yaydp_pricing_table_saving_text', $text, $row['saving_percent'], $this->product );
		return '<span class="yaydp-pricing-tier__saving"' . ( $row['saving_percent'] > 0 ? '' : ' hidden' ) . '>' . wp_kses_post( $text ) . '</span>';
	}

	/**
	 * Returns the struck-through regular price html, recomputed per variation through the "base_price" formula
	 *
	 * @param array $row Entry of get_rows().
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_regular_price_html( $row ) {
		return '<del class="yaydp-pricing-tier__regular" data-variable="base_price" data-formula="x"' . ( $row['saving_percent'] > 0 ? '' : ' hidden' ) . '>' . wp_kses_post( \wc_price( $this->get_base_display_price() ) ) . '</del>';
	}

	/**
	 * Returns the discounted price html of a tier, recomputed per variation
	 *
	 * @param array $row Entry of get_rows().
	 *
	 * @since 3.6.2
	 *
	 * @return string
	 */
	public function get_price_html( $row ) {
		return '<span class="yaydp-pricing-tier__price" data-variable="discounted_price" data-formula="' . esc_attr( $row['price_formula'] ) . '">' . wp_kses_post( $row['price_text'] ) . '</span>';
	}

	/**
	 * Returns the variation ids of a variable product the rule applies to, for the wrapper data attribute
	 *
	 * @param \WC_Product $product Product given to the template.
	 *
	 * @since 3.6.2
	 *
	 * @return array
	 */
	public function get_applicable_variations( $product ) {
		$applicable_variations = array();
		if ( \yaydp_is_variable_product( $product ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				$variation = \wc_get_product( $variation_id );
				if ( $this->rule->can_apply_adjustment( $variation ) ) {
					$applicable_variations[] = $variation_id;
				}
			}
		}
		return $applicable_variations;
	}

	/**
	 * Returns the base price as displayed ( converted, tax display applied ), i.e. the `x` of the JS formulas.
	 *
	 * @return float
	 */
	public function get_base_display_price() {
		$base_price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $this->get_table_base_price() );
		return \wc_get_price_to_display( $this->product, array( 'price' => $base_price ) );
	}

	/**
	 * Returns the base price used for Pricing Table discount math.
	 *
	 * Exposes the `yaydp_pricing_table_base_price` filter so integrations
	 * (e.g. yay-wholesale-b2b-pro) can swap retail for wholesale base while
	 * keeping the value in store base currency — currency conversion still
	 * runs later via YAYDP_Pricing_Helper::convert_price().
	 *
	 * @since 3.x
	 *
	 * @return float
	 */
	private function get_table_base_price() {
		$base = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $this->product );
		return apply_filters( 'yaydp_pricing_table_base_price', $base, $this->product );
	}
}
