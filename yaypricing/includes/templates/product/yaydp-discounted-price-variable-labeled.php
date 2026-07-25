<?php
/**
 * Labeled two-line discounted price for a variable product on the single product page.
 *
 * Renders an original line (single value or range) and a discounted line (single value or
 * range), each with an adaptive label. The discounted values come from variations that
 * actually receive a discount.
 *
 * @package YayPricing\Templates
 *
 * @param \WC_Product $product
 * @param float       $discounted_min
 * @param float       $discounted_max
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$yaydp_format_price = function( $price ) use ( $product ) {
	$price = \wc_get_price_to_display( $product, array( 'price' => $price ) );
	$price = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $price ); // Maybe converted by other currency plugin.
	return \wc_price( $price );
};

// Original prices: store prices (not affected by YayPricing).
$original_min    = \yaydp_get_variable_product_store_min_price( $product );
$original_max    = \yaydp_get_variable_product_store_max_price( $product );
$original_values = array_unique(
	array_filter(
		array( $original_min, $original_max ),
		function( $price ) {
			return floatval( $price ) > 0;
		}
	)
);
\yaydp_sort_array( $original_values );
$original_is_range = count( $original_values ) > 1;

// Discounted prices: only variations receiving an active discount.
$discounted_values = array_unique( array( $discounted_min, $discounted_max ) );
\yaydp_sort_array( $discounted_values );
$discounted_is_range = count( $discounted_values ) > 1;

$original_label   = $original_is_range ? __( 'Original price range:', 'yaypricing' ) : __( 'Original price:', 'yaypricing' );
$discounted_label = $discounted_is_range ? __( 'Discounted price range:', 'yaypricing' ) : __( 'Discounted price:', 'yaypricing' );

$original_html   = implode( ' - ', array_map( $yaydp_format_price, $original_values ) );
$discounted_html = implode( ' - ', array_map( $yaydp_format_price, $discounted_values ) );
?>

<span class="yaydp-discounted-price yaydp-labeled">
	<span class="yaydp-original-prices">
		<span class="yaydp-price-label"><?php echo esc_html( $original_label ); ?></span>
		<span class="yaydp-original-price-value"><?php echo $original_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	</span>
	<span class="yaydp-calculated-prices">
		<span class="yaydp-price-label"><?php echo esc_html( $discounted_label ); ?></span>
		<ins aria-hidden="true"><?php echo $discounted_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ins>
	</span>
	<span class="yaydp-calculated-prices-suffix">
		<?php echo wp_kses_post( $product->get_price_suffix() ); ?>
	</span>
</span>
