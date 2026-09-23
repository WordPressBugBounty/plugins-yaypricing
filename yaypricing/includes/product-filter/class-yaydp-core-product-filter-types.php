<?php
/**
 * Registers the plugin's own product filter types.
 *
 * One list, in the order the admin dropdown shows them, so adding a core
 * filter means one class plus one line here.
 *
 * @package YayPricing\Product_Filter
 * @since 3.5.8
 */

namespace YAYDP\Product_Filter;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Core_Product_Filter_Types {

	/**
	 * Register every core type, in dropdown order.
	 *
	 * @param YAYDP_Product_Filter_Registry $registry Registry to populate.
	 */
	public static function register_all( YAYDP_Product_Filter_Registry $registry ) {
		$types = array(
			new Type\YAYDP_Filter_Product(),
			new Type\YAYDP_Filter_Product_Variation(),
			new Type\YAYDP_Filter_Product_Category(),
			new Type\YAYDP_Filter_Product_Attribute(),
			new Type\YAYDP_Filter_Product_Specific_Attributes(),
			new Type\YAYDP_Filter_Product_Attribute_Taxonomies(),
			new Type\YAYDP_Filter_Product_Tag(),
			new Type\YAYDP_Filter_Product_Price(),
			new Type\YAYDP_Filter_Product_In_Stock(),
			new Type\YAYDP_Filter_Products_On_Sale_Wc(),
			new Type\YAYDP_Filter_All_Product(),
			new Type\YAYDP_Filter_Shipping_Class(),
			new Type\YAYDP_Filter_Cart_Item_Price_Criterion(),
			new Type\YAYDP_Filter_Sub_Filter_Product_Price_Criterion(),
		);
		foreach ( $types as $type ) {
			$registry->register( $type );
		}
	}
}
