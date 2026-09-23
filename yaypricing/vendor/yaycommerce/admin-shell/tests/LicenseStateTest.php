<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\License\License;
use YayCommerce\AdminShell\License\Contracts\LicenseConfigAdapter;

/**
 * Tests License model option persistence.
 */
class LicenseStateTest extends TestCase {

    private LicenseConfigAdapter $adapter;

    protected function setUp(): void {
        _test_reset_options();
        $this->adapter = new class implements LicenseConfigAdapter {
            public function get_plugin_slug(): string    { return 'yaymail'; }
            public function get_plugin_name(): string    { return 'YayMail Pro'; }
            public function get_menu_title(): string     { return 'YayMail'; }
            public function get_page_title(): string      { return 'YayMail Settings'; }
            public function get_menu_slug(): string   { return ''; }
            public function get_settings_page_callback(): ?callable { return null; }
            public function get_settings_page_position(): ?int { return null; }
            public function get_settings_label(): string { return 'Settings'; }
            public function get_docs_url(): string       { return ''; }
            public function get_pro_url(): string        { return ''; }
            public function get_plugin_version(): string { return '4.4'; }
            public function get_plugin_file(): string    { return '/tmp/yaymail.php'; }
            public function get_plugin_basename(): string { return 'yaymail-pro/yaymail.php'; }
            public function get_item_id(): int           { return 4216; }
            public function get_store_url(): string      { return 'https://yaycommerce.com/'; }
            public function get_store_link(): string     { return 'https://yaycommerce.com/yaymail'; }
            public function get_capability(): string     { return 'manage_options'; }
        };
    }

    public function test_is_not_active_when_no_key_set(): void {
        $license = new License( $this->adapter );
        $this->assertFalse( $license->is_active() );
    }

    public function test_is_active_after_key_set(): void {
        update_option( 'yaymail_license_key', 'MY-LICENSE-KEY' );
        $license = new License( $this->adapter );
        $this->assertTrue( $license->is_active() );
    }

    public function test_update_and_get_license_key(): void {
        $license = new License( $this->adapter );
        $license->update_license_key( 'ABCD-EFGH-IJKL-MNOP' );
        $this->assertSame( 'ABCD-EFGH-IJKL-MNOP', $license->get_license_key() );
        $this->assertSame( 'ABCD-EFGH-IJKL-MNOP', get_option( 'yaymail_license_key' ) );
    }

    public function test_remove_license_key_deletes_option(): void {
        update_option( 'yaymail_license_key', 'SOME-KEY' );
        $license = new License( $this->adapter );
        $license->remove_license_key();
        $this->assertFalse( get_option( 'yaymail_license_key', false ) );
    }

    public function test_update_license_info_stores_array(): void {
        $license = new License( $this->adapter );
        $info    = [ 'expires' => '2026-01-01', 'license_limit' => 5, 'payment_id' => 'pay123', 'success' => true ];
        $license->update_license_info( $info );
        $stored = get_option( 'yaymail_license_info' );
        $this->assertIsArray( $stored );
        // 'success' key must be stripped (per source code)
        $this->assertArrayNotHasKey( 'success', $stored );
        $this->assertSame( '2026-01-01', $stored['expires'] );
    }

    public function test_is_not_expired_for_lifetime_license(): void {
        update_option( 'yaymail_license_key', 'KEY' );
        update_option( 'yaymail_license_info', [ 'expires' => 'lifetime' ] );
        $license = new License( $this->adapter );
        $this->assertFalse( $license->is_expired() );
    }

    public function test_is_expired_for_past_date(): void {
        update_option( 'yaymail_license_key', 'KEY' );
        update_option( 'yaymail_license_info', [ 'expires' => '2020-01-01 00:00:00' ] );
        $license = new License( $this->adapter );
        $this->assertTrue( $license->is_expired() );
    }

    public function test_is_not_expired_for_future_date(): void {
        update_option( 'yaymail_license_key', 'KEY' );
        update_option( 'yaymail_license_info', [ 'expires' => '2099-01-01 00:00:00' ] );
        $license = new License( $this->adapter );
        $this->assertFalse( $license->is_expired() );
    }

    public function test_remove_clears_both_options(): void {
        update_option( 'yaymail_license_key', 'KEY' );
        update_option( 'yaymail_license_info', [ 'expires' => 'lifetime' ] );
        $license = new License( $this->adapter );
        $license->remove_license_key();
        $license->remove_license_info();
        $this->assertFalse( get_option( 'yaymail_license_key', false ) );
        $this->assertFalse( get_option( 'yaymail_license_info', false ) );
    }

    public function test_format_license_key_masks_end(): void {
        update_option( 'yaymail_license_key', '12345678901234567890123456789012' );
        $license   = new License( $this->adapter );
        $formatted = $license->format_license_key();
        $this->assertStringContainsString( '*', $formatted );
        // First group should be visible
        $this->assertStringStartsWith( '12345678', $formatted );
    }
}
