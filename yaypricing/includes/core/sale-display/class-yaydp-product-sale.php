<?php
/**
 * Managing product sale information
 *
 * @since 2.4
 *
 * @package YayPricing\SaleDisplay
 */

namespace YAYDP\Core\Sale_Display;

/**
 * Declare class
 */
class YAYDP_Product_Sale {

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
	 * Calculate minimum and maximum discount percentages of current product
	 */
	public function get_min_max_discounts_percent() {
		global $yaydp_products_discount_percents;

		$product_id = $this->product->get_id();

		if ( isset( $yaydp_products_discount_percents[ $product_id ] ) ) {
			return $yaydp_products_discount_percents[ $product_id ];
		}

		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		if ( ! $settings->show_product_sale_as_discountable_price_range() ) {
			$result = $this->get_absolute_min_max_percent();
		} else {
			$result = $this->get_relative_min_max_percent();
		}
		if ( isset( $result['min'] ) ) {
			$result['min'] = max( 0, $result['min'] );
		}
		if ( isset( $result['max'] ) ) {
			$result['max'] = min( 100, $result['max'] );
		}

		$yaydp_products_discount_percents[ $product_id ] = $result;

		return $result;
	}

	/**
	 * Calculate the relative minimum and maximum discount percentages
	 */
	private function get_relative_min_max_percent() {
		$product = $this->product;

		if ( \yaydp_is_variable_product( $product ) ) {
			$result      = array(
				'min' => null,
				'max' => null,
			);
			$children_id = $product->get_children();
			$children    = array_map(
				function( $id ) {
					return \wc_get_product( $id );
				},
				$children_id
			);
			foreach ( $children as $child ) {
				$sub = $this->get_relative_min_max_percent_per_product( $child );
				if ( is_null( $sub ) ) {
					$result['min'] = 0;
				} else {
					$result['max'] = max( $sub['max'], $result['max'] );
					$result['min'] = is_null( $result['min'] ) ? $sub['min'] : min( $result['min'], $sub['min'] );
				}
			}
			if ( is_null( $result['max'] ) ) {
				$result['max'] = 0;
			}
			return $result;
		} else {
			return $this->get_relative_min_max_percent_per_product( $product );
		}
	}

	/**
	 * Calculate the absolute minimum and maximum discount percentages
	 */
	private function get_absolute_min_max_percent() {
		$product = $this->product;
		if ( \yaydp_is_variable_product( $product ) ) {
			$result      = array(
				'min' => null,
				'max' => null,
			);
			$children_id = $product->get_children();
			$children    = array_map(
				function( $id ) {
					return \wc_get_product( $id );
				},
				$children_id
			);
			foreach ( $children as $child ) {
				$sub = $this->get_absolute_min_max_percent_per_product( $child );
				if ( is_null( $sub ) ) {
					$result['min'] = 0;
				} else {
					$result['max'] = max( $sub['max'], $result['max'] );
					$result['min'] = is_null( $result['min'] ) ? $sub['min'] : min( $result['min'], $sub['min'] );
				}
			}
			if ( is_null( $result['max'] ) ) {
				$result['max'] = 0;
			}
			return $result;
		} else {
			return $this->get_absolute_min_max_percent_per_product( $product );
		}
	}

	/**
	 * Calculate the relative minimum and maximum discount percentages of given product
	 *
	 * @param \WC_Product $product Product.
	 */
	private function get_relative_min_max_percent_per_product( $product ) {
		$running_rules = \yaydp_get_running_product_pricing_rules();
		foreach ( $running_rules as $rule ) {
			if ( \yaydp_is_buy_x_get_y( $rule ) ) {
				$filters    = $rule->get_receive_filters();
				$match_type = 'any';
			} else {
				$filters    = $rule->get_buy_filters();
				$match_type = $rule->get_match_type_of_buy_filters();
			}
			if ( ! $rule->can_apply_adjustment( $product, $filters, $match_type ) ) {
				continue;
			}
			$min_discounts[] = $rule->get_min_discount( $product );
			$max_discounts[] = $rule->get_max_discount( $product );
		}
		if ( empty( $min_discounts ) && empty( $max_discounts ) ) {
			return null;
		}

		$product_price        = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		$min_percent_discount = $this->get_total_sale_off_percent( $product_price, $min_discounts );
		$max_percent_discount = $this->get_total_sale_off_percent( $product_price, $max_discounts );
		if ( ! \yaydp_product_pricing_is_applied_all_rules() && ! empty( $max_percent_discount ) ) {
			$min_percent_discount = 0;
		}
		return array(
			'min' => min( 100, max( 0, $min_percent_discount ) ),
			'max' => min( 100, max( 0, $max_percent_discount ) ),
		);
	}

