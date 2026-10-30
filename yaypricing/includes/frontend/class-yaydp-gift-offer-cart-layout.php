<?php
/**
 * Prints each free gift offer's panel under its anchor line on the classic
 * cart page.
 *
 * Grouped mode only. Lines keep their cart order; each offer's panel row
 * follows its anchor line (see YAYDP_Gift_Offer_Anchor_Builder), after the
 * line's bundle or composite children. Gift rows themselves are hidden: the
 * panel stands in for them. Outside the table (mini cart, checkout) nothing
 * changes.
 *
 * The panel is printed from `woocommerce_cart_item_visible`, the only per-row
 * hook that runs before a row's `<tr>` opens; `$in_table` keeps that side
 * effect inside the cart table.
 *
 * @package YayPricing\Frontend
 */

namespace YAYDP\Frontend;

use YAYDP\Helper\YAYDP_Gift_Helper as Gift_Helper;
use YAYDP\Helper\YAYDP_Gift_Offer_Group_Helper as Offer_Group;
use YAYDP\Frontend\YAYDP_Gift_Offer_Panel_Renderer as Panel_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * YAYDP_Gift_Offer_Cart_Layout class
 */
class YAYDP_Gift_Offer_Cart_Layout {

	/**
	 * Whether the cart table loop is running.
	 *
	 * @var bool
	 */
	private $in_table = false;

	/**
	 * Anchor key => anchor group, captured when the table starts.
	 *
	 * @var array
	 */
	private $anchors = array();

	/**
	 * Group of the offers with no line on screen.
	 *
	 * @var array|null
	 */
	private $orphans = null;

	/**
	 * Anchor key whose panel waits for the anchor's rows to end.
	 *
	 * @var string|null
	 */
	private $pending = null;

	/**
	 * Rule ids that own a panel.
	 *
	 * @var array
	 */
	private $rule_ids = array();

	/**
	 * Anchor keys whose panel is already out.
	 *
	 * @var array
	 */
	private $printed = array();

	/**
	 * Cart contents, captured when the table starts.
	 *
	 * @var array
	 */
	private $cart = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_before_cart_contents', array( $this, 'start_table' ), 999 );
		add_filter( 'woocommerce_cart_item_visible', array( $this, 'on_row' ), 30, 3 );
		add_action( 'woocommerce_cart_contents', array( $this, 'end_table' ), 5 );
		// Fallback for cart templates that drop woocommerce_cart_contents.
		add_action( 'woocommerce_after_cart_contents', array( $this, 'end_table' ), 5 );
	}

	/**
	 * Capture the anchors before the loop reads the cart.
	 */
	public function start_table() {
		if ( ! is_cart() ) {
			return;
		}
		$this->anchors  = array();
		$this->orphans  = null;
		$this->pending  = null;
		$this->printed  = array();
		$this->rule_ids = array();
		$this->cart     = \WC()->cart->get_cart();
		foreach ( Offer_Group::get_anchors() as $group ) {
			$this->rule_ids += array_fill_keys( $group['rule_ids'], true );
			if ( '' === $group['anchor'] ) {
				$this->orphans = $group;
			} else {
				$this->anchors[ $group['anchor'] ] = $group;
			}
		}
		$this->in_table = ! empty( $this->anchors ) || null !== $this->orphans;
	}

	/**
	 * Close the previous offer when the loop leaves it, open the next one, and
	 * hide gift rows.
	 *
	 * @param bool   $visible Whether the row renders.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return bool
	 */
	public function on_row( $visible, $cart_item, $cart_item_key ) {
		if ( ! $this->in_table ) {
			return $visible;
		}

		if ( null !== $this->pending && ! $this->is_within( $cart_item_key, $this->pending ) ) {
			$this->flush( $this->pending );
		}
		if ( isset( $this->anchors[ $cart_item_key ] ) && ! isset( $this->printed[ $cart_item_key ] ) ) {
			$this->pending = $cart_item_key;
		}

		// Gifts of an offer with a panel are listed there. Any other gift row
		// (its rule is gone) keeps the regular grouped row.
		if ( Gift_Helper::is_gift_item( $cart_item ) ) {
			$rule = Gift_Helper::get_gift_rule( $cart_item );
			if ( ! is_null( $rule ) && isset( $this->rule_ids[ (string) $rule->get_rule_id() ] ) ) {
				return false;
			}
		}
		return $visible;
	}

	/**
	 * Whether a row is the anchor row or one of its (nested) children.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param string $anchor Anchor key.
	 * @return bool
	 */
	private function is_within( $cart_item_key, $anchor ) {
		$seen = array();
		$key  = $cart_item_key;
		while ( '' !== $key && ! isset( $seen[ $key ] ) ) {
			if ( $key === $anchor ) {
				return true;
			}
			$seen[ $key ] = true;
			$key          = isset( $this->cart[ $key ] ) ? Offer_Group::get_parent_key( $this->cart[ $key ] ) : '';
		}
		return false;
	}

	/**
	 * Print the last open panel, then the offers with no line on screen,
	 * including those whose anchor row never rendered (e.g. a product that
	 * no longer exists), styled as orphans.
	 */
	public function end_table() {
		if ( ! $this->in_table ) {
			return;
		}
		if ( null !== $this->pending ) {
			$this->flush( $this->pending );
		}
		$orphans = null === $this->orphans ? array() : $this->orphans['rule_ids'];
		foreach ( $this->anchors as $key => $group ) {
			if ( ! isset( $this->printed[ $key ] ) ) {
				$orphans = array_merge( $orphans, $group['rule_ids'] );
			}
		}
		if ( ! empty( $orphans ) ) {
			echo Panel_Renderer::render_row( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the renderer.
				array(
					'anchor'   => '',
					'rule_ids' => $orphans,
				)
			);
		}
		$this->in_table = false;
		$this->cart     = array();
	}

	/**
	 * Print the panel row of an anchor, once.
	 *
	 * @param string $key Anchor key.
	 */
	private function flush( $key ) {
		if ( $key === $this->pending ) {
			$this->pending = null;
		}
		if ( isset( $this->printed[ $key ] ) ) {
			return;
		}
		$this->printed[ $key ] = true;
		echo Panel_Renderer::render_row( $this->anchors[ $key ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the renderer.
	}
}
