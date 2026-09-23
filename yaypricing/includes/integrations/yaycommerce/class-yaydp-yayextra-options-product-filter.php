<?php
/**
 * Product filter type: products_with_yayextra_options — products an active
 * YayExtra option set with one of the listed options applies to.
 *
 * @package YayPricing\Integrations
 * @since 3.5.8
 */

namespace YAYDP\Integrations\YayCommerce;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_YayExtra_Options_Product_Filter extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'products_with_yayextra_options';
	}

	public function label() {
		return __( 'YayExtra Option', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::in_list();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'yayextra_options',
		);
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$product_list = $this->products_of_matching_option_sets( $filter );
		$product_id   = $product->get_id();
		$parent_id    = $product->get_parent_id();
		if ( ! empty( $parent_id ) ) {
			return in_array( $product_id, $product_list, true ) || in_array( $parent_id, $product_list, true );
		}
		return in_array( $product_id, $product_list, true );
	}

	/**
	 * Ids of every product an active option set with a listed (in_list) or an
	 * unlisted (not_in_list) option applies to, whether the set targets
	 * products one by one or through YayExtra's own conditions.
	 *
	 * @param array $filter Stored filter.
	 * @return array
	 */
	private function products_of_matching_option_sets( array $filter ) {
		$filter_values      = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $filter );
		$comparation        = $filter['comparation'];
		$option_set_id_list = \YayExtra\Init\Settings::get_instance()->get_option_set_id_list();
		$option_set_list    = \YayExtra\Init\CustomPostType::get_option_set_array( $option_set_id_list );
		$option_set_list    = array_filter(
			$option_set_list,
			function ( $option_set ) {
				return '1' === $option_set['status'];
			}
		);
		$option_set_list    = array_filter(
			$option_set_list,
			function ( $option_set ) use ( $filter_values, $comparation ) {
				if ( empty( $option_set['options'] ) ) {
					return false;
				}
				$has_matching     = false;
				$has_non_matching = false;
				foreach ( $option_set['options'] as $option ) {
					if ( in_array( $option['id'], $filter_values, true ) ) {
						$has_matching = true;
						if ( 'in_list' === $comparation ) {
							return true;
						}
					} else {
						$has_non_matching = true;
						if ( 'not_in_list' === $comparation ) {
							return true;
						}
					}
				}
				return 'in_list' === $comparation ? $has_matching : $has_non_matching;
			}
		);

		$product_list = array();
		foreach ( $option_set_list as $option_set ) {
			$products = $option_set['products'];
			if ( 1 === (int) $products['product_filter_type'] && ! empty( $products['product_filter_one_by_one'] ) ) {
				$product_list = array_merge( $product_list, $products['product_filter_one_by_one'] );
			}
			if ( 2 === (int) $products['product_filter_type'] && ! empty( $products['product_filter_by_conditions']['conditions'] ) ) {
				$matched = \YayExtra\Helper\Utils::get_products_match(
					$products['product_filter_by_conditions']['conditions'],
					$products['product_filter_by_conditions']['match_type'],
					array( 'page_size' => 999999999 )
				);
				if ( ! empty( $matched ) ) {
					$product_list = array_merge( $product_list, array_column( $matched['product_list'], 'id' ) );
				}
			}
		}
		return array_unique( $product_list );
	}
}
