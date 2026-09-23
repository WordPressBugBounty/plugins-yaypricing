<?php
/**
 * Plugin adapter for YayPricing Lite - WooCommerce Dynamic Pricing And Discount.
 *
 * Implements only the menu contract: the admin shell registers a plugin in free
 * mode (no License submenu, "Go Pro" action link) when the adapter is not a
 * LicenseConfigAdapter.
 */

defined( 'ABSPATH' ) || exit;

class YaypricingPluginAdapter implements \YayPricingScoped\YayCommerce\AdminShell\Contracts\PluginMenuAdapter {
    public function get_menu_title(): string         { return 'YayPricing'; }
    public function get_page_title(): string         { return 'YayPricing Lite - WooCommerce Dynamic Pricing And Discount'; }
    public function get_menu_slug(): string          { return 'yaypricing'; }
    public function get_settings_page_callback(): ?callable {
        return array('YAYDP\admin\YAYDP_Admin_Settings', 'render');
     }
    public function get_settings_page_position(): ?int { return 0; }
    public function get_capability(): string         { return 'manage_options'; }
    public function get_plugin_basename(): string    { return YAYDP_PLUGIN_BASENAME; }
    public function get_settings_label(): string     { return 'Settings'; }
    public function get_docs_url(): string           { return 'https://docs.yaycommerce.com/yaypricing/features'; }
    public function get_pro_url(): string            { return 'https://yaycommerce.com/yaypricing-woocommerce-dynamic-pricing-and-discounts/?utm_source=yaypricing-lite&utm_medium=gopro'; }
}