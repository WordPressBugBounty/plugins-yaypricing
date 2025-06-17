<?php

namespace YAYDP\Core\Manager;

use YAYDP\Settings\YAYDP_General_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Order_Manager {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'woocommerce_admin_order_items_after_line_items', array( $this, 'add_discount_description_to_order' ), 10, 3 );
		if (YAYDP_General_Settings::get_instance()->do_show_original_price_and_saved_amount()) {
			add_action( 'woocommerce_admin_order_item_headers', array( $this, 'add_order_item_header' ) );
			add_action( 'woocommerce_admin_order_item_values', array( $this, 'add_order_item_value' ), 10, 1 );
			add_action( 'woocommerce_admin_order_totals_after_tax', array( $this, 'add_saved_amount_to_order_totals' ), 10, 3 );
		}
	}

	public function add_discount_description_to_order( $order_id ) {
		$applied_rules = $this->get_order_pricing_rules( $order_id );

		if ( empty( $applied_rules ) ) {
			return;
		}
		?>
		<tr class="item yaydp-applied-rules">
			<td class="thumb"></td>
			<td colspan="5">
				<div class="yaydp-applied-rules-wrapper" style="display: flex; align-items: center; gap: 5px;">
					<label class="yaydp-applied-rules__title"><strong><?php esc_html_e( 'YayPricing applied rules:', 'yaypricing' ); ?></strong></label>
					<span class="yaydp-applied-rules__list">
					<?php
					foreach ( $applied_rules as $index => $rule_id ) :
						$rule = yaydp_get_pricing_rule_by_id( $rule_id );
						if ( 0 != $index ) {
							echo '<span class="yaydp-applied-rule-separator">,</span>';
						}
						echo '<span class="yaydp-applied-rule">' . esc_html( $rule ? $rule->get_name() : $rule_id ) . '</span>';
						?>
					<?php endforeach; ?>
					</span>
				</div>
			</td>
		</tr>
		<?php
	}

	public function get_order_pricing_rules( $order_id ) {
		if ( \yaydp_check_wc_hpos() ) {
			$order = \wc_get_order( $order_id );
			return $order->get_meta( 'yaydp_product_pricing_rules', true );
		} else {
			return get_post_meta( $order_id, 'yaydp_product_pricing_rules', true );
		}
	}

	public function add_order_item_header() {
		echo '<th class="item_original_price sortable" data-sort="float">' . esc_html( 'Original Price' ) . '</th>';
	}

	public function add_order_item_value( $_product ) {
		if( $_product instanceof \WC_Product ) {
			$discount_based_on = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->get_discount_base_on();
			if ( 'regular_price' === $discount_based_on ) {
				$original_price = $_product->get_regular_price();
			} else {
				$original_price = $_product->get_sale_price();
			}
			echo '<td class="item_original_price" data-sort-value="' . esc_attr( $original_price ) . '"><del>' . \wc_price( $original_price ) . '</del></td>';
		}
	}

	public function add_saved_amount_to_order_totals( $order_id ) {
		$discount_based_on = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->get_discount_base_on();
		$order             = \wc_get_order( $order_id );
		$items             = $order->get_items( 'line_item' );
		$original_total    = 0;
		foreach( $items as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			if ( 'regular_price' === $discount_based_on ) {
				$original_price = $product->get_regular_price();
			} else {
				$original_price = $product->get_sale_price();
			}
			$quantity = $item->get_quantity();
			$original_total += (float)$original_price * (float)$quantity;
		}
		$saved_amount = $original_total - $order->get_subtotal();
		echo '
		<tr>
			<td class="label"> ' . esc_html__( 'Saved Amount:', 'yaypricing' ) . ' </td>
			<td width="1%"></td>
			<td class="total">' . wc_price( $saved_amount, array( 'currency' => $order->get_currency() ) ) . '</td>
		</tr>
		';
	}

}

YAYDP_Order_Manager::get_instance();
