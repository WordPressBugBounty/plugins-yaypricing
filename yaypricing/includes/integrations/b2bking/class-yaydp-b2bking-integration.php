<?php
/**
 * Handles the compatibility with B2BKing plugin
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\B2bking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_B2BKing_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Flag to track if we modified any order items
	 *
	 * @var bool
	 */
	private $modified_order_items = false;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'B2bking' ) ) {
			return;
		}

		add_filter( 'yaydp_extra_conditions', array( $this, 'user_group_condition' ) );
		add_filter( 'yaydp_extra_conditions', array( $this, 'user_role_condition' ) );
		add_filter( 'yaydp_check_b2bking_user_group_condition', array( $this, 'check_b2bking_user_group_condition' ), 10, 2 );
		add_filter( 'yaydp_check_b2bking_custom_role_condition', array( $this, 'check_b2bking_user_condition' ), 10, 2 );
		if ( is_user_logged_in() && get_user_meta( get_current_user_id(), 'b2bking_b2buser', true ) === 'yes' ) {
			add_filter( 'yaydp_other_source_product_base_price', array( $this, 'get_product_base_price' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'modify_cart_item_subtotal' ), 1, 3 );
			add_filter( 'woocommerce_cart_subtotal', array( $this, 'modify_cart_subtotal' ), 1, 2 );
			add_filter( 'woocommerce_cart_total', array( $this, 'modify_cart_total' ), 1, 1 );

			add_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'modify_mini_cart_item_quantity' ), 10, 3 );
			add_action( 'woocommerce_widget_shopping_cart_total', array( $this, 'modify_mini_cart_total' ), 5 );

			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'preserve_discounted_price_in_order_item' ), 5, 4 );
			add_action( 'woocommerce_checkout_create_order', array( $this, 'recalculate_order_totals' ), 20, 2 );
		}
	}

	public function user_group_condition( $conditions ) {

		if ( ! class_exists( 'B2bking' ) ) {
			return $conditions;
		}

		$groups = get_posts(
			array(
				'post_type'   => 'b2bking_group',
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);

		$conditions[] = array(
			'value'        => 'b2bking_user_group',
			'label'        => 'B2BKing User Group',
			'comparations' => array(
				array(
					'value' => 'in_list',
					'label' => 'In list',
				),
				array(
					'value' => 'not_in_list',
					'label' => 'Not in list',
				),
			),
			'values'       => array_map(
				function( $group ) {
					return array(
						'value' => $group->ID,
						'label' => $group->post_title,
					);
				},
				$groups
			),
		);
		return $conditions;
	}

	public function user_role_condition( $conditions ) {

		if ( ! class_exists( 'B2bking' ) ) {
			return $conditions;
		}

		$custom_roles = get_posts(
			array(
				'post_type'   => 'b2bking_custom_role',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'   => 'b2bking_custom_role_status',
						'value' => 1,
					),
				),
			)
		);

		$conditions[] = array(
			'value'        => 'b2bking_custom_role',
			'label'        => 'B2BKing Custom Role',
			'comparations' => array(
				array(
					'value' => 'in_list',
					'label' => 'In list',
				),
				array(
					'value' => 'not_in_list',
					'label' => 'Not in list',
				),
			),
			'values'       => array_map(
				function( $role ) {
					return array(
						'value' => 'role_' . $role->ID,
						'label' => get_the_title( apply_filters( 'wpml_object_id', $role->ID, 'post', true ) ),
					);
				},
				$custom_roles
			),
		);
		return $conditions;
	}

	public function check_b2bking_user_condition( $result, $condition ) {

		$current_user = \wp_get_current_user();
		if ( ! $current_user ) {
			return false;
		}
		$user_role = get_user_meta( $current_user->ID, 'b2bking_registration_role', true );

		$condition_values   = array_map(
			function ( $item ) {
				return $item['value'];
			},
			$condition['value']
		);
		$intersection_roles = array_intersect( $condition_values, array( $user_role ) );
		return 'in_list' === $condition['comparation'] ? ! empty( $intersection_roles ) : empty( $intersection_roles );

	}

	public function check_b2bking_user_group_condition( $result, $condition ) {
		if ( ! function_exists( 'b2bking' ) ) {
			return false;
		}

		$group_id = \b2bking()->get_user_group();

		$condition_values    = array_map(
			function ( $item ) {
				return $item['value'];
			},
			$condition['value']
		);
		$intersection_groups = array_intersect( $condition_values, array( $group_id ) );
		return 'in_list' === $condition['comparation'] ? ! empty( $intersection_groups ) : empty( $intersection_groups );

	}

	public function get_product_base_price( $price, $product ) {
		if ( ! function_exists( 'b2bking' ) ) {
			return $price;
		}

		if ( ! apply_filters( 'yaydp_discount_based_on_other_source', false ) ) {
			return $price;
		}

		$group_id                  = \b2bking()->get_user_group();
		$product_regular_price     = get_post_meta( $product->get_id(), 'b2bking_regular_product_price_group_' . $group_id, true );
		$product_sale_price        = get_post_meta( $product->get_id(), 'b2bking_sale_product_price_group_' . $group_id, true );
		$settings                  = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance();
		$is_based_on_regular_price = 'regular_price' === $settings->get_discount_base_on();
		$is_product_on_sale        = ! empty( $product_sale_price );
		$sale_price                = $is_product_on_sale ? $product_sale_price : $product_regular_price;
		$product_price             = $is_based_on_regular_price ? $product_regular_price : $sale_price;
		
		if ( empty( $product_price ) || floatval( $product_price ) <= 0 ) {
			return $price;
		}
		
		return floatval( $product_price );
	}

	public function modify_cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
		if ( ! isset( $cart_item['yaydp_custom_data']['price'] ) ) {
			return $subtotal;
		}

		$yaydp_cart_item    = new \YAYDP\Core\YAYDP_Cart_Item( $cart_item );
		$item_price         = $cart_item['yaydp_custom_data']['price'];
		$item_initial_price = $yaydp_cart_item->get_store_price();
		$item_quantity      = $cart_item['quantity'];

		$product  = $yaydp_cart_item->get_product();
		$subtotal = \wc_get_price_to_display(
			$product,
			array(
				'price'           => $item_price,
				'qty'             => $item_quantity,
				'display_context' => 'cart',
			)
		);
		$subtotal = \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $subtotal ) );
		$html     = $subtotal;

		if ( \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_original_subtotal_price() && $yaydp_cart_item->can_modify() ) {
			$original_subtotal = \wc_get_price_to_display(
				$product,
				array(
					'price'           => $item_initial_price,
					'qty'             => $item_quantity,
					'display_context' => 'cart',
				)
			);

			ob_start();
			?>
			<del><?php echo \wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $original_subtotal ) ); ?></del>
			<?php
			$strikethrough_html = ob_get_clean();
			$html               = '<div class="price">' . $html . '</div>';
		}

		return apply_filters( 'yaydp_cart_item_subtotal_html', $html, $cart_item, $cart_item_key );
	}

	public function modify_cart_subtotal( $subtotal, $compound = false ) {
		global $yaydp_cart;

		if ( ! $yaydp_cart ) {
			return $subtotal;
		}

		$modified_subtotal = 0;
		foreach ( \WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['yaydp_custom_data']['price'] ) ) {
				$item_price         = $cart_item['yaydp_custom_data']['price'];
				$item_quantity      = $cart_item['quantity'];
				$modified_subtotal += $item_price * $item_quantity;
			} else {
				$modified_subtotal += $cart_item['line_subtotal'];
			}
		}

		if ( $compound ) {
			$cart_subtotal = wc_price( $modified_subtotal + \WC()->cart->get_shipping_total() + \WC()->cart->get_taxes_total( false, false ) );
		} elseif ( \WC()->cart->display_prices_including_tax() ) {
			$cart_subtotal = wc_price( $modified_subtotal );
			if ( \WC()->cart->get_subtotal_tax() > 0 && ! wc_prices_include_tax() ) {
				$cart_subtotal .= ' <small class="tax_label">' . \WC()->countries->inc_tax_or_vat() . '</small>';
			}
		} else {
			$cart_subtotal = wc_price( $modified_subtotal );
			if ( \WC()->cart->get_subtotal_tax() > 0 && wc_prices_include_tax() ) {
				$cart_subtotal .= ' <small class="tax_label">' . \WC()->countries->ex_tax_or_vat() . '</small>';
			}
		}

		return apply_filters( 'yaydp_cart_subtotal_html', $cart_subtotal, $compound );
	}

	public function modify_cart_total( $total ) {
		global $yaydp_cart;

		if ( ! $yaydp_cart ) {
			return $total;
		}

		$modified_total = 0;
		foreach ( \WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['yaydp_custom_data']['price'] ) ) {
				$item_price      = $cart_item['yaydp_custom_data']['price'];
				$item_quantity   = $cart_item['quantity'];
				$modified_total += $item_price * $item_quantity;
			} else {
				$modified_total += $cart_item['line_total'];
			}
		}

		$shipping_total = \WC()->cart->get_shipping_total();
		$tax_total      = \WC()->cart->get_total_tax();
		$fee_total      = \WC()->cart->get_fee_total();

		$final_total = $modified_total + $shipping_total + $tax_total + $fee_total;

		return apply_filters( 'yaydp_cart_total_html', wc_price( $final_total ), $final_total );
	}

	/**
	 * Modify mini cart item quantity display
	 */
	public function modify_mini_cart_item_quantity( $quantity_html, $cart_item, $cart_item_key ) {
		if ( ! isset( $cart_item['yaydp_custom_data']['price'] ) ) {
			return $quantity_html;
		}

		$item_price    = $cart_item['yaydp_custom_data']['price'];
		$item_quantity = $cart_item['quantity'];

		$formatted_price = wc_price( \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $item_price ) );

		$quantity_html = sprintf( '<span class="quantity">%s &times; %s</span>', $item_quantity, $formatted_price );

		return apply_filters( 'yaydp_mini_cart_item_quantity_html', $quantity_html, $cart_item, $cart_item_key );
	}

	/**
	 	* Modify mini cart total
	 */
	public function modify_mini_cart_total() {
		global $yaydp_cart;

		if ( ! $yaydp_cart ) {
			return;
		}

		$modified_subtotal = 0;
		foreach ( \WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['yaydp_custom_data']['price'] ) ) {
				$item_price         = $cart_item['yaydp_custom_data']['price'];
				$item_quantity      = $cart_item['quantity'];
				$modified_subtotal += $item_price * $item_quantity;
			} else {
				$modified_subtotal += $cart_item['line_subtotal'];
			}
		}

		echo '<strong class="text-v-dark text-uppercase">' . esc_html__( 'Total', 'woocommerce' ) . ':</strong> ' . wc_price( $modified_subtotal );
	}

	/**
	 * Preserve discounted price in order line item
	 *
	 * @param \WC_Order_Item_Product $item Order line item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values Cart item values.
	 * @param \WC_Order              $order Order object.
	 */
	public function preserve_discounted_price_in_order_item( $item, $cart_item_key, $values, $order ) {
		if ( ! isset( $values['yaydp_custom_data'] ) || ! is_array( $values['yaydp_custom_data'] ) ) {
			return;
		}

		$yaydp_data = $values['yaydp_custom_data'];
		$quantity   = floatval( $values['quantity'] );
		$product    = $item->get_product();

		if ( ! $product ) {
			return;
		}

		if ( ! isset( $yaydp_data['price'] ) ) {
			return;
		}

		$discounted_price = floatval( $yaydp_data['price'] );

		if ( $discounted_price === null || $discounted_price < 0 ) {
			return;
		}

		if ( wc_prices_include_tax() ) {
			$discounted_price = wc_get_price_excluding_tax( $product, array( 'price' => $discounted_price ) );
		}

		$subtotal = $discounted_price * $quantity;
		$total    = $discounted_price * $quantity;

		$item->set_subtotal( $subtotal );
		$item->set_total( $total );

		$this->modified_order_items = true;
	}

	/**
	 * Recalculate order totals after line items have been updated with discounted prices
	 *
	 * @param \WC_Order $order Order object.
	 * @param array    $data Order data.
	 */
	public function recalculate_order_totals( $order, $data ) {
		if ( ! $this->modified_order_items ) {
			return;
		}

		$order->calculate_totals();

		$this->modified_order_items = false;
	}

}