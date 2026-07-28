<?php
/**
 * Represents a YayPricing discounted price for product
 *
 * @since 2.4
 *
 * @package YayPricing\SaleDisplay
 */

namespace YAYDP\Core\Sale_Display;

/**
 * Declare class
 */
class YAYDP_Discounted_Price {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_filter( 'woocommerce_get_price_html', array( $this, 'change_product_price_html' ), 100000, 2 );
	}

	/**
	 * Callback for woocommerce_get_price_html hook
	 *
	 * @param string      $html Current price html.
	 * @param \WC_Product $product Current product.
	 */
	public function change_product_price_html( $html, $product ) {
		$can_current_user_see_discounted_price = apply_filters( 'yaydp_can_current_user_see_discount_rule', true );
		if ( ! $can_current_user_see_discounted_price ) {
			return $html;
		}
		if ( apply_filters( 'yaydp_change_price_html', true ) === false ) {
			return $html;
		}
		if ( empty( $product ) ) {
			return $html;
		}

		$show_discounted_price = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_discounted_price();
		if ( ! $show_discounted_price ) {
			return $html;
		}

		$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
		$min_max_discounted_price = $product_sale->get_min_max_discounted_price();
		$product_price            = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );

		// Note: Acceptable when not empty min_max. Current price is different with min_max.
		if ( is_null( $min_max_discounted_price ) ) {
			return $html;
		}

		if ( \yaydp_is_variable_product( $product ) ) {
			$min_price = \yaydp_get_variable_product_min_price( $product );
			$max_price = \yaydp_get_variable_product_max_price( $product );
			if ( $min_price === $min_max_discounted_price['min'] && $max_price === $min_max_discounted_price['max'] ) {
				return $html;
			}
		} else {
			if ( $product_price === $min_max_discounted_price['min'] && $product_price === $min_max_discounted_price['max'] ) {
				return $html;
			}
		}

		$min_discounted_price = $min_max_discounted_price['min'];
		$max_discounted_price = $min_max_discounted_price['max'];
		$min_discounted_rate  = 1;
		$max_discounted_rate  = 1;
		$base_min_price       = isset( $min_price ) ? floatval( $min_price ) : floatval( $product_price );
		$base_max_price       = isset( $max_price ) ? floatval( $max_price ) : floatval( $product_price );
		if ( ! empty( $min_discounted_price ) && $base_min_price > 0 ) {
			$min_discounted_rate = $min_discounted_price / $base_min_price;
		}
		if ( ! empty( $max_discounted_price ) && $base_max_price > 0 ) {
			$max_discounted_rate = $max_discounted_price / $base_max_price;
		}

		$show_discounted_with_regular_price = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_discounted_with_regular_price();

		// Use the labeled two-line layout only for the main variable product on a single product page.
		$is_main_variable = \yaydp_is_variable_product( $product )
			&& function_exists( 'is_product' ) && is_product()
			&& (int) $product->get_id() === (int) get_queried_object_id();

		/**
		 * Filters whether the discounted price renders with the labeled two-line layout.
		 *
		 * Return true for the two-line layout (an "Original price" row and a "Discounted price" row,
		 * where the discounted range covers only variations that actually receive a discount).
		 * Defaults to false, which keeps the compact single-line layout.
		 *
		 * Only consulted for the main variable product on a single product page, and only while
		 * the "Show product regular price within discounted" setting is enabled.
		 *
		 * @since 3.5.8
		 *
		 * @param bool        $use_two_lines Whether to use the two-line layout. Default false.
		 * @param \WC_Product $product       Product being rendered.
		 */
		$use_two_lines = apply_filters( 'yaydp_use_two_lines_discounted_price', false, $product );

		$use_labeled = $is_main_variable
			&& $show_discounted_with_regular_price
			&& $use_two_lines;

		$discounted_range = null;
		if ( $use_labeled ) {
			$discounted_range = $product_sale->get_discounted_variations_min_max_price();
			if ( is_null( $discounted_range ) ) {
				$use_labeled = false; // No actually-discounted variation, fall back to compact.
			}
		}

		if ( $use_labeled ) {
			$template = 'product/yaydp-discounted-price-variable-labeled.php';
			$args     = array(
				'product'        => $product,
				'discounted_min' => $discounted_range['min'],
				'discounted_max' => $discounted_range['max'],
			);
		} else {
			$template = 'product/yaydp-discounted-price.php';
			$args     = array(
				'product'                            => $product,
				'min_discounted_price'               => $min_discounted_price,
				'max_discounted_price'               => $max_discounted_price,
				'show_discounted_with_regular_price' => $show_discounted_with_regular_price,
			);
		}

		ob_start();
		echo '<span class="hidden yaydp-product-discounted-data" style="display: none" data-product-id="' . $product->get_id() . '" data-min-rate="' . $min_discounted_rate . '" data-max-rate="' . $max_discounted_rate . '"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		\wc_get_template(
			$template,
			$args,
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
		$content = ob_get_contents();
		ob_end_clean();
		if ( ! empty( $content ) ) {
			return $content;
		}
		return $html;
	}

}