	/**
	 * Calculate the absolute minimum and maximum discount percentages of given product
	 *
	 * @param \WC_Product $product Product.
	 */
	private function get_absolute_min_max_percent_per_product( $product ) {
		$cart = new \YAYDP\Core\YAYDP_Cart();
		$cart->reset_modifiers();
		$running_rules = \yaydp_get_running_product_pricing_rules();
		$is_on_sale    = false;
		foreach ( $running_rules as $rule ) {
			if ( \yaydp_is_buy_x_get_y( $rule ) ) {
				$filters    = $rule->get_receive_filters();
				$match_type = 'any';
			} else {
				$filters    = $rule->get_buy_filters();
				$match_type = $rule->get_match_type_of_buy_filters();
			}
			if ( $rule->can_apply_adjustment( $product, $filters, $match_type ) ) {
				$is_on_sale = true;
				break;
			}
		}

		if ( ! $is_on_sale ) {
			return null;
		}
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		if ( $settings->show_product_sale_as_next_discount_tier() ) {
			$cart->add_item( $product, 1 );
		}
		$product_pricing_adjustments = new \YAYDP\Core\Adjustments\YAYDP_Product_Pricing_Adjustments( $cart );
		$product_pricing_adjustments->do_stuff();
		foreach ( $cart->get_items() as $item ) {
			if ( $item->is_extra() ) {
				continue;
			}
			$item_product = $item->get_product();
			if ( $product->get_id() === $item_product->get_id() ) {
				$product_price     = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
				$item_price_ranges = $item->get_prices_based_on_quantity();
				krsort($item_price_ranges);
				$item_price        = array_key_last( $item_price_ranges );
				if ( empty( $product_price ) || empty( $item_price ) ) {
					continue;
				}
				$discount_percent = 100 - ( round( $item_price, 3 ) / round( $product_price, 3 ) * 100 );
				return array(
					'min' => min( 100, max( 0, $discount_percent ) ),
					'max' => min( 100, max( 0, $discount_percent ) ),
				);
			}
		}
		return null;
	}

	/**
	 * Calculate the final discount percentage of product by list discounts
	 *
	 * @param float $product_price Product price.
	 * @param array $discounts List discount information.
	 */
	private function get_total_sale_off_percent( $product_price, $discounts ) {
		if ( empty( $product_price ) ) {
			return 0;
		}
		$total_percent_discount         = 0;
		$after_discounted_product_price = $product_price;
		foreach ( $discounts as $discount ) {
			$pricing_value = $discount['pricing_value'];
			$pricing_type  = $discount['pricing_type'];
			$maximum       = $discount['maximum'];
			if ( \yaydp_is_percentage_pricing_type( $pricing_type ) ) {
				$discount_amount                = min( $maximum, $after_discounted_product_price * $pricing_value / 100 );
				$after_discounted_product_price = max( 0, $after_discounted_product_price - $discount_amount );
			}
			if ( \yaydp_is_fixed_pricing_type( $pricing_type ) ) {
				$discount_amount                = min( $maximum, $pricing_value );
				$after_discounted_product_price = max( 0, $after_discounted_product_price - $discount_amount );
			}
			if ( \yaydp_is_flat_pricing_type( $pricing_type ) ) {
				$after_discounted_product_price = min( $after_discounted_product_price, $pricing_value );
			}
		}
		$total_percent_discount = ( $product_price - $after_discounted_product_price ) / $product_price * 100;
		return $total_percent_discount;
	}

	/**
	 * Calculate minimum and maximum discounted price of current product
	 */
	public function get_min_max_discounted_price() {
		$settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		if ( ! $settings->show_product_sale_as_discountable_price_range() ) {
			return $this->get_absolute_min_max_discounted_price();
		} else {
			return $this->get_relative_min_max_discounted_price();
		}
	}

