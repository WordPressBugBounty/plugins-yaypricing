<?php
/**
 * The Template for displaying pricing table
 *
 * @package YayPricing\Templates
 * @param $product
 * @param $rule
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pricing_table  = new \YAYDP\NoName\YAYDP_Pricing_Table( $product, $rule );
$table_title    = $pricing_table->get_table_title();
$quantity_title = $pricing_table->get_quantity_title();
$discount_title = $pricing_table->get_discount_title();
$price_title    = $pricing_table->get_price_title();
$border_color   = $pricing_table->get_border_color();
$border_style   = $pricing_table->get_border_style();
?>

<div class="yaydp-pricing-table-wrapper">
	<strong class="yaydp-pricing-table-header"><?php echo esc_html( $table_title ); ?></strong>
	<table class="yaydp-pricing-table" style="border-color: <?php echo esc_attr( $border_color ); ?>;">
		<thead>
			<tr>
				<th style="border-color: <?php echo esc_attr( $border_color ); ?>;"><?php echo esc_html( $quantity_title ); ?></th>
				<th style="border-color: <?php echo esc_attr( $border_color ); ?>;"><?php echo esc_html( $discount_title ); ?></th>
				<th style="border-color: <?php echo esc_attr( $border_color ); ?>;"><?php echo esc_html( $price_title ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $rule->get_ranges() as $range ) :
				$range_instance = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range( $range );
				?>
				<tr>
					<td style="border-color: <?php echo esc_attr( $border_color ); ?>;">
						<?php
							echo esc_html( $pricing_table->get_quantity_text( $range_instance ) );
						?>
					</td>
					<td style="border-color: <?php echo esc_attr( $border_color ); ?>;" data-variable="discount_value" data-formula="<?php echo esc_attr( $pricing_table->get_discount_value_formula( $range_instance ) ); ?>">
						<?php echo wp_kses_post( $pricing_table->get_discount_text( $range_instance ) ); ?>
					</td>
					<td style="border-color: <?php echo esc_attr( $border_color ); ?>;" data-variable="discounted_price" data-formula="<?php echo esc_attr( $pricing_table->get_discounted_price_formula( $range_instance ) ); ?>">
						<?php echo wp_kses_post( $pricing_table->get_discounted_price_text( $range_instance ) ); ?>
					</td>
				</tr>
				<?php
			endforeach;
			?>
		</tbody>
	</table>
</div>
