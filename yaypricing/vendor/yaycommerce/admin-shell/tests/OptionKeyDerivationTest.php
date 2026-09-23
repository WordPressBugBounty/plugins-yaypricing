<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\License\License;
use YayCommerce\AdminShell\License\Contracts\LicenseConfigAdapter;

/**
 * Verifies all 9 plugin slugs produce byte-exact wp_options keys.
 * This test is the primary guard against customer license key loss.
 *
 * CRITICAL: These slugs MUST match existing wp_options keys exactly.
 * Three quirk slugs: yay-wholesale-b2b-pro, yay_currency, yay_swatches.
 */
class OptionKeyDerivationTest extends TestCase {

    /**
     * All 9 in-scope plugins with their exact slugs and expected option keys.
     */
    public function providePluginSlugs(): array {
        return [
            'yaymail'              => [ 'yaymail',              'yaymail_license_key',              'yaymail_license_info' ],
            'yayboost'             => [ 'yayboost',             'yayboost_license_key',             'yayboost_license_info' ],
            // QUIRK: hyphens in option key (unusual but valid, must not be normalized)
            'yay-wholesale-b2b-pro' => [ 'yay-wholesale-b2b-pro', 'yay-wholesale-b2b-pro_license_key', 'yay-wholesale-b2b-pro_license_info' ],
            'yaypricing'           => [ 'yaypricing',           'yaypricing_license_key',           'yaypricing_license_info' ],
            'yayextra'             => [ 'yayextra',             'yayextra_license_key',             'yayextra_license_info' ],
            // QUIRK: underscore between yay and currency (NOT yaycurrency)
            'yay_currency'         => [ 'yay_currency',         'yay_currency_license_key',         'yay_currency_license_info' ],
            // QUIRK: underscore between yay and swatches (NOT yayswatches)
            'yay_swatches'         => [ 'yay_swatches',         'yay_swatches_license_key',         'yay_swatches_license_info' ],
            'yaysmtp'              => [ 'yaysmtp',              'yaysmtp_license_key',              'yaysmtp_license_info' ],
            'yayrev'               => [ 'yayrev',               'yayrev_license_key',               'yayrev_license_info' ],
        ];
    }

    /**
     * @dataProvider providePluginSlugs
     */
    public function test_option_key_derivation( string $slug, string $expected_key_option, string $expected_info_option ): void {
        _test_reset_options();

        $adapter = $this->make_adapter( $slug );
        $license = new License( $adapter );

        // Write via update_license_key → must land in {slug}_license_key
        $license->update_license_key( 'TEST-KEY-123' );
        $this->assertSame(
            'TEST-KEY-123',
            get_option( $expected_key_option ),
            "Option key mismatch for slug '{$slug}': expected '{$expected_key_option}'"
        );

        // Write via update_license_info → must land in {slug}_license_info
        $license->update_license_info( [ 'expires' => 'lifetime', 'license_limit' => 1, 'payment_id' => 'abc' ] );
        $stored_info = get_option( $expected_info_option );
        $this->assertIsArray( $stored_info, "Info option for '{$slug}' should be array" );
        $this->assertSame( 'lifetime', $stored_info['expires'] );

        // Remove license key → option must be gone
        $license->remove_license_key();
        $this->assertFalse(
            get_option( $expected_key_option, false ),
            "Option '{$expected_key_option}' should be deleted after remove_license_key()"
        );
    }

    private function make_adapter( string $slug ): LicenseConfigAdapter {
        return new class( $slug ) implements LicenseConfigAdapter {
            private string $slug;
            public function __construct( string $slug ) { $this->slug = $slug; }
            public function get_plugin_slug(): string    { return $this->slug; }
            public function get_plugin_name(): string    { return 'Test Plugin'; }
            public function get_menu_title(): string     { return 'Test'; }
            public function get_page_title(): string      { return 'Test Settings'; }
            public function get_menu_slug(): string   { return ''; }
            public function get_settings_page_callback(): ?callable { return null; }
            public function get_settings_page_position(): ?int { return null; }
            public function get_settings_label(): string { return 'Settings'; }
            public function get_docs_url(): string       { return ''; }
            public function get_pro_url(): string        { return ''; }
            public function get_plugin_version(): string { return '1.0'; }
            public function get_plugin_file(): string    { return '/tmp/test.php'; }
            public function get_plugin_basename(): string { return 'test/test.php'; }
            public function get_item_id(): int           { return 1; }
            public function get_store_url(): string      { return 'https://yaycommerce.com/'; }
            public function get_store_link(): string     { return 'https://yaycommerce.com/'; }
            public function get_capability(): string     { return 'manage_options'; }
        };
    }
}
