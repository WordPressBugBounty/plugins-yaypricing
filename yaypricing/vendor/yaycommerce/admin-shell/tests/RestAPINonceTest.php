<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\License\RestAPI;
use YayCommerce\AdminShell\License\Contracts\LicenseConfigAdapter;

/**
 * Regression test for H2: REST endpoints must return HTTP 403 on nonce failure.
 *
 * Before the fix, nonce mismatch returned WP_REST_Response with status 200
 * and body ['success' => false, 'message' => 'Nonce is invalid'].
 * A 200 response is incorrect — it pollutes telemetry and violates WP REST conventions.
 */
class RestAPINonceTest extends TestCase {

    private LicenseConfigAdapter $adapter;

    protected function setUp(): void {
        _test_reset_options();
        _test_reset_filters();

        $this->adapter = new class implements LicenseConfigAdapter {
            public function get_plugin_slug(): string    { return 'yaytest'; }
            public function get_plugin_name(): string    { return 'YayTest Pro'; }
            public function get_menu_title(): string     { return 'YayTest'; }
            public function get_page_title(): string      { return 'YayTest Settings'; }
            public function get_menu_slug(): string   { return ''; }
            public function get_settings_page_callback(): ?callable { return null; }
            public function get_settings_page_position(): ?int { return null; }
            public function get_settings_label(): string { return 'Settings'; }
            public function get_docs_url(): string       { return ''; }
            public function get_pro_url(): string        { return ''; }
            public function get_plugin_version(): string { return '1.0'; }
            public function get_plugin_file(): string    { return '/tmp/yaytest.php'; }
            public function get_plugin_basename(): string { return 'yaytest/yaytest.php'; }
            public function get_item_id(): int           { return 99; }
            public function get_store_url(): string      { return 'https://yaycommerce.com/'; }
            public function get_store_link(): string     { return 'https://yaycommerce.com/yaytest'; }
            public function get_capability(): string     { return 'manage_options'; }
        };
    }

    private function make_request_with_bad_nonce(): \WP_REST_Request {
        $request = new \WP_REST_Request();
        $request->set_header( 'x_wp_nonce', 'invalid_nonce_value' );
        return $request;
    }

    public function test_activate_returns_403_on_nonce_failure(): void {
        $GLOBALS['_mock_wp_verify_nonce_fail'] = true;

        $api      = new RestAPI( $this->adapter );
        $request  = $this->make_request_with_bad_nonce();
        $response = $api->activate_license( $request );

        $this->assertSame( 403, $response->get_status() );
        $this->assertFalse( $response->data['success'] );

        unset( $GLOBALS['_mock_wp_verify_nonce_fail'] );
    }

    public function test_update_returns_403_on_nonce_failure(): void {
        $GLOBALS['_mock_wp_verify_nonce_fail'] = true;

        $api      = new RestAPI( $this->adapter );
        $request  = $this->make_request_with_bad_nonce();
        $response = $api->update_license( $request );

        $this->assertSame( 403, $response->get_status() );
        $this->assertFalse( $response->data['success'] );

        unset( $GLOBALS['_mock_wp_verify_nonce_fail'] );
    }

    public function test_remove_returns_403_on_nonce_failure(): void {
        $GLOBALS['_mock_wp_verify_nonce_fail'] = true;

        $api      = new RestAPI( $this->adapter );
        $request  = $this->make_request_with_bad_nonce();
        $response = $api->remove_license( $request );

        $this->assertSame( 403, $response->get_status() );
        $this->assertFalse( $response->data['success'] );

        unset( $GLOBALS['_mock_wp_verify_nonce_fail'] );
    }
}

// Namespace-level override for wp_verify_nonce in RestAPI's namespace.
namespace YayCommerce\AdminShell\License;

if ( ! function_exists( 'YayCommerce\AdminShell\License\wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action ) {
        if ( ! empty( $GLOBALS['_mock_wp_verify_nonce_fail'] ) ) {
            return false;
        }
        return true;
    }
}
