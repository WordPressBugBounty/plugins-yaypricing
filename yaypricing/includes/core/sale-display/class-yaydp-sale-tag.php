<?php
/**
 * Represents a YayPricing sale tag
 *
 * @since 2.4
 *
 * @package YayPricing\SaleDisplay
 */

namespace YAYDP\Core\Sale_Display;

/**
 * Declare class
 */
class YAYDP_Sale_Tag {

	/**
	 * Contains product that sale tag belong to
	 */
	protected $product = null;

	/**
	 * Constructor
	 *
	 * @param \WC_Product $product Product.
	 */
	public function __construct( $product ) {
		$this->product = $product;
	}

	/**
	 * Check whether sale tag is enable
	 */
	public function is_enabled() {
		return \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_sale_tag();
	}

	/**
	 * Get content of the sale tag that displays on product
	 */
	public function get_content( $show_sale_off_amount = null, $is_custom = false ) {
		$product                   = $this->product;
		$product_sale              = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_percent_discounts = $product_sale->get_min_max_discounts_percent(); // Get min max discount percentage.

		// Note: product is on sale when there is at least min or max percentage not empty.
		if ( is_null( $min_max_percent_discounts ) || ( empty( $min_max_percent_discounts['min'] ) && empty( $min_max_percent_discounts['max'] ) ) ) {
			return '';
		}

		$min_percent_discount = $min_max_percent_discounts['min'];
		$max_percent_discount = $min_max_percent_discounts['max'];

		if ( is_null( $show_sale_off_amount ) ) {
			$show_sale_off_amount = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_sale_off_amount();
		}

		$has_image_gallery = ! empty( $product->get_gallery_image_ids() );

		$running_rules = \yaydp_get_running_product_pricing_rules();

		$matching_rules = array();

		foreach ( $running_rules as $rule ) {
			if ( \yaydp_is_buy_x_get_y( $rule ) ) {
				$filters    = $rule->get_receive_filters();
				$match_type = 'any';
			} else {
				$filters    = $rule->get_buy_filters();
				$match_type = $rule->get_match_type_of_buy_filters();
			}
			if ( $rule->can_apply_adjustment( $product, $filters, $match_type ) ) {
				$matching_rules[] = $rule;
			}
		}

		ob_start();
		\wc_get_template(
			'product/yaydp-sale-tag.php',
			array(
				'min_percent_discount' => $min_percent_discount,
				'max_percent_discount' => $max_percent_discount,
				'show_sale_off_amount' => $show_sale_off_amount,
				'has_image_gallery'    => $has_image_gallery,
				'product'              => $product,
				'matching_rules'       => $matching_rules,
				'is_custom'            => $is_custom,
			),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
		$html = ob_get_contents();
		ob_end_clean();

		return apply_filters( 'yaydp_sale_tag', $html, $product, $min_percent_discount, $max_percent_discount );
	}

	/**
	 * Check whethere sale tag can display
	 */
	public function can_display() {
		return $this->is_enabled();
	}


}
