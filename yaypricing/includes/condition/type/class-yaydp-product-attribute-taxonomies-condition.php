<?php
/**
 * Condition type: product_attribute_taxonomies
 *
 * @package YayPricing\Condition\Type
 * @since 3.5.8
 */

namespace YAYDP\Condition\Type;

use YAYDP\Condition\YAYDP_Cart_Item_Matcher;
use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Condition\YAYDP_Condition_Context;
use YAYDP\Condition\YAYDP_Condition_Registry;

defined( 'ABSPATH' ) || exit;

class YAYDP_Product_Attribute_Taxonomies_Condition extends \YAYDP\Abstracts\YAYDP_Condition_Type {

	public function slug() {
		return 'product_attribute_taxonomies';
	}

	public function label() {
		return __( 'Cart item - attribute taxonomies', 'yaypricing' );
	}

	public function group() {
		return YAYDP_Condition_Registry::GROUP_CART_ITEMS;
	}

	public function families() {
		return array( YAYDP_Condition_Registry::FAMILY_PRODUCT_PRICING, YAYDP_Condition_Registry::FAMILY_CART_DISCOUNT, YAYDP_Condition_Registry::FAMILY_CHECKOUT_FEE );
	}

	public function comparators() {
		return YAYDP_Comparators::contain();
	}

	public function editor() {
		return array(
			'kind'   => 'source',
			'source' => 'product_attribute_taxonomies',
		);
	}

	public function check( array $condition, YAYDP_Condition_Context $ctx ) {
		$ids       = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		$intersect = array_intersect( YAYDP_Cart_Item_Matcher::attribute_taxonomies( $ctx->cart_items() ), $ids );
		return YAYDP_Comparators::matches_contain( $intersect, count( $ids ), $condition );
	}
}
