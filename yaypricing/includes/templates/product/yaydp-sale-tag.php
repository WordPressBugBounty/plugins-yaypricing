<?php
/**
 * The Template for displaying sale tag for product
 *
 * @package YayPricing\Templates
 *
 * @param $min_percent_discount
 * @param $max_percent_discount
 * @param $has_image_gallery
 * @param $show_sale_off_amount
 * @param $product
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: min and max passed in maybe is the same, so in this case just take 1.
$percent_discounts = array_unique( array( $min_percent_discount, $max_percent_discount ) );

$show_up_to = count( $percent_discounts ) > 1;

// Note: Remove discount 0%.
$percent_discounts = array_filter(
	$percent_discounts,
	function( $value ) {
		return ! empty( $value );
	}
);

$sale_text        = apply_filters( 'yaydp_sale_tag_text', __( 'SALE!', 'yaypricing' ), empty( $matching_rules ) ? array() : $matching_rules );
$up_to_text       = apply_filters( 'yaydp_sale_tag_up_to_text', __( 'Up to %s', 'yaypricing' ), empty( $matching_rules ) ? array() : $matching_rules );
$background_color = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->get_sale_tag_bg_color();
$text_color       = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->get_sale_tag_text_color();

?>
<div class="yaydp-sale-tag<?php echo empty( $is_custom ) ? '' : ' yaydp-custom-sale-tag'; ?>" style="<?php echo esc_attr( $has_image_gallery ? 'right: 50px;' : '' ); ?>;background-color:<?php echo esc_attr( $background_color ); ?>;border-color:<?php echo esc_attr( $background_color ); ?>;color:<?php echo esc_attr( $text_color ); ?>;">
	<div><?php echo esc_html( $sale_text ); ?></div>
	<?php
	if ( ! empty( $percent_discounts ) && $show_sale_off_amount ) :
		\yaydp_sort_array( $percent_discounts );
		$max_discount = end( $percent_discounts ); // Take the highest one (last item after sort by asc).

		// Note: There will be a case: ceil( 30 ) = 31. So need to check with this algorithm.
		if ( round( $max_discount, 10 ) !== floor( $max_discount ) ) {
			$round_value = ceil( $max_discount );
		} else {
			$round_value = floor( $max_discount );
		}
		?>
		<div>
			<?php
				// Translators: max percent discount.
			if ( $show_up_to ) {
				echo wp_kses_post( sprintf( $up_to_text, "$round_value%" ) );
			} else {
				echo esc_html( "$round_value%" );
			}
			?>
		</div>
		<?php
	endif;
	?>
</div>
<?php
