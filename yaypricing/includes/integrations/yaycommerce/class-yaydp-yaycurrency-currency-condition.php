<?php
/**
 * Condition type: yaycurrency_currency — the shopper's current YayCurrency currency.
 *
 * @package YayPricing\Integrations
 * @since 3.5.8
 */

namespace YAYDP\Integrations\YayCommerce;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_YayCurrency_Currency_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'yaycurrency_currency';
	}

	public function label() {
		return __( 'YayCurrency current currency', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		$options = array();
		if ( class_exists( '\Yay_Currency\Helpers\Helper' ) ) {
			$names = \Yay_Currency\Helpers\Helper::woo_list_currencies();
			foreach ( \Yay_Currency\Helpers\Helper::get_currencies_post_type() as $currency ) {
				$code      = $currency->post_title;
				$name      = isset( $names[ $code ] ) ? $names[ $code ] : '';
				$options[] = array(
					'value' => $currency->ID,
					'label' => "$name ( $code )",
				);
			}
		}
		return array(
			'kind'    => 'select',
			'options' => $options,
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		if ( ! class_exists( '\Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
			return false;
		}
		$current = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
		$id      = isset( $current['ID'] ) ? $current['ID'] : 0;
		$in_list = in_array( $id, \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
		return YAYDP_Comparators::matches_list( $in_list, $condition );
	}
}
