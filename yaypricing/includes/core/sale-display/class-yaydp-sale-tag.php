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
		$min_max_percent_discounts = $product_sale->get_min_max_discounts_percent();

		if ( is_null( $min_max_percent_discounts ) || ( empty( $min_max_percent_discounts['min'] ) && empty( $min_max_percent_discounts['max'] ) ) ) {
			return '';
		}

		$min_percent_discount = min( 100, max( 0, $min_max_percent_discounts['min'] ) );
		$max_percent_discount = min( 100, max( 0, $min_max_percent_discounts['max'] ) );

		if ( 0 == $min_percent_discount && 0 == $max_percent_discount ) {
			return '';
		}

		if ( is_null( $show_sale_off_amount ) ) {
			$show_sale_off_amount = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_sale_off_amount();
		}

		$has_image_gallery    = ! empty( $product->get_gallery_image_ids() );

		$product_for_rule_check = $product;
		$is_variable_product    = \yaydp_is_variable_product( $product );
		
		if ( \yaydp_is_variation_product( $product ) ) {
			$parent_id = $product->get_parent_id();
			if ( ! empty( $parent_id ) ) {
				$parent_product = \wc_get_product( $parent_id );
				if ( ! empty( $parent_product ) ) {
					$product_for_rule_check = $parent_product;
					$is_variable_product    = true;
				}
			}
		}

		$running_rules  = \yaydp_get_running_product_pricing_rules();
		$matching_rules = array();
		$variation_rules_data = array();

		if ( $is_variable_product && is_product() ) {
			$children_id = $product_for_rule_check->get_children();
			$all_rule_names = array();
			
			foreach ( $children_id as $variation_id ) {
				$variation = \wc_get_product( $variation_id );
				if ( empty( $variation ) ) {
					continue;
				}
				
				$variation_rule_names = array();
				foreach ( $running_rules as $rule ) {
					if ( \yaydp_is_buy_x_get_y( $rule ) ) {
						$filters    = $rule->get_receive_filters();
						$match_type = 'any';
					} else {
						$filters    = $rule->get_buy_filters();
						$match_type = $rule->get_match_type_of_buy_filters();
					}
					if ( $rule->can_apply_adjustment( $variation, $filters, $match_type ) ) {
						$rule_name = $rule->get_name();
						$variation_rule_names[] = $rule_name;
						if ( ! in_array( $rule_name, $all_rule_names, true ) ) {
							$all_rule_names[] = $rule_name;
						}
					}
				}
				
				if ( ! empty( $variation_rule_names ) ) {
					$variation_rules_data[ $variation_id ] = $variation_rule_names;
				}
			}
			
			foreach ( $running_rules as $rule ) {
				$rule_name = $rule->get_name();
				if ( in_array( $rule_name, $all_rule_names, true ) ) {
					$matching_rules[] = $rule;
				}
			}
		} else {
			foreach ( $running_rules as $rule ) {
				if ( \yaydp_is_buy_x_get_y( $rule ) ) {
					$filters    = $rule->get_receive_filters();
					$match_type = 'any';
				} else {
					$filters    = $rule->get_buy_filters();
					$match_type = $rule->get_match_type_of_buy_filters();
				}
				if ( $rule->can_apply_adjustment( $product_for_rule_check, $filters, $match_type ) ) {
					$matching_rules[] = $rule;
				}
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
				'variation_rules_data' => $variation_rules_data,
				'is_variable_product'  => $is_variable_product,
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