	/**
	 * Calculate relative minimum and maximum discounted price of current product
	 */
	private function get_relative_min_max_discounted_price() {
		$product = $this->product;

		if ( empty( $product ) ) {
			return null;
		}

		if ( \yaydp_is_variable_product( $product ) ) {
			$min_price      = \yaydp_get_variable_product_min_price( $product );
			$max_price      = \yaydp_get_variable_product_max_price( $product );
			$has_discounted = false;
			$result         = array(
				'min' => $min_price,
				'max' => $max_price,
			);
			$children_id    = $product->get_children();
			$children       = array_map(
				function( $id ) {
					return \wc_get_product( $id );
				},
				$children_id
			);
			foreach ( $children as $child ) {
				if ( empty( $child ) ) {
					continue;
				}
				$sub = $this->get_relative_min_max_discounted_price_per_product( $child );
				if ( is_null( $sub ) ) {
					continue;
				}
				$has_discounted = true;
				$result['min']  = min( $sub['min'], $result['min'] );
				$result['max']  = max( $sub['max'], $result['max'] );
			}
			if ( $has_discounted ) {
				return $result;
			}
			return null;
		} else {
			return $this->get_relative_min_max_discounted_price_per_product( $product );
		}
	}

	/**
	 * Calculate relative minimum and maximum discounted price of current product
	 *
	 * @param \WC_Product $product Produce.
	 */
	private function get_relative_min_max_discounted_price_per_product( $product ) {
		$product_price   = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		$min_max_percent = $this->get_relative_min_max_percent_per_product( $product );
		if ( is_null( $min_max_percent ) || ( empty( $min_max_percent['min'] ) && empty( $min_max_percent['max'] ) ) ) {
			return null;
		} else {
			return array(
				'min' => $product_price - ( $product_price * $min_max_percent['max'] / 100 ),
				'max' => $product_price - ( $product_price * $min_max_percent['min'] / 100 ),
			);
		}
	}

	/**
	 * Calculate absolute minimum and maximum discounted price of current product
	 */
	private function get_absolute_min_max_discounted_price() {
		$product = $this->product;

		if ( empty( $product ) ) {
			return null;
		}

		if ( \yaydp_is_variable_product( $product ) ) {
			$result         = array(
				'min' => null,
				'max' => null,
			);
			$children_id    = $product->get_children();
			$children       = array_map(
				function( $id ) {
					return \wc_get_product( $id );
				},
				$children_id
			);
			$has_discounted = false;
			foreach ( $children as $child ) {
				if ( empty( $child ) ) {
					continue;
				}
				$sub = $this->get_absolute_min_max_discounted_price_per_product( $child );
				if ( is_null( $sub ) ) {
					if ( is_null( $result['min'] ) ) {
						$result['min'] = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $child );
					}
					if ( is_null( $result['max'] ) ) {
						$result['max'] = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $child );
					}
					continue;
				}
				$has_discounted = true;
				$result['min']  = is_null( $result['min'] ) ? $sub['min'] : min( $sub['min'], $result['min'] );
				$result['max']  = is_null( $result['max'] ) ? $sub['max'] : max( $sub['max'], $result['max'] );
			}
			if ( $has_discounted ) {
				return $result;
			}
			return null;
		} else {
			return $this->get_absolute_min_max_discounted_price_per_product( $product );
		}
	}

	/**
	 * Calculate absolute minimum and maximum discounted price of current product
	 *
	 * @param \WC_Product $product Produce.
	 */
	private function get_absolute_min_max_discounted_price_per_product( $product ) {
		$product_price   = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
		$min_max_percent = $this->get_absolute_min_max_percent_per_product( $product );
		if ( is_null( $min_max_percent ) || ( empty( $min_max_percent['min'] ) && empty( $min_max_percent['max'] ) ) ) {
			return null;
		} else {
			return array(
				'min' => $product_price - ( $product_price * $min_max_percent['max'] / 100 ),
				'max' => $product_price - ( $product_price * $min_max_percent['min'] / 100 ),
			);
		}
	}

}
