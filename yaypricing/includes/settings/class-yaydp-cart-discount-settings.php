<?php
/**
 * Represents the settings for cart discount in the YAYDP application
 *
 * @package YayPricing\Classes\Settings
 */

namespace YAYDP\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Cart_Discount_Settings {

	/**
	 * Contains setting data.
	 *
	 * @var array|null
	 */
	protected $settings = null;

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		$data           = \YAYDP\API\Models\YAYDP_Setting_Model::get_all();
		$this->settings = $data['cart_discount'];
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
	 * Retrieves the "is_combined" information of the settings
	 *
	 * @return bool
	 */
	public function is_combined() {
		return isset( $this->settings['is_combined'] ) ? $this->settings['is_combined'] : false;
	}

	/**
	 * Retrieves the "use_id_as_code" information of the settings
	 *
	 * @return bool
	 */
	public function use_id_as_code() {
		return isset( $this->settings['use_id_as_code'] ) ? $this->settings['use_id_as_code'] : false;
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
		return isset( $this->settings['encouraged_notice'] ) ? $this->settings['encouraged_notice'] : \YAYDP\Constants\YAYDP_Encouraged_Notice::get_default( 'cart_discount' );
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
