<?php
/**
 * Represents the general settings
 *
 * @package YayPricing\Classes\Settings
 */

namespace YAYDP\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_General_Settings {

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
		$this->settings = $data['general'] ?? array();
	}

	/**
	 * Retrieves the "sync with coupon individual use only" information of the settings
	 *
	 * @return string
	 */
	public function is_sync_with_coupon_individual_use_only() {
		return $this->settings['sync_with_coupon_individual_use_only'] ?? true;
	}

	/**
	 * Retrieves the "show original price and saved amount" information of the settings
	 *
	 * @return string
	 * @since 3.5.1
	 */
	public function do_show_original_price_and_saved_amount() {
		return $this->settings['show_original_price_and_saved_amount'] ?? false;
	}
}
