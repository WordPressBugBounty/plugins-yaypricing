<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\Registry\LegacyBridge;
use YayCommerce\AdminShell\Registry\LicenseRegistry;
use YayCommerce\AdminShell\Registry\PluginLicenseInfo;

/**
 * Tests LegacyBridge: reads yaycommerce_licensing_plugins filter and
 * converts each legacy entry to a PluginLicenseInfo stub with is_legacy=true.
 */
class LegacyBridgeTest extends TestCase {

    protected function setUp(): void {
        _test_reset_options();
        _test_reset_filters();
    }

    public function test_loads_legacy_plugin_as_stub(): void {
        // Register a legacy plugin via the filter
        add_filter( 'yaycommerce_licensing_plugins', function( $plugins ) {
            return array_merge( $plugins, [ [
                'slug'     => 'yayboost',
                'name'     => 'YayBoost Sales Booster',
                'basename' => 'yayboost-sales-booster-for-woocommerce/yayboost.php',
                'file'     => '/path/to/yayboost.php',
                'url'      => 'https://yaycommerce.com/yayboost',
                'item_id'  => 66679,
                'dir_path' => '/path/to/',
            ] ] );
        }, 100 );

        // Seed wp_options with existing license data
        update_option( 'yayboost_license_key', 'BOOST-KEY-XYZ' );
        update_option( 'yayboost_license_info', [ 'expires' => '2099-01-01', 'site_count' => 1, 'license_limit' => 2 ] );

        $registry = new LicenseRegistry();
        $bridge   = new LegacyBridge( $registry );
        $bridge->load_legacy_plugins();

        $info = $registry->get( 'yayboost' );

        $this->assertInstanceOf( PluginLicenseInfo::class, $info );
        $this->assertSame( 'yayboost', $info->slug );
        $this->assertSame( 'YayBoost Sales Booster', $info->name );
        $this->assertTrue( $info->is_legacy );
        $this->assertSame( 'valid', $info->status );
        $this->assertSame( 'BOOST-KEY-XYZ', $info->license_key );
        $this->assertSame( 1, $info->activations_used );
        $this->assertSame( 2, $info->activations_limit );
    }

    public function test_skips_already_registered_slug(): void {
        // Pre-register a migrated plugin
        $registry = new LicenseRegistry();
        $existing = new PluginLicenseInfo();
        $existing->slug             = 'yayboost';
        $existing->name             = 'Migrated YayBoost';
        $existing->version          = '2.0';
        $existing->basename         = 'yayboost-pro/yayboost.php';
        $existing->item_id          = 66679;
        $existing->store_link       = '';
        $existing->license_key      = '';
        $existing->status           = 'valid';
        $existing->expires_at       = null;
        $existing->activations_used  = 0;
        $existing->activations_limit = 0;
        $existing->is_legacy        = false;
        $existing->raw_info         = [];
        $registry->register( $existing );

        add_filter( 'yaycommerce_licensing_plugins', function( $plugins ) {
            return array_merge( $plugins, [ [ 'slug' => 'yayboost', 'name' => 'Legacy YayBoost', 'basename' => '', 'file' => '', 'url' => '', 'item_id' => 66679, 'dir_path' => '' ] ] );
        }, 100 );

        $bridge = new LegacyBridge( $registry );
        $bridge->load_legacy_plugins();

        // Should still be the migrated version (not overwritten by legacy)
        $info = $registry->get( 'yayboost' );
        $this->assertSame( 'Migrated YayBoost', $info->name );
        $this->assertFalse( $info->is_legacy );
    }

    public function test_inactive_status_when_no_license_key(): void {
        add_filter( 'yaycommerce_licensing_plugins', function( $plugins ) {
            return array_merge( $plugins, [ [
                'slug'     => 'yaysmtp',
                'name'     => 'YaySMTP Pro',
                'basename' => 'yaysmtp-pro/yay-smtp.php',
                'file'     => '/path/to/yay-smtp.php',
                'url'      => 'https://yaycommerce.com/yaysmtp',
                'item_id'  => 5269,
                'dir_path' => '/path/to/',
            ] ] );
        }, 100 );

        // No license key in options
        $registry = new LicenseRegistry();
        $bridge   = new LegacyBridge( $registry );
        $bridge->load_legacy_plugins();

        $info = $registry->get( 'yaysmtp' );
        $this->assertSame( 'inactive', $info->status );
        $this->assertSame( '', $info->license_key );
    }

    public function test_expired_status_for_past_expiry(): void {
        add_filter( 'yaycommerce_licensing_plugins', function( $plugins ) {
            return array_merge( $plugins, [ [
                'slug'     => 'yayrev',
                'name'     => 'YayReviews',
                'basename' => 'yay-customer-reviews-woocommerce/yayrev.php',
                'file'     => '/path/to/yayrev.php',
                'url'      => 'https://yaycommerce.com/',
                'item_id'  => 64111,
                'dir_path' => '/path/to/',
            ] ] );
        }, 100 );

        update_option( 'yayrev_license_key', 'REV-KEY' );
        update_option( 'yayrev_license_info', [ 'expires' => '2020-01-01', 'site_count' => 0, 'license_limit' => 1 ] );

        $registry = new LicenseRegistry();
        $bridge   = new LegacyBridge( $registry );
        $bridge->load_legacy_plugins();

        $info = $registry->get( 'yayrev' );
        $this->assertSame( 'expired', $info->status );
    }
}
