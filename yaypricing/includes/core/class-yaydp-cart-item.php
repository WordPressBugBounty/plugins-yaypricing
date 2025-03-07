<?php
/**
 * Represents an item in the cart
 *
 * @package YayPricing\Classes
 */

namespace YAYDP\Core;

/**
 * Declare class
 */
class YAYDP_Cart_Item {

	/**
	 * Contains cart key
	 *
	 * @var string|null
	 */
	protected $key = null;

	/**
	 * Contains item quantity
	 *
	 * @var float
	 */
	protected $quantity = 0;

	/**
	 * Contains item price
	 *
	 * @var float
	 */
	protected $price = 0;

	/**
	 * Contains product of this item.
	 *
	 * @var \WC_Product.
	 */
	protected $product = null;

	protected $variation = array();

	/**
	 * Show whether item is extra
	 *
	 * @var bool
	 */
	protected $is_extra = false;

	/**
	 * Contains item extra data.
	 *
	 * @var array
	 */
	protected $extra_data = array();

	protected $bulk_quantity = 0;

	protected $initial_price = 0;

	/**
	 * Initial item regular price
	 * The initial one
	 *
	 * @since 3.4
	 */
	protected $regular_price = 0;

	/**
	 * Initial item sale price
	 * The initial one
	 *
	 * @since 3.4
	 */
	protected $sale_price = 0;

	/**
	 * Initial item price display on store
	 *
	 * @since 3.4
	 */
	protected $store_price = 0;

	/**
	 * Contains item's modifiers.
	 *
	 * @var array
	 */
	protected $modifiers      = array();
	public $adjustment_values = array();
	/**
	 * Constructor
	 *
	 * @param array $cart_item_data Given item data.
	 */
	public function __construct( $cart_item_data ) {
		if ( isset( $cart_item_data['key'] ) ) {
			$this->key = $cart_item_data['key'];
		}
		if ( isset( $cart_item_data['variation'] ) ) {
			$this->variation = $cart_item_data['variation'];
		}
		if ( isset( $cart_item_data['quantity'] ) ) {
			$this->quantity      = $cart_item_data['quantity'];
			$this->bulk_quantity = $cart_item_data['quantity'];
		}
		if ( isset( $cart_item_data['is_extra'] ) ) {
			$this->is_extra = $cart_item_data['is_extra'];
		}
		if ( ! empty( $cart_item_data['extra_data'] ) ) {
			$this->extra_data = \yaydp_unserialize_cart_data( $cart_item_data['extra_data'] );
		}
		if ( ! empty( $cart_item_data['modifiers'] ) ) {
			$this->modifiers = \yaydp_unserialize_cart_data( $cart_item_data['modifiers'] );
		}

		$this->product = $cart_item_data['data'];

		if ( isset( $cart_item_data['custom_price'] ) ) {
			$initial_price = $cart_item_data['custom_price'];
		} else {
			$initial_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_price( $this->product );
		}
		$this->initial_price = apply_filters( 'yaydp_initial_cart_item_price', $initial_price, $cart_item_data );
		$this->price         = $this->initial_price;
		$this->regular_price = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_specific_price( $this->product, 'regular' );
		$this->sale_price    = \YAYDP\Helper\YAYDP_Pricing_Helper::get_product_specific_price( $this->product, 'sale' );
		$this->store_price   = \YAYDP\Helper\YAYDP_Pricing_Helper::get_store_product_price( $this->product );

		if ( isset( $cart_item_data['yaydp_adjustment_values'] ) && is_array( $cart_item_data['yaydp_adjustment_values'] ) ) {
			$this->adjustment_values = $cart_item_data['yaydp_adjustment_values'];
		}
		do_action( 'yaydp_after_initial_cart_item', $cart_item_data, $this );
	}

	/**
	 * Returns item key
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Returns item quantity
	 */
	public function get_quantity() {
		return floatval( $this->quantity );
	}

	/**
	 * Assign item quantity
	 *
	 * @param float $value Quantity input.
	 */
	public function set_quantity( $value ) {
		$this->quantity = floatval( $value );
	}

	/**
	 * Returns item price
	 */
	public function get_price() {
		return floatval( $this->price );
	}

	/**
	 * Assign item price
	 *
	 * @param float $value price value.
	 */
	public function set_price( $value ) {
		$this->price = floatval( $value );
	}

	/**
	 * Determine whether the product is on sale or not.
	 */
	public function is_sale_product() {
		 return $this->product->is_on_sale();
	}

	/**
	 * Returns item product
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * Check whether item is extra
	 */
	public function is_extra() {
		return $this->is_extra;
	}

	/**
	 * Return extra data
	 */
	public function get_extra_data() {
		return $this->extra_data;
	}

	/**
	 * Add modifier to item
	 *
	 * @param array $modifier_data Modifier data.
	 */
	public function add_modifier( $modifier_data ) {
		$this->modifiers[] = new \YAYDP\Core\Single_Modifier\YAYDP_Product_Pricing_Modifier( $modifier_data );
	}

	/**
	 * Returns all modifiers of item
	 */
	public function get_modifiers() {
		return $this->modifiers;
	}

