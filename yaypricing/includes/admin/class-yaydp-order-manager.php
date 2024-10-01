<?php

namespace YAYDP\Core\Manager;

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
					<label class="yaydp-applied-rules__title"><strong><?php echo __( 'YayPricing applied rules:', 'yaypricing' ); ?></strong></label>
					<span class="yaydp-applied-rules__list">
					<?php
					foreach ( $applied_rules as $index => $rule_id ) :
						$rule = yaydp_get_pricing_rule_by_id( $rule_id );
						if ( $index != 0 ) {
							echo '<span class="yaydp-applied-rule-separator">,</span>';
						}
						echo '<span class="yaydp-applied-rule">' . ( $rule ? $rule->get_name() : $rule_id ) . '</span>';
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
			$order->save();
		} else {
			return get_post_meta( $order_id, 'yaydp_product_pricing_rules', true );
		}
	}

}

YAYDP_Order_Manager::get_instance();
