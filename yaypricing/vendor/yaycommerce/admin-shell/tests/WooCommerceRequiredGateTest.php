<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\License\Contracts\LicenseConfigAdapter;
use YayCommerce\AdminShell\Menu\PluginSubmenu;
use YayCommerce\AdminShell\Tests\Fixtures\LegacyAdapter;

/**
 * Tests the WooCommerce dependency gate in PluginSubmenu::register().
 *
 * When an adapter opts in via needs_woocommerce_screen(), the registered page
 * callback must render the shared "WooCommerce required" screen — except when a
 * pro plugin is unlicensed, where the license redirect keeps precedence.
 */
class WooCommerceRequiredGateTest extends TestCase {

    protected function setUp(): void {
        _test_reset_menus();
        _test_reset_options();
    }

    /** The render callback captured by the add_submenu_page() test mock. */
    private function captured_callback( string $slug ) {
        return $GLOBALS['_wp_submenu_callbacks'][ $slug ] ?? null;
    }

    /** Invoke a captured callback and return its echoed output. */
    private function render_output( callable $callback ): string {
        ob_start();
        $callback();
        return (string) ob_get_clean();
    }

    public function test_opt_in_lite_adapter_renders_woocommerce_screen(): void {
        $adapter = new class extends LegacyAdapter {
            public function needs_woocommerce_screen(): bool { return true; }
        };

        ( new PluginSubmenu( $adapter ) )->register();

        $callback = $this->captured_callback( 'test-settings' );
        $this->assertInstanceOf( \Closure::class, $callback );

        $html = $this->render_output( $callback );
        $this->assertStringContainsString( 'yaycommerce-requirement-card', $html );
        $this->assertStringContainsString( 'plugin-install.php?s=woocommerce', $html );
        $this->assertStringContainsString( 'WooCommerce is required', $html );
    }

    public function test_copy_override_is_applied(): void {
        $adapter = new class extends LegacyAdapter {
            public function needs_woocommerce_screen(): bool { return true; }
            public function get_woocommerce_screen_copy(): array {
                return [ 'title' => 'Reviews needs WooCommerce' ];
            }
        };

        ( new PluginSubmenu( $adapter ) )->register();

        $html = $this->render_output( $this->captured_callback( 'test-settings' ) );
        $this->assertStringContainsString( 'Reviews needs WooCommerce', $html );
        // Non-overridden fields fall back to defaults.
        $this->assertStringContainsString( 'Install WooCommerce', $html );
    }

    public function test_opt_out_adapter_keeps_default_callback(): void {
        $adapter = new class extends LegacyAdapter {
            public function needs_woocommerce_screen(): bool { return false; }
        };

        ( new PluginSubmenu( $adapter ) )->register();

        // Base callback is null → WP fallback string, not the gate closure.
        $this->assertSame( '__return_false', $this->captured_callback( 'test-settings' ) );
    }

    public function test_legacy_adapter_without_method_is_unaffected(): void {
        ( new PluginSubmenu( new LegacyAdapter() ) )->register();

        $this->assertSame( '__return_false', $this->captured_callback( 'test-settings' ) );
    }

    public function test_license_redirect_takes_precedence_over_woo_screen(): void {
        // Pro adapter, no license key set → unlicensed → redirect to Licenses.
        $adapter = new class implements LicenseConfigAdapter {
            public function get_plugin_slug(): string    { return 'yayrev'; }
            public function get_plugin_name(): string    { return 'YayReviews Pro'; }
            public function get_menu_title(): string     { return 'YayReviews'; }
            public function get_page_title(): string     { return 'YayReviews Settings'; }
            public function get_menu_slug(): string      { return 'pro-settings'; }
            public function get_settings_page_callback(): ?callable { return null; }
            public function get_settings_page_position(): ?int { return null; }
            public function get_settings_label(): string { return 'Settings'; }
            public function get_docs_url(): string       { return ''; }
            public function get_pro_url(): string        { return ''; }
            public function get_plugin_version(): string { return '1.3.0'; }
            public function get_plugin_file(): string    { return '/tmp/yayrev.php'; }
            public function get_plugin_basename(): string { return 'yay-reviews/yay-reviews.php'; }
            public function get_item_id(): int           { return 64111; }
            public function get_store_url(): string      { return 'https://yaycommerce.com/'; }
            public function get_store_link(): string     { return 'https://yaycommerce.com/yayreviews'; }
            public function get_capability(): string     { return 'manage_options'; }
            public function needs_woocommerce_screen(): bool { return true; }
        };

        ( new PluginSubmenu( $adapter ) )->register();

        // Woo gate must NOT install its closure; the license redirect owns this page.
        $this->assertSame( '__return_false', $this->captured_callback( 'pro-settings' ) );
    }
}
