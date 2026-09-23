<?php
/**
 * Registers the plugin's own condition types.
 *
 * One list, in the order the admin dropdown shows them, so adding a core type
 * means one class plus one line here (no directory scanning).
 *
 * @package YayPricing\Condition
 * @since 3.5.8
 */

namespace YAYDP\Condition;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Core_Condition_Types {

	/**
	 * Register every core type, in dropdown order.
	 *
	 * @param YAYDP_Condition_Registry $registry Registry to populate.
	 */
	public static function register_all( YAYDP_Condition_Registry $registry ) {
		$types = array(
			// Cart.
			new Type\YAYDP_Cart_Subtotal_Price_Condition(),
			new Type\YAYDP_Cart_Quantity_Condition(),
			new Type\YAYDP_Cart_Total_Weight_Condition(),
			// Cart items.
			new Type\YAYDP_Cart_Item_Condition(),
			new Type\YAYDP_Product_Variation_Condition(),
			new Type\YAYDP_Product_Attribute_Taxonomies_Condition(),
			new Type\YAYDP_Cart_Item_Category_Condition(),
			new Type\YAYDP_Cart_Item_Tag_Condition(),
			new Type\YAYDP_Cart_Item_Regular_Price_Condition(),
			new Type\YAYDP_Shipping_Class_Condition(),
			// Customer.
			new Type\YAYDP_Logged_Customer_Condition(),
			new Type\YAYDP_Customer_Role_Condition(),
			new Type\YAYDP_Specific_Customer_Condition(),
			new Type\YAYDP_Account_Create_Time_Condition(),
			new Type\YAYDP_User_Creation_Date_Condition(),
			// Purchase history.
			new Type\YAYDP_Customer_Order_Count_Condition(),
			new Type\YAYDP_Billing_Email_Order_Count_Condition(),
			new Type\YAYDP_Customer_Order_Count_From_Last_Discount_Condition(),
			new Type\YAYDP_Orders_Purchased_Date_Condition(),
			new Type\YAYDP_Order_History_Product_Condition(),
			new Type\YAYDP_Order_History_Category_Condition(),
			new Type\YAYDP_Bought_Products_Condition(),
			new Type\YAYDP_Previous_Order_In_Condition(),
			// Others.
			new Type\YAYDP_Shipping_Total_Condition(),
			new Type\YAYDP_Shipping_Region_Condition(),
			new Type\YAYDP_Billing_Region_Condition(),
			new Type\YAYDP_Payment_Method_Condition(),
			new Type\YAYDP_Shipping_Method_Condition(),
			new Type\YAYDP_Applied_Coupons_Condition(),
		);
		foreach ( $types as $type ) {
			$registry->register( $type );
		}
	}
}
