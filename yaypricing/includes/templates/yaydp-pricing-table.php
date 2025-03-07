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
$columns_order        = $pricing_table->get_pricing_table_columns_order();
$quantity_title = $pricing_table->get_quantity_title();
$discount_title = $pricing_table->get_discount_title();
$price_title    = $pricing_table->get_price_title();
$border_color   = $pricing_table->get_border_color();
$border_style   = $pricing_table->get_border_style();

$applicable_variations = array();

if ( yaydp_is_variable_product( $product ) ) {
	foreach ( $product->get_children() as $variation_id ) {
		$variation = \wc_get_product( $variation_id );
		if ( $rule->can_apply_adjustment( $variation ) ) {
			$applicable_variations[] = $variation_id;
		}
	}
}

?>

<div class="yaydp-pricing-table-wrapper" data-applicable-variations="<?php echo esc_attr( implode( ',', $applicable_variations ) ); ?>">
	<strong class="yaydp-pricing-table-header"><?php echo esc_html( $table_title ); ?></strong>
	<table class="yaydp-pricing-table" style="border-color: <?php echo esc_attr( $border_color ); ?>;">
		<thead>
			<tr>
				<?php
                    foreach ( $columns_order as $column ) :
                        $header_title = '';
                        switch ( $column ) {
                            case 'quantity_title':
                                $header_title = $quantity_title;
                                break;
                            case 'discount_title':
                                $header_title = $discount_title;
                                break;
                            case 'price_title':
                                $header_title = $price_title;
                                break;
                        }
                        ?>
                        <th data-key="<?php echo esc_attr( $column ); ?>" style="border-color: <?php echo esc_attr( $border_color ); ?>;">
                            <?php echo esc_html( $header_title ); ?>
                        </th>
                        <?php
                    endforeach;
                ?>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $rule->get_ranges() as $range ) :
				$range_instance = new \YAYDP\Core\Rule\Product_Pricing\YAYDP_Bulk_Range( $range );
				?>
				<tr data-min-value="<?php echo esc_attr( $range['from_quantity'] ); ?>" data-max-value="<?php echo esc_attr( $range['to_quantity'] ); ?>">
					<?php
						foreach ( $columns_order as $column ) {
							switch ( $column ) {
								case 'quantity_title':
									echo '<td data-key="quantity" style="border-color: ' . esc_attr( $border_color ) . ';">' 
									. esc_html( $pricing_table->get_quantity_text( $range_instance ) ) . 
									'</td>';
									break;
								case 'discount_title':
									echo '<td data-key="discount" style="border-color: ' . esc_attr( $border_color ) . ';" data-variable="discount_value" data-formula="' . esc_attr( $pricing_table->get_discount_value_formula( $range_instance ) ) . '">' 
									. wp_kses_post( $pricing_table->get_discount_text( $range_instance ) ) . 
									'</td>';
									break;
								case 'price_title':
									echo '<td data-key="price" style="border-color:' . esc_attr( $border_color ) . ';" data-variable="discounted_price" data-formula="' . esc_attr( $pricing_table->get_discounted_price_formula( $range_instance ) ) . '">' 
									. wp_kses_post( $pricing_table->get_discounted_price_text( $range_instance ) ) . 
									'</td>';
									break;
							}
						}
					?>
				</tr>
				<?php
			endforeach;
			?>
		</tbody>
	</table>
</div>
