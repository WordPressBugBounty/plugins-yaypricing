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
	 * Order statuses ( without "wc-" ) that give a usage-limit use back when a
	 * counted order moves to them. Empty by default: a counted order keeps its use.
	 *
	 * @return string[]
	 */
	public function get_use_limit_restore_statuses() {
		$statuses = $this->settings['use_limit_restore_statuses'] ?? array();
		if ( ! is_array( $statuses ) ) {
			return array();
		}
		$restorable = array_keys( self::get_use_limit_restorable_statuses() );
		return array_values( array_intersect( array_filter( $statuses, 'is_string' ), $restorable ) );
	}

	/**
	 * Order statuses a merchant can choose to give a use back: every status
	 * except the counted ones and the checkout draft. Custom statuses included.
	 *
	 * @return array Status slug ( without "wc-" ) => label.
	 */
	public static function get_use_limit_restorable_statuses() {
		if ( ! function_exists( 'wc_get_order_statuses' ) ) {
			return array();
		}
		$excluded = array_merge( \YAYDP\Abstracts\YAYDP_Use_Time::COUNTED_STATUSES, array( 'wc-checkout-draft' ) );
		$statuses = array();
		foreach ( \wc_get_order_statuses() as $status => $label ) {
			if ( ! in_array( $status, $excluded, true ) ) {
				$statuses[ preg_replace( '/^wc-/', '', $status ) ] = $label;
			}
		}
		return $statuses;
	}

}
