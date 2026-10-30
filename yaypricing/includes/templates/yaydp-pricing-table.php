<?php
/**
 * The Template for displaying pricing table
 *
 * @package YayPricing\Templates
 * @param $product
 * @param $rule
 * @param $pricing_table Optional, \YAYDP\NoName\YAYDP_Pricing_Table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $pricing_table ) ) {
	$pricing_table = new \YAYDP\NoName\YAYDP_Pricing_Table( $product, $rule );
}
$table_title           = $pricing_table->get_table_title();
$columns_order         = $pricing_table->get_pricing_table_columns_order();
$border_color          = $pricing_table->get_border_color();
$applicable_variations = $pricing_table->get_applicable_variations( $product );

?>

<div class="yaydp-pricing-table-wrapper" data-applicable-variations="<?php echo esc_attr( implode( ',', $applicable_variations ) ); ?>" data-base-price="<?php echo esc_attr( $pricing_table->get_base_display_price() ); ?>">
	<strong class="yaydp-pricing-table-header"><?php echo esc_html( $table_title ); ?></strong>
	<table class="yaydp-pricing-table" style="border-color: <?php echo esc_attr( $border_color ); ?>;">
		<thead>
			<tr>
				<?php foreach ( $columns_order as $column ) : ?>
					<th style="border-color: <?php echo esc_attr( $border_color ); ?>;">
						<?php echo esc_html( $pricing_table->get_column_title( $column ) ); ?>
					</th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $pricing_table->get_rows() as $row ) : ?>
				<tr data-min-value="<?php echo esc_attr( $row['min'] ); ?>" data-max-value="<?php echo esc_attr( $row['max'] ); ?>">
					<?php
					foreach ( $columns_order as $column ) {
						echo $pricing_table->get_table_cell( $row, $column ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in get_table_cell().
					}
					?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
