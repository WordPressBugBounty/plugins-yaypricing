<?php
/**
 * Represents the settings for product pricing in the YAYDP application
 *
 * @package YayPricing\Classes\Settings
 */

namespace YAYDP\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Settings {

	/**
	 * Contains setting data.
	 *
	 * @var array|null
	 */
	protected $settings = null;

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructors
	 */
	protected function __construct() {
		$data           = \YAYDP\API\Models\YAYDP_Setting_Model::get_all();
		$this->settings = isset( $data['product_pricing'] ) ? $data['product_pricing'] : array();
	}

	/**
	 * Retrieves the "how to apply" information of the settings
	 *
	 * @return string
	 */
	public function get_how_to_apply() {
		return isset( $this->settings['how_to_apply'] ) ? $this->settings['how_to_apply'] : 'all';
	}

	/**
	 * Retrieves the "discount_base_on" information of the settings
	 *
	 * @return string
	 */
	public function get_discount_base_on() {
		return isset( $this->settings['discount_base_on'] ) ? $this->settings['discount_base_on'] : 'regular_price';
	}

	/**
	 * Retrieves the "show_regular_price" information of the settings
	 *
	 * @return bool
	 */
	public function show_regular_price() {
		return isset( $this->settings['show_regular_price'] ) ? $this->settings['show_regular_price'] : true;
	}

	/**
	 * Retrieves the "show_sale_tag" information of the settings
	 *
	 * @return bool
	 */
	public function show_sale_tag() {
		return isset( $this->settings['show_sale_tag'] ) ? $this->settings['show_sale_tag'] : true;
	}

	public function show_order_saving_amount() {
		return isset( $this->settings['show_order_saving_amount'] ) ? $this->settings['show_order_saving_amount'] : false;
	}

	public function order_saving_amount_position() {
		return isset( $this->settings['order_saving_amount_position'] ) ? $this->settings['order_saving_amount_position'] : 'after_order_total';
	}

	/**
	 * Retrieves the "show_sale_off_amount" information of the settings
	 *
	 * @return bool
	 */
	public function show_sale_off_amount() {
		return isset( $this->settings['show_sale_off_amount'] ) ? $this->settings['show_sale_off_amount'] : true;
	}

	/**
	 * Retrieves the "pricing_table" information of the settings
	 *
	 * @return array
	 */
	public function get_pricing_table_settings() {
		return isset( $this->settings['pricing_table'] ) ? $this->settings['pricing_table'] : \YAYDP\Constants\YAYDP_Pricing_Table::get_default();
	}

	/**
	 * Retrieves the "countdown_timer" information of the settings
	 *
	 * @return array
	 */
	public function get_countdown_settings() {
		return isset( $this->settings['countdown_timer'] ) ? $this->settings['countdown_timer'] : \YAYDP\Constants\YAYDP_Countdown_Timer::get_default();
	}

	/**
	 * Retrieves the "encouraged_notice" information of the settings
	 *
	 * @return array
	 */
	public function get_encouraged_notice_settings() {
		return isset( $this->settings['encouraged_notice'] ) ? $this->settings['encouraged_notice'] : \YAYDP\Constants\YAYDP_Encouraged_Notice::get_default( 'product_pricing' );
	}

	/**
	 * Retrieves the "pricing_table.position" information of the settings
	 *
	 * @return string
	 */
	public function get_pricing_table_position() {
		return isset( $this->settings['pricing_table']['position'] ) ? $this->settings['pricing_table']['position'] : \YAYDP\Constants\YAYDP_Pricing_Table::get_default()['position'];
	}

	/**
	 * Retrieves the "pricing_table.table_title" information of the settings
	 *
	 * @return string
	 */
	public function get_pricing_table_title() {
		return isset( $this->settings['pricing_table']['table_title'] ) ? $this->settings['pricing_table']['table_title'] : \YAYDP\Constants\YAYDP_Pricing_Table::get_default()['table_title'];
	}

	/**
	 * Retrieves the "pricing_table.quantity_title" information of the settings
	 *
	 * @return string
	 */
	public function get_pricing_table_quantity_title() {
		return isset( $this->settings['pricing_table']['quantity_title'] ) ? $this->settings['pricing_table']['quantity_title'] : \YAYDP\Constants\YAYDP_Pricing_Table::get_default()['quantity_title'];
	}

