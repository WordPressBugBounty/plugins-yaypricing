<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\License\License;
use YayCommerce\AdminShell\License\Contracts\LicenseConfigAdapter;

/**
 * Tests the License model's activate/update/remove state machine.
 * LicenseHandler has too many WordPress hooks to unit-test in isolation;
 * we test the License model (the state machine core) directly.
 */
class LicenseHandlerTest extends TestCase {

    private LicenseConfigAdapter $adapter;

    protected function setUp(): void {
        _test_reset_options();
        _test_reset_filters();
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

    /** Happy path: activate with valid EDD response. */
    public function test_activate_happy_path(): void {
        $GLOBALS['_mock_wp_remote_body'] = json_encode( [
            'success'       => true,
            'expires'       => '2030-06-01',
            'license_limit' => 3,
            'payment_id'    => 'pay_42',
            'customer_name' => 'Alice',
        ] );

        $license = new License( $this->adapter );
        $result  = $license->activate( 'GOOD-LICENSE-KEY' );

        $this->assertTrue( $result['success'] );
        $this->assertTrue( $license->is_active() );
        $this->assertSame( 'GOOD-LICENSE-KEY', get_option( 'yaymail_license_key' ) );

        unset( $GLOBALS['_mock_wp_remote_body'] );
    }

    /** Expired key: activate returns expired error, license not stored. */
    public function test_activate_with_expired_key_does_not_store(): void {
        $GLOBALS['_mock_wp_remote_body'] = json_encode( [
            'success' => false,
            'error'   => 'expired',
        ] );

        $license = new License( $this->adapter );
        $result  = $license->activate( 'EXPIRED-KEY' );

        $this->assertFalse( $result['success'] );
        $this->assertFalse( $license->is_active() );
        $this->assertFalse( get_option( 'yaymail_license_key', false ) );

        unset( $GLOBALS['_mock_wp_remote_body'] );
    }

    /** Network error: check returns is_server_error, license not removed. */
    public function test_update_network_error_preserves_license(): void {
        // Pre-load an active license
        update_option( 'yaymail_license_key', 'EXISTING-KEY' );
        update_option( 'yaymail_license_info', [ 'expires' => '2099-01-01' ] );

        // Simulate WP_Error response from wp_remote_get → triggers is_server_error branch
        $GLOBALS['_mock_wp_remote_is_error'] = true;

        $license = new License( $this->adapter );
        $result  = $license->update();

        // is_server_error branch: license NOT removed
        $this->assertArrayHasKey( 'is_server_error', $result );
        $this->assertTrue( $result['is_server_error'] );
        // Key still present
        $this->assertSame( 'EXISTING-KEY', get_option( 'yaymail_license_key' ) );

        unset( $GLOBALS['_mock_wp_remote_is_error'] );
    }

    /** Remove: clears both options. */
    public function test_remove_clears_license(): void {
        update_option( 'yaymail_license_key', 'KEY' );
        update_option( 'yaymail_license_info', [ 'expires' => 'lifetime' ] );

        $license = new License( $this->adapter );
        $license->remove();

        $this->assertFalse( get_option( 'yaymail_license_key', false ) );
        $this->assertFalse( get_option( 'yaymail_license_info', false ) );
    }
}

// Namespace-level overrides for HTTP in License namespace
namespace YayCommerce\AdminShell\License;

if ( ! function_exists( 'YayCommerce\AdminShell\License\get_site_transient' ) ) {
    function get_site_transient( $key ) { return false; }
    function set_site_transient( $key, $value ) {}
}
