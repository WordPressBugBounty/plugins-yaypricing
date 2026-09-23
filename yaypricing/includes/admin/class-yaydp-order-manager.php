<?php

namespace YAYDP\Core\Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Order_Manager {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Id of the phantom meta box that backs the "Original price & saved amount"
	 * checkbox under Screen Options > Screen elements on the edit-order screen.
	 * Doubles as the CSS class on every element that checkbox toggles.
	 */
	const SAVINGS_BOX_ID = 'yaydp-order-savings';

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'woocommerce_admin_order_items_after_line_items', array( $this, 'add_discount_description_to_order' ), 10, 3 );
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'add_order_item_header' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'add_order_item_value' ), 10, 2 );
		add_action( 'woocommerce_admin_order_totals_after_tax', array( $this, 'add_saved_amount_to_order_totals' ), 10, 3 );
		add_action( 'add_meta_boxes', array( $this, 'register_savings_screen_option' ) );
		add_filter( 'default_hidden_meta_boxes', array( $this, 'hide_savings_by_default' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_savings_toggle_script' ) );
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

	/**
	 * Screen id of the edit-order screen: `shop_order` (posts) or
	 * `woocommerce_page_wc-orders` (HPOS). Resolved by WC, so no HPOS branching here.
	 *
	 * @return string
	 */
	private function get_order_screen_id() {
		return \wc_get_page_screen_id( 'shop-order' );
	}

	/**
	 * Registers the meta box that puts the "Original price & saved amount" checkbox
	 * under Screen Options > Screen elements.
	 *
	 * Context `yaydp` is never passed to do_meta_boxes() by WP/WC, so no postbox is
	 * rendered on the page; meta_box_prefs() still lists it and WP core persists the
	 * per-user choice in `metaboxhidden_{screen}`.
	 *
	 * @param string $screen Post type (legacy) or screen id (HPOS).
	 */
	public function register_savings_screen_option( $screen ) {
		if ( $this->get_order_screen_id() !== $screen ) {
			return;
		}
		add_meta_box( self::SAVINGS_BOX_ID, __( 'Original price & saved amount', 'yaypricing' ), '__return_null', $screen, 'yaydp' );
		// Only edit screens fire add_meta_boxes, so the anchor never reaches the HPOS list (same screen id).
		add_action( 'admin_footer', array( $this, 'render_savings_postbox_anchor' ) );
	}

	/**
	 * Hides the column and totals row until the user opts in.
	 *
	 * @param array      $hidden Default hidden meta box ids.
	 * @param \WP_Screen $screen Current screen.
	 * @return array
	 */
	public function hide_savings_by_default( $hidden, $screen ) {
		if ( $this->get_order_screen_id() === $screen->id ) {
			$hidden[] = self::SAVINGS_BOX_ID;
		}
		return $hidden;
	}

	/**
	 * Whether the current user has the savings elements hidden.
	 *
	 * Uses the screen id string rather than get_current_screen(): the item hooks also
	 * fire during WC AJAX re-renders (add item, recalculate) where there is no screen.
	 *
	 * @return bool
	 */
	private function is_savings_hidden() {
		return in_array( self::SAVINGS_BOX_ID, \get_hidden_meta_boxes( $this->get_order_screen_id() ), true );
	}

	/**
	 * CSS class list for a toggled element.
	 *
	 * @return string
	 */
	private function get_savings_class() {
		return self::SAVINGS_BOX_ID . ( $this->is_savings_hidden() ? ' hidden' : '' );
	}

	/**
	 * Zero-size DOM anchor for the phantom meta box.
	 *
	 * WP's postbox.js builds the `hidden` list it persists from `.postbox:hidden` elements, and
	 * shows/hides `#{box id}` when its Screen Options checkbox is toggled — so the box must
	 * exist in the DOM. `hide-if-js` mirrors what do_meta_boxes() emits for a hidden box.
	 */
	public function render_savings_postbox_anchor() {
		printf(
			'<div id="%s" class="postbox%s" style="height:0;margin:0;padding:0;border:0;box-shadow:none;overflow:hidden" aria-hidden="true"></div>',
			esc_attr( self::SAVINGS_BOX_ID ),
			$this->is_savings_hidden() ? ' hide-if-js' : ''
		);
	}

	/**
	 * Mirrors the Screen Options checkbox onto the column/row without a reload.
	 * Persistence is handled by WP core's postbox.js (`closed-postboxes` request).
	 */
	public function enqueue_savings_toggle_script() {
		$screen = get_current_screen();
		if ( ! $screen || $this->get_order_screen_id() !== $screen->id ) {
			return;
		}
		wp_add_inline_script(
			'postbox',
			"jQuery(document).on('change','#" . self::SAVINGS_BOX_ID . "-hide',function(){jQuery('." . self::SAVINGS_BOX_ID . "').toggleClass('hidden',!this.checked);});"
		);
	}

	public function add_order_item_header() {
		echo '<th class="item_original_price sortable ' . esc_attr( $this->get_savings_class() ) . '" data-sort="float">' . esc_html__( 'Original Price', 'yaypricing' ) . '</th>';
	}

	/**
	 * Price the line would have cost per unit without any Woo sale or YayPricing
	 * discount: the regular price, regardless of the "discount based on" setting.
	 *
	 * @param \WC_Product $product Product behind the order line.
	 * @return float
	 */
	private function get_item_original_price( $product ) {
		return (float) apply_filters( 'yaydp_other_source_product_base_price', floatval( $product->get_regular_price() ), $product );
	}

	/**
	 * Line subtotal actually charged (before coupons), on the same tax basis as the
	 * regular price so the two can be compared.
	 *
	 * @param \WC_Order_Item_Product $item Order line.
	 * @return float
	 */
	private function get_item_paid_subtotal( $item ) {
		return (float) $item->get_subtotal() + ( \wc_prices_include_tax() ? (float) $item->get_subtotal_tax() : 0 );
	}

	/**
	 * Whether the difference is a real saving after currency rounding.
	 *
	 * @param float $original Original amount.
	 * @param float $paid     Charged amount.
	 * @return bool
	 */
	private function is_discounted( $original, $paid ) {
		return round( $original - $paid, \wc_get_price_decimals() ) > 0;
	}

	public function add_order_item_value( $_product, $item = null ) {
		if ( ! $_product instanceof \WC_Product || ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}
		$original_price = $this->get_item_original_price( $_product );
		$quantity       = (float) $item->get_quantity();
		$paid_price     = $quantity > 0 ? $this->get_item_paid_subtotal( $item ) / $quantity : 0;
		// Only a line that is actually cheaper than its regular price shows the struck-out original.
		$cell = $this->is_discounted( $original_price, $paid_price )
			? '<del>' . \wc_price( $original_price, array( 'currency' => $item->get_order()->get_currency() ) ) . '</del>'
			: '';
		echo '<td class="item_original_price ' . esc_attr( $this->get_savings_class() ) . '" data-sort-value="' . esc_attr( $original_price ) . '">' . $cell . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function add_saved_amount_to_order_totals( $order_id ) {
		$order          = \wc_get_order( $order_id );
		$original_total = 0;
		$paid_total     = 0;
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			$original_total += $this->get_item_original_price( $product ) * (float) $item->get_quantity();
			$paid_total     += $this->get_item_paid_subtotal( $item );
		}
		$saved_amount = $original_total - $paid_total;
		// Suppress when there is no positive saving (a markup-direction rule can make the
		// subtotal exceed the origin total); never render "Saved Amount: -$X".
		if ( ! $this->is_discounted( $original_total, $paid_total ) ) {
			return;
		}
		echo '
		<tr class="' . esc_attr( $this->get_savings_class() ) . '">
			<td class="label"> ' . esc_html__( 'Saved Amount:', 'yaypricing' ) . ' </td>
			<td width="1%"></td>
			<td class="total">' . \wc_price( $saved_amount, array( 'currency' => $order->get_currency() ) ) . '</td>
		</tr>
		'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}

YAYDP_Order_Manager::get_instance();
