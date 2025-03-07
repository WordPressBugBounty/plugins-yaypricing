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
			$product_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $product );
			if ( $product_price === $min_max_discounted_price['min'] && $product_price === $min_max_discounted_price['max'] ) {
				return $html;
			}
		}

		$min_discounted_price = $min_max_discounted_price['min'];
		$max_discounted_price = $min_max_discounted_price['max'];
		$min_discounted_rate  = 1;
		$max_discounted_rate  = 1;
		if ( ! empty( $min_discounted_price ) ) {
			$min_discounted_rate = ! empty( $min_price ) ? ( $min_discounted_price / $min_price ) : ( $min_discounted_price / $product_price );
		}
		if ( ! empty( $max_discounted_price ) ) {
			$max_discounted_rate = ! empty( $max_price ) ? ( $max_discounted_price / $max_price ) : ( $max_discounted_price / $product_price );
		}

		$show_discounted_with_regular_price = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_discounted_with_regular_price();
		ob_start();
		echo '<span class="hidden yaydp-product-discounted-data" style="display: none" data-product-id="' . $product->get_id() . '" data-min-rate="' . $min_discounted_rate . '" data-max-rate="' . $max_discounted_rate . '"></span>';
		\wc_get_template(
			'product/yaydp-discounted-price.php',
			array(
				'product'                            => $product,
				'min_discounted_price'               => $min_discounted_price,
				'max_discounted_price'               => $max_discounted_price,
				'show_discounted_with_regular_price' => $show_discounted_with_regular_price,
			),
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
