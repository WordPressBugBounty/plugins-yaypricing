<?php
/**
 * Class handles the integration of external services with our application
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP;

use YAYDP\Integrations\Meowcrew\YAYDP_Role_Based_Pricing;
use YAYDP\Integrations\Translations\YAYDP_WPML_Integration;
use YAYDP\Integrations\YAYDP_Custom_Taxonomies_Integration;
use YAYDP\Integrations\YAYDP_DIBS_Integration;
use YAYDP\Integrations\YAYDP_WC_Product_Feed_Pro_Integration;
use YAYDP\Integrations\YITH\YAYDP_YITH_Product_Bundles_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Integrations {

	/**
	 * Constructor
	 */
	public function __construct() {
		\YAYDP\Integrations\YayCommerce\YAYDP_YayCurrency_Integration::get_instance();
		\YAYDP\Integrations\WebDevStudios\YAYDP_CPT_UI_Integration::get_instance();
		\YAYDP\Integrations\YITH\YAYDP_YITH_WC_Brands_Integration::get_instance();
		\YAYDP\Integrations\YITH\YAYDP_YITH_Gift_Card_Integration::get_instance();
		\YAYDP\Integrations\YITH\YAYDP_YITH_Product_Add_On_Integration::get_instance();
		\YAYDP\Integrations\YITH\YAYDP_YITH_Request_A_Quote_Integration::get_instance();
		\YAYDP\Integrations\VillaTheme\YAYDP_CURCY_Integration::get_instance();
		\YAYDP\Integrations\Themes\YAYDP_Astra_Theme_Integration::get_instance();
		\YAYDP\Integrations\Aelia\YAYDP_Aelia_Currency_Integration::get_instance();
		\YAYDP\Integrations\WooCommerce\YAYDP_WooCommerce_Subscriptions_Integration::get_instance();
		\YAYDP\Integrations\WooCommerce\YAYDP_WooCommerce_Brands_Integration::get_instance();
		\YAYDP\Integrations\WooCommerce\YAYDP_WooCommerce_Composite_Products_Integration::get_instance();
		\YAYDP\Integrations\Ademti\YAYDP_WC_Google_Product_Feed_Integration::get_instance();
		\YAYDP\Integrations\MarketPress\YAYDP_B2B_Market_Integration::get_instance();
		\YAYDP\Integrations\WooCommerce\YAYDP_WC_Listing_Ads_Integration::get_instance();
		\YAYDP\Integrations\LiteSpeed\YAYDP_LiteSpeed_Cache_Integration::get_instance();
		\YAYDP\Integrations\FlexibleQuantity\YAYDP_Flexible_Quantity_Integration::get_instance();
		\YAYDP\Integrations\WPEngine\YAYDP_ACF_Integration::get_instance();
		\YAYDP\Integrations\Acowebs\YAYDP_Custom_Product_Add_On_Integration::get_instance();
		\YAYDP\Integrations\APF\YAYDP_APF_Intergration::get_instance();
		\YAYDP\Integrations\Automattic\YAYDP_Extra_Product_Options_Integration::get_instance();
		\YAYDP\Integrations\DiviTheme\YAYDP_Divi_Theme_Integration::get_instance();
		\YAYDP\Integrations\B2bking\YAYDP_B2BKing_Integration::get_instance();
		\YAYDP\Integrations\CtxFeed\YAYDP_Ctx_Feed_Integration::get_instance();
		\YAYDP\Integrations\WPClever\YAYDP_WPC_Product_Bundles_Integration::get_instance();
		\YAYDP\Integrations\Iconic\YAYDP_Iconic_Attribute_Swatches_Integration::get_instance();
		\YAYDP\Integrations\CartFlows\YAYDP_Order_Bumps_Integration::get_instance();
		\YAYDP\Integrations\Geiger\YAYDP_GTM_Integration::get_instance();
		\YAYDP\Integrations\ThemeHigh\YAYDP_WooCommerce_Extra_Product_Options_Integration::get_instance();
		\YAYDP\Integrations\RankMathSeo\YAYDP_Rank_Math_Seo_Integration::get_instance();

		YAYDP_WPML_Integration::get_instance();
		YAYDP_Custom_Taxonomies_Integration::get_instance();
		YAYDP_Role_Based_Pricing::get_instance();
		YAYDP_DIBS_Integration::get_instance();
		YAYDP_WC_Product_Feed_Pro_Integration::get_instance();
		YAYDP_YITH_Product_Bundles_Integration::get_instance();
	}
}

new YAYDP_Integrations();
