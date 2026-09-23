<?php
/**
 * Manage encouragement for product pricing
 *
 * @package YayPricing\Encouragement
 *
 * @since 2.4
 */

namespace YAYDP\Core\Encouragement;

use YAYDP\Condition\Type\YAYDP_Cart_Quantity_Condition;
use YAYDP\Condition\Type\YAYDP_Cart_Subtotal_Price_Condition;
use YAYDP\Condition\Type\YAYDP_Cart_Total_Weight_Condition;
use YAYDP\Condition\Type\YAYDP_Customer_Order_Count_Condition;
use YAYDP\Condition\Type\YAYDP_Logged_Customer_Condition;
use YAYDP\Condition\Type\YAYDP_Shipping_Total_Condition;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Encouragement extends  \YAYDP\Abstracts\YAYDP_Encouragement {

	/**
	 * Contains item
	 */
	protected $item = null;

	/**
	 * Contains rule
	 */
	protected $rule = null;

	/**
	 * Contains missing quantity
	 *
	 * @var float
	 */
	protected $missing_quantity = 0;

	/**
	 * Contains conditions encouragements
	 *
	 * @var array
	 */
	protected $conditions_encouragements = array();

	/**
	 * Constructor
	 *
	 * @param array $data Input.
	 */
	public function __construct( $data ) {
		if ( isset( $data['item'] ) ) {
			$this->item = $data['item'];
		}
		if ( isset( $data['rule'] ) ) {
			$this->rule = $data['rule'];
		}
		if ( isset( $data['missing_quantity'] ) ) {
			$this->missing_quantity = $data['missing_quantity'];
		}
		if ( isset( $data['conditions_encouragements'] ) ) {
			$this->conditions_encouragements = $data['conditions_encouragements'];
		}
	}

	/**
	 * Get encouraged notice content
	 *
	 * @override
	 */
	public function get_content() {
		$raw_content      = $this->get_raw_content();
		$replaced_content = $this->replace_current_item( $raw_content );
		$replaced_content = $this->replace_discount_value( $replaced_content );
		$replaced_content = $this->replace_action( $replaced_content );
		if ( strpos( $replaced_content, '[current_item]' ) !== false || strpos( $replaced_content, '[discount_value]' ) !== false || strpos( $replaced_content, '[action]' ) !== false ) {
			return '';
		}
		return $replaced_content;
	}

	/**
	 * Get encouraged notice raw content ( before replacing variables )
	 *
	 * @override
	 */
	public function get_raw_content() {
		$encouraged_settings = \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->get_encouraged_notice_settings();
		return empty( $encouraged_settings['text'] ) ? '' : $encouraged_settings['text'];
	}

	/**
	 * Replace [current_item] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	public function replace_current_item( $raw_content ) {
		if ( empty( $this->item ) ) {
			return $raw_content;
		}
		$product      = $this->item->get_product();
		if ( empty( $product ) ) {
			return $raw_content;
		}
		$product_link = sprintf( '<a href="%s" target="_blank">%s</a>', $product->get_permalink(), $product->get_name() );
		return str_replace( '[current_item]', $product_link, $raw_content );
	}

	/**
	 * Replace [discount_value] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	public function replace_discount_value( $raw_content ) {
		$product       = $this->item->get_product();
		$item_quantity = $this->item->get_quantity() + $this->missing_quantity;
		$pricing_value = $this->rule->get_pricing_value( $item_quantity );
		$pricing_type  = $this->rule->get_pricing_type( $item_quantity );
		$custom_item   = \YAYDP\Helper\YAYDP_Helper::initialize_custom_cart_item( $product, $item_quantity );
		$pricing_value = $this->rule->get_discount_value_per_item( $custom_item );
		if ( ! \YAYDP\Pricing_Type\YAYDP_Pricing_Type_Registry::is_percentage_adjustment( $pricing_type ) ) {
			$pricing_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $pricing_value );
		}
		if ( empty( $pricing_value ) ) {
			return $raw_content;
		}
		$formatted_discount_value = \yaydp_get_formatted_pricing_value( $pricing_value, $pricing_type );
		return str_replace( '[discount_value]', $formatted_discount_value, $raw_content );
	}

	/**
	 * Replace [action] variable
	 *
	 * @param string $raw_content Raw content.
	 */
	public function replace_action( $raw_content ) {

		$action           = '';
		$missing_quantity = $this->missing_quantity;
		$is_buy_x_get_y   = \yaydp_is_buy_x_get_y( $this->rule );
		if ( ! empty( $missing_quantity ) ) {
			$product      = $this->item->get_product();
			$product_link = sprintf( '<a href="%s" target="_blank">%s</a>', $product->get_permalink(), $product->get_name() );
			if ( ! $is_buy_x_get_y ) {
				// Translators: buy more text.
				$action = sprintf( __( 'add %1$s more item%2$s of %3$s', 'yaypricing' ), $missing_quantity, $missing_quantity > 1 ? 's' : '', $product_link );
				$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), 'product_missing_quantity', $missing_quantity, $product_link, $raw_content );
			} else {
				$action = __( 'add more items', 'yaypricing' );
				$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), 'product_missing_quantity', null, null );
			}
			return str_replace( '[action]', $action, $raw_content );
		}

		foreach ( $this->conditions_encouragements as $condition_encouragement ) {
			$missing_value = $condition_encouragement['missing_value'];

			if ( in_array( $condition_encouragement['type'], array( YAYDP_Cart_Subtotal_Price_Condition::INCOMPLETE_KEY, YAYDP_Shipping_Total_Condition::INCOMPLETE_KEY ), true ) ) {
				$missing_value = \YAYDP\Helper\YAYDP_Pricing_Helper::convert_price( $missing_value );
			}

			if ( $missing_value <= 0 ) {
				continue;
			}

			switch ( $condition_encouragement['type'] ) {
				case YAYDP_Cart_Subtotal_Price_Condition::INCOMPLETE_KEY:
					// Translators: buy more text.
					$action = sprintf( __( 'add %s more', 'yaypricing' ), \wc_price( $missing_value ) );
					$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), $condition_encouragement['type'], $missing_value, null );
					break;
				case YAYDP_Cart_Quantity_Condition::INCOMPLETE_KEY:
					// Translators: buy more text.
					$action = sprintf( __( 'add %1$s more item%2$s', 'yaypricing' ), $missing_value, $missing_value > 1 ? 's' : '' );
					$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), $condition_encouragement['type'], $missing_value, null );
					break;
				case YAYDP_Logged_Customer_Condition::INCOMPLETE_KEY:
					$action = __( 'login', 'yaypricing' );
					$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), $condition_encouragement['type'], null, null );
					break;
				case YAYDP_Customer_Order_Count_Condition::INCOMPLETE_KEY:
					// translators: %s value.
					$action = sprintf( __( 'make more %1$s order%2$s', 'yaypricing' ), $missing_value, $missing_value > 1 ? 's' : '' );
					$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), $condition_encouragement['type'], $missing_value, null );
					break;
				case YAYDP_Shipping_Total_Condition::INCOMPLETE_KEY:
					// translators: %s value.
					$action = sprintf( __( 'take more %1$s shipping fee', 'yaypricing' ), \wc_price( $missing_value ) );
					$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), $condition_encouragement['type'], $missing_value, null );
					break;
				case YAYDP_Cart_Total_Weight_Condition::INCOMPLETE_KEY:
					// translators: %s value.
					$action = sprintf( __( 'add %1$skg more', 'yaypricing' ), $missing_value );
					$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), $condition_encouragement['type'], $missing_value, null );
					break;
				default:
					$action = __( 'add more items', 'yaypricing' );
					$action = apply_filters( 'yaydp_product_pricing_encouraged_notice_action', $action, $this->get_raw_content(), $condition_encouragement['type'], null, null );
					break;
			}
			break;
		}
		if ( empty( $action ) ) {
			return $raw_content;
		}
		return str_replace( '[action]', $action, $raw_content );
	}

}
