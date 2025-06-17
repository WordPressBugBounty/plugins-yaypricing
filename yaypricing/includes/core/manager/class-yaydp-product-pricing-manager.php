<?php
/**
 * This class is responsible for managing the pricing of products in the YAYDP system.
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\Core\Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Manager {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_pricings' ), YAYDP_PRODUCT_CALCULATE_PRIORITY );
		add_action( 'yaydp_before_calculate_product_pricing', array( $this, 'before_calculate_pricings' ), 10 );
		add_action( 'yaydp_after_calculate_product_pricing', array( $this, 'after_calculate_pricings' ), 10 );
		add_action( 'woocommerce_before_mini_cart', array( $this, 'recalculate_mini_cart' ), 10 );

		// Change cart item HTML.
		add_filter( 'woocommerce_cart_item_price', array( $this, 'change_cart_item_price_html' ), 100000, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'change_cart_item_subtotal_html' ), 100000, 3 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'change_cart_item_price_html' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'hide_extra_cart_item_remove_link' ), 10, 2 );
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'disable_extra_cart_item_quantity_input' ), 10, 1 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'remove_extra_cart_item_subtotal' ), 100000, 2 );

		add_filter(
			'woocommerce_update_cart_validation',
			function( $check, $cart_item_key, $values ) {
				if ( empty( $values['is_extra'] ) ) {
					return $check;
				}
				$cart_contents = \WC()->cart->cart_contents;
				if ( ! isset( $cart_contents[ $cart_item_key ] ) ) {
					return false;
				}
				return $check;
			},
			1000,
			3
		);

		add_action(
			'woocommerce_add_to_cart',
			function( $key ) {
				$cart_item = \WC()->cart->get_cart_item( $key );

				if ( empty( $cart_item ) ) {
					return;
				}

				if ( empty( $cart_item['is_extra'] ) ) {
					return;
				}

				$cart_item['data']->set_price( 0 );
				if ( \yaydp_product_pricing_is_discount_based_on_regular_price() ) {
					$cart_item['data']->set_regular_price( 0 );
				}
				return;
			}
		);

		// Add offer description.
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'add_offer_description' ), 9 );
		add_action( 'yaydp_product_offer_description', array( $this, 'add_offer_description' ), 9 );

		$this->handle_saving_amount();

		$this->handle_pricing_table();

		$this->handle_sale_flash();

		$this->handle_discounted_price();

		$this->handle_use_time();
	}

	/**
	 * Recalculates the mini cart by updating the cart items and total price.
	 */
	public function recalculate_mini_cart() {
		if ( \is_checkout() ) {
			return;
		}
		\WC()->cart->calculate_totals();
	}

	/**
	 * Remove extra data.
	 */
	private function remove_extra_data() {
		add_filter( 'yaydp_prevent_recalculate_cart', '__return_true' );
		foreach ( \WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( \yaydp_is_extra_wc_cart_item( $cart_item ) ) {
				// \WC()->cart->remove_cart_item( $cart_item_key );
				if ( isset( \WC()->cart->removed_cart_contents[ $cart_item_key ] ) ) {
					unset( \WC()->cart->removed_cart_contents[ $cart_item_key ]['data'] );
				}
				unset( \WC()->cart->cart_contents[ $cart_item_key ] );
				continue;
			}
			if ( isset( $cart_item['extra_data'] ) ) {
				\WC()->cart->cart_contents[ $cart_item_key ]['extra_data'] = '';
			}
			if ( isset( $cart_item['modifiers'] ) ) {
				\WC()->cart->cart_contents[ $cart_item_key ]['modifiers'] = '';
			}
		}
		remove_filter( 'yaydp_prevent_recalculate_cart', '__return_true' );
	}

	/**
	 * This function is called before calculating the pricings for a cart.
	 */
	public function before_calculate_pricings() {
		$this->remove_extra_data();
	}

	/**
	 * This function is called after calculating the pricings for a cart.
	 */
	public function after_calculate_pricings() {
	}

	/**
	 * This function calculates the pricing for a cart.
	 */
	public function calculate_pricings() {

		// remove_action( 'woocommerce_before_calculate_totals', array( self::get_instance(), 'calculate_pricings' ), 100 );

		static $has_run = false;
		if ( $has_run ) {
			return;
		}
		$has_run = true;

		if ( apply_filters( 'yaydp_prevent_recalculate_cart', false ) ) {
			return;
		}

		do_action( 'yaydp_before_calculate_product_pricing' );

		global $yaydp_cart;
		$yaydp_cart = new \YAYDP\Core\YAYDP_Cart();
		\YAYDP\Core\Discounted_Products\YAYDP_Discounted_Products::get_instance()->clear_products();
		$product_pricing_adjustments = new \YAYDP\Core\Adjustments\YAYDP_Product_Pricing_Adjustments( $yaydp_cart );
		$product_pricing_adjustments->do_stuff();
		$yaydp_cart->publish();

		do_action( 'yaydp_after_calculate_product_pricing' );
	}

	/**
	 * Change cart item price.
	 *
	 * @param string $html Current item price html.
	 * @param array $cart_item Cart item.
	 */
	public function change_cart_item_price_html( $html, $cart_item ) {
		$yaydp_cart_item = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );
		if ( $yaydp_cart_item->can_modify() ) {
			$html = $yaydp_cart_item->get_modified_price_html();
		}
		return apply_filters( 'yaydp_cart_item_price_html', $html, $cart_item );
	}

	/**
	 * Hide item price for extra item.
	 *
	 * @param string $html Current item price html.
	 * @param string $cart_item_key Cart item key.
	 */
	public function hide_extra_cart_item_remove_link( $html, $cart_item_key ) {
		$cart_item = \WC()->cart->get_cart_item( $cart_item_key );
		if ( \yaydp_is_extra_wc_cart_item( $cart_item ) ) {
			return \apply_filters( 'yaydp_extra_cart_item_remove_link', '', $html, $cart_item );
		}
		return $html;
	}

	/**
	 * Disable quantity input for extra item.
	 *
	 * @param array $args Input args.
	 */
	public function disable_extra_cart_item_quantity_input( $args ) {
		$input_name = isset( $args['input_name'] ) ? $args['input_name'] : '';
		$delimiter  = '#';
		$start_tag  = 'cart[';
		$end_tag    = '][qty]';
		$regex      = $delimiter . preg_quote( $start_tag, $delimiter )
							. '(.*?)'
							. preg_quote( $end_tag, $delimiter )
							. $delimiter
							. 's';
		preg_match( $regex, $input_name, $matches );
		if ( empty( $matches ) || ! isset( $matches[1] ) ) {
			return $args;
		}
		$cart_item_key = $matches[1];
		$cart_item     = \WC()->cart->get_cart_item( $cart_item_key );
		if ( \yaydp_is_extra_wc_cart_item( $cart_item ) ) {
			$args['readonly'] = true;
			return \apply_filters( 'yaydp_extra_cart_item_quantity_input_args', $args, $cart_item );
		}
		return $args;
	}

	/**
	 * Remove subtotal for extra item.
	 *
	 * @param string $html Current item price html.
	 * @param array  $cart_item Cart item.
	 */
	public function remove_extra_cart_item_subtotal( $html, $cart_item ) {
		if ( \yaydp_is_extra_wc_cart_item( $cart_item ) ) {
			return \apply_filters( 'yaydp_extra_cart_item_subtotal', \wc_price( 0 ), $html, $cart_item );
		}
		return $html;
	}

	/**
	 * Add offer description.
	 */
	public function add_offer_description() {
		global $product;
		if ( empty( $product ) ) {
			return;
		}
		$running_rules      = \yaydp_get_running_product_pricing_rules();
		$offer_descriptions = array();
		foreach ( $running_rules as $rule ) {
			$filters = $rule->get_buy_filters();
			if ( \yaydp_is_buy_x_get_y( $rule ) ) {
				if ( $rule->can_apply_adjustment( $product, $filters, $rule->get_match_type_of_buy_filters() ) ) {
					$offer_description = $rule->get_offer_description( $product, 'buy_content' );
					if ( $offer_description->can_display() ) {
						$offer_descriptions[] = $offer_description;
					}
				}

				$receive_filters = $rule->get_receive_filters();
				if ( $rule->can_apply_adjustment( $product, $receive_filters ) ) {
					$offer_description = $rule->get_offer_description( $product, 'get_content' );
					if ( $offer_description->can_display() ) {
						$offer_descriptions[] = $offer_description;
					}
				}
				continue;
			}
			$match_type = $rule->get_match_type_of_buy_filters();
			if ( $rule->can_apply_adjustment( $product, $filters, $match_type ) ) {
				$offer_description = $rule->get_offer_description( $product, 'buy_content' );
				if ( ! $offer_description->can_display() ) {
					continue;
				}
				$offer_descriptions[] = $offer_description;
			}
		}
		\wc_get_template(
			'yaydp-offer-description.php',
			array(
				'offer_descriptions' => $offer_descriptions,
				'product'            => $product,
			),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
	}

	/**
	 * Handle add pricing table on specific hooks.
	 */
	private function handle_pricing_table() {
		$table_position = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->get_pricing_table_position();
		switch ( $table_position ) {
			case 'before_add_to_cart_button':
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'add_pricing_table' ), 10 );
				// add_action( 'woocommerce_single_product_summary', array( $this, 'add_pricing_table' ), 29 );
				break;
			case 'after_add_to_cart_button':
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'add_pricing_table' ), 10 );
				// add_action( 'woocommerce_single_product_summary', array( $this, 'add_pricing_table' ), 31 );
				break;
			case 'before_single_product_summary':
				add_action( 'woocommerce_before_single_product_summary', array( $this, 'add_pricing_table' ), 10 );
				// add_action( 'woocommerce_single_product_summary', array( $this, 'add_pricing_table' ), 19 );
				break;
			case 'after_single_product_summary':
				add_action( 'woocommerce_after_single_product_summary', array( $this, 'add_pricing_table' ), 10 );
				// add_action( 'woocommerce_single_product_summary', array( $this, 'add_pricing_table' ), 21 );
				break;
			case 'product_meta_start':
				add_action( 'woocommerce_product_meta_start', array( $this, 'add_pricing_table' ), 10 );
				// add_action( 'woocommerce_single_product_summary', array( $this, 'add_pricing_table' ), 39 );
				break;
			case 'product_meta_end':
				add_action( 'woocommerce_product_meta_end', array( $this, 'add_pricing_table' ), 10 );
				// add_action( 'woocommerce_single_product_summary', array( $this, 'add_pricing_table' ), 41 );
				break;

			default:
				break;
		}
		add_action( 'yaydp_product_pricing_table', array( $this, 'add_pricing_table' ) );
	}

	/**
	 * Add pricing table.
	 */
	public function add_pricing_table() {
		global $product;

		if ( empty( $product ) ) {
			return;
		}

		$running_rules = \yaydp_get_running_product_pricing_rules();
		foreach ( $running_rules as $rule ) {
			if ( ! \yaydp_is_bulk_pricing( $rule ) && ! \yaydp_is_tiered_pricing( $rule ) ) {
				continue;
			}
			if ( ! $rule->can_apply_adjustment( $product ) ) {
				continue;
			}

			\wc_get_template(
				'yaydp-pricing-table.php',
				array(
					'rule'    => $rule,
					'product' => $product,
				),
				'',
				YAYDP_PLUGIN_PATH . 'includes/templates/'
			);
		}
	}

	/**
	 * Handle use time.
	 */
	private function handle_use_time() {
		\YAYDP\Core\Use_Time\YAYDP_Product_Pricing_Use_Time::get_instance();
	}

	/**
	 * Handle sale flash.
	 */
	private function handle_sale_flash() {
		\YAYDP\Core\Sale_Display\YAYDP_Sale_Flash::get_instance();
	}

	/**
	 * Handle the discounted price.
	 */
	private function handle_discounted_price() {
		\YAYDP\Core\Sale_Display\YAYDP_Discounted_Price::get_instance();
	}

	private function handle_saving_amount() {
		$show_order_saving_amount = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_order_saving_amount();
		$position                 = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->order_saving_amount_position();

		if ( $show_order_saving_amount ) {
			$hook_name = 'woocommerce_cart_totals_after_order_total';
			if ( 'before_order_total' === $position ) {
				$hook_name = 'woocommerce_cart_totals_before_order_total';
			}
			add_action( $hook_name, array( $this, 'show_saving_amount' ) );
		}
	}

	public function show_saving_amount() {
		global $yaydp_cart;
		if ( ! is_null( $yaydp_cart ) ) {
			$saved_amount = $yaydp_cart->get_cart_origin_total( false ) - $yaydp_cart->get_cart_subtotal( false );
			if ( empty( $saved_amount ) ) {
				return;
			}
			?>
		<tr>
			<th><?php esc_html_e( 'Product discounts', 'yaypricing' ); ?></th>
			<td><?php echo wp_kses_post( \wc_price( $saved_amount ) ); ?></td>
		</tr>
			<?php
		}
	}

	/**
	 * Change cart item price
	 *
	 * @param string $html Current item price html.
	 * @param array  $cart_item Cart item.
	 *
	 * @since 3.4
	 */
	public function change_cart_item_subtotal_html( $html, $cart_item ) {
		if ( ! \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_original_subtotal_price() ) {
			return $html;
		}
		$yaydp_cart_item = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );
		if ( $yaydp_cart_item->can_modify() ) {

			$product            = $yaydp_cart_item->get_product();
			$item_initial_price = $yaydp_cart_item->get_initial_price();
			$item_quantity      = $yaydp_cart_item->get_quantity();
			$subtotal           = \wc_get_price_to_display(
				$product,
				array(
					'price'           => $item_initial_price,
					'qty'             => $item_quantity,
					'display_context' => 'cart',
				)
			);

			ob_start();
			?>
			<del><?php echo \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) ); ?></del>
			<?php
			$extra_html = ob_get_contents();
			ob_end_clean();
			$html = '<div class="price">' . $extra_html . $html . '</div>';
		}
		return apply_filters( 'yaydp_cart_item_price_html', $html, $cart_item );
	}

}