	/**
	 * Check whether item can be modified
	 */
	public function can_modify() {
		return ! empty( $this->get_modifiers() );
	}

	/**
	 * Returns item price html after modifying
	 */
	public function get_modified_price_html() {
		if ( $this->is_extra ) {
			return $this->get_modified_price_html_for_extra_item();
		}
		return $this->get_modified_price_html_for_normal_item();
	}

	/**
	 * Get item tooltips.
	 */
	private function get_available_tooltips() {
		$tooltips = array();
		foreach ( $this->modifiers as $modifier ) {
			$rule    = $modifier->get_rule();
			$tooltip = $rule->get_tooltip( $modifier );
			if ( ! $tooltip->is_enabled() ) {
				continue;
			}
			$tooltips[] = $tooltip;
		}
		return $tooltips;
	}

	/**
	 * Returns item price html after modifying
	 */
	private function get_modified_price_html_for_normal_item() {
		$tooltips                = $this->get_available_tooltips();
		$show_regular_price      = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_regular_price();
		$origin_price            = $this->initial_price;
		$prices_base_on_quantity = $this->get_prices_based_on_quantity();
		$modifiers               = $this->get_modifiers();
		if ( is_array( $modifiers ) && count( $modifiers ) == 1 ) {
			$mod = $modifiers[0];
			if ( \yaydp_is_tiered_pricing( $mod->get_rule() ) ) {
				$prices_base_on_quantity = array();
				$item_quantity           = $this->quantity;
				$origin_price            = $this->initial_price;
				$step_with_price         = array_fill( 1, $item_quantity, $origin_price );

				for ( $i = 1; $i <= $item_quantity; $i++ ) {
					$step_with_price[ $i ] -= isset( $this->adjustment_values[ $i ] ) ? $this->adjustment_values[ $i ] : 0;
				}
				foreach ( $step_with_price as $price ) {
					if ( empty( $prices_base_on_quantity[ strval( $price ) ] ) ) {
						$prices_base_on_quantity[ strval( $price ) ] = 0;
					}
					$prices_base_on_quantity[ strval( $price ) ]++;
				}
			}
		}
		ob_start();
		\wc_get_template(
			'cart-item-price/normal-item.php',
			array(
				'tooltips'                => $tooltips,
				'origin_price'            => $origin_price,
				'prices_base_on_quantity' => $prices_base_on_quantity,
				'show_regular_price'      => $show_regular_price,
				'product'                 => $this->product,
				'item' 					  => $this,
			),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	 * Calculate prices range based on quantity
	 */
	public function get_prices_based_on_quantity() {
		$item_quantity           = $this->quantity;
		$origin_price            = $this->initial_price;
		$prices_base_on_quantity = array();
		$step_with_price         = array_fill( 1, $item_quantity, $origin_price );
		foreach ( $this->modifiers as $modifier ) {
			$modify_quantity        = $modifier->get_modify_quantity();
			$rule                   = $modifier->get_rule();
			$is_buy_x_get_y_or_bogo = \yaydp_is_bogo( $rule ) || \yaydp_is_buy_x_get_y( $rule );
			if ( $modify_quantity > 0 ) {
				for ( $i = 1; $i <= $modify_quantity; $i++ ) {
					if ( empty( $step_with_price[ $i ] ) ) {
						continue;
					}
					$i_quantity             = ( $is_buy_x_get_y_or_bogo || \yaydp_is_tiered_pricing( $rule ) ) ? $i : $modifier->get_modify_quantity();
					$custom_item            = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $this->get_product(), $i_quantity, $step_with_price[ $i ] );
					$discount_per_unit      = $modifier->get_discount_per_unit();
					$step_with_price[ $i ] -= $discount_per_unit;
				}
			}
		}

		foreach ( $step_with_price as $price ) {
			if ( empty( $prices_base_on_quantity[ strval( $price ) ] ) ) {
				$prices_base_on_quantity[ strval( $price ) ] = 0;
			}
			$prices_base_on_quantity[ strval( $price ) ]++;
		}

		return $prices_base_on_quantity;
	}

	/**
	 * Returns item price html after modifying
	 */
	private function get_modified_price_html_for_extra_item() {
		$tooltips = $this->get_available_tooltips();
		ob_start();
		\wc_get_template(
			'cart-item-price/extra-item.php',
			array(
				'tooltips' => $tooltips,
				'product'  => $this->product,
			),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	public function clear_modifiers() {
		$this->modifiers = array();
	}

	public function set_bulk_quantity( $value ) {
		$this->bulk_quantity = $value;
	}

	public function get_bulk_quantity() {
		return $this->bulk_quantity;
	}

	public function get_initial_price() {
		return $this->initial_price;
	}

	public function get_variation() {
		return $this->variation;
	}

	/**
	 * Returns item product initial regular price
	 *
	 * @since 3.4
	 */
	public function get_regular_price() {
		return $this->regular_price;
	}

	/**
	 * Returns item product initial sale price
	 *
	 * @since 3.4
	 */
	public function get_sale_price() {
		return $this->sale_price;
	}

	/**
	 * Returns item product price display on store
	 *
	 * @since 3.4
	 */
	public function get_store_price() {
		return $this->store_price;
	}

}