	/**
	 * Retrieves the "pricing_table.discount_title" information of the settings
	 *
	 * @return string
	 */
	public function get_pricing_table_discount_title() {
		return isset( $this->settings['pricing_table']['discount_title'] ) ? $this->settings['pricing_table']['discount_title'] : \YAYDP\Constants\YAYDP_Pricing_Table::get_default()['discount_title'];
	}

	/**
	 * Retrieves the "pricing_table.price_title" information of the settings
	 *
	 * @return string
	 */
	public function get_pricing_table_price_title() {
		return isset( $this->settings['pricing_table']['price_title'] ) ? $this->settings['pricing_table']['price_title'] : \YAYDP\Constants\YAYDP_Pricing_Table::get_default()['price_title'];
	}

	/**
	 * Retrieves the "pricing_table.border_color" information of the settings
	 *
	 * @return string
	 */
	public function get_pricing_table_border_color() {
		return isset( $this->settings['pricing_table']['border_color'] ) ? $this->settings['pricing_table']['border_color'] : \YAYDP\Constants\YAYDP_Pricing_Table::get_default()['border_color'];
	}

	/**
	 * Retrieves the "pricing_table.border_style" information of the settings
	 *
	 * @return string
	 */
	public function get_pricing_table_border_style() {
		return 'solid';
	}

	/**
	 * Retrieves the "show_discounted_price" information of the settings
	 *
	 * @return bool
	 */
	public function show_discounted_price() {
		return isset( $this->settings['show_discounted_price'] ) ? $this->settings['show_discounted_price'] : false;
	}

	/**
	 * Retrieves the "show_discounted_with_regular_price" information of the settings
	 *
	 * @return bool
	 */
	public function show_discounted_with_regular_price() {
		return isset( $this->settings['show_discounted_with_regular_price'] ) ? $this->settings['show_discounted_with_regular_price'] : true;
	}

	/**
	 * Retrieves the "show_discounted_with_regular_price" information of the settings
	 *
	 * @since 2.1
	 *
	 * @return bool
	 */
	public function show_encouraged_notice_at_bottom() {
		return isset( $this->settings['encouraged_notice']['show_at_bottom'] ) ? $this->settings['encouraged_notice']['show_at_bottom'] : false;
	}

	/**
	 * Retrieves the "disable_when_on_sale" information of the settings
	 *
	 * @since 2.1
	 *
	 * @return bool
	 */
	public function disable_when_on_sale() {
		return isset( $this->settings['disable_when_on_sale'] ) ? $this->settings['disable_when_on_sale'] : false;
	}

	/**
	 * Retrieves the "on_sale_product" rules prop
	 *
	 * @since 2.1
	 *
	 * @return bool
	 */
	public function get_on_sale_products_rules() {
		return isset( $this->settings['on_sale_products']['rules'] ) ? $this->settings['on_sale_products']['rules'] : array();
	}

	/**
	 * Returns whether show product sale as discountable price range
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	public function show_product_sale_as_discountable_price_range() {
		$value = isset( $this->settings['show_product_sale_as'] ) ? $this->settings['show_product_sale_as'] : 'discountable_price_range';
		return 'discountable_price_range' === $value;
	}

	/**
	 * Returns whether show product sale as current discount tier
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	public function show_product_sale_as_current_discount_tier() {
		$value = isset( $this->settings['show_product_sale_as'] ) ? $this->settings['show_product_sale_as'] : 'discountable_price_range';
		return 'current_discount_tier' === $value;
	}

	/**
	 * Returns whether show product sale when product match next rule
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	public function show_product_sale_as_next_discount_tier() {
		$value = isset( $this->settings['show_product_sale_as'] ) ? $this->settings['show_product_sale_as'] : 'discountable_price_range';
		return 'next_discount_tier' === $value;
	}

	/**
	 * Returns whether enable show encouraged notice
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	public function is_enabled_encouraged_notice() {
		$encouraged_notice_settings = $this->get_encouraged_notice_settings();
		return isset( $encouraged_notice_settings['enable'] ) ? $encouraged_notice_settings['enable'] : false;
	}
}
