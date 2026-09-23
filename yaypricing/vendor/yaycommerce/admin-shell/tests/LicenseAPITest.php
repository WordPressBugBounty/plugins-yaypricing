<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\License\LicenseAPI;

/**
 * Tests LicenseAPI EDD response parsing.
 * wp_remote_get is overridden per test via globals.
 */
class LicenseAPITest extends TestCase {

    public function test_get_error_message_missing(): void {
        $this->assertSame( "License doesn't exist", LicenseAPI::get_error_message( 'missing' ) );
    }

    public function test_get_error_message_disabled(): void {
        $this->assertSame( 'License key revoked', LicenseAPI::get_error_message( 'disabled' ) );
    }

    public function test_get_error_message_expired(): void {
        $this->assertSame( 'License has expired', LicenseAPI::get_error_message( 'expired' ) );
    }

    public function test_get_error_message_no_activations_left(): void {
        $this->assertSame( 'No activations left', LicenseAPI::get_error_message( 'no_activations_left' ) );
    }

    public function test_get_error_message_unknown_returns_default(): void {
        $result = LicenseAPI::get_error_message( 'totally_unknown_error_code' );
        $this->assertSame( 'Your license could not be activated.', $result );
    }

    public function test_get_error_message_server_error(): void {
        $this->assertSame(
            'Your license could not be activated because of server error.',
            LicenseAPI::get_error_message( 'server_error' )
        );
    }

    /**
     * Activate: simulate successful EDD response.
     * We override wp_remote_get by redefining the global stub return.
     */
    public function test_activate_license_success(): void {
        $GLOBALS['_mock_wp_remote_body'] = json_encode( [
            'success'       => true,
            'expires'       => '2030-01-01',
            'license_limit' => 5,
            'payment_id'    => 'pay_001',
            'customer_name' => 'Test User',
        ] );

        $result = LicenseAPI::activate_license( 'https://yaycommerce.com/', 4216, 'VALID-KEY' );
        $this->assertTrue( $result['success'] );
        $this->assertSame( '2030-01-01', $result['expires'] );
        $this->assertSame( 'pay_001', $result['payment_id'] );

        unset( $GLOBALS['_mock_wp_remote_body'] );
    }

    public function test_activate_license_failure(): void {
        $GLOBALS['_mock_wp_remote_body'] = json_encode( [
            'success' => false,
            'error'   => 'expired',
        ] );

        $result = LicenseAPI::activate_license( 'https://yaycommerce.com/', 4216, 'EXPIRED-KEY' );
        $this->assertFalse( $result['success'] );
        $this->assertSame( 'expired', $result['message'] );

        unset( $GLOBALS['_mock_wp_remote_body'] );
    }

    public function test_check_license_valid(): void {
        $GLOBALS['_mock_wp_remote_body'] = json_encode( [
            'success'       => true,
            'license'       => 'valid',
            'expires'       => 'lifetime',
            'license_limit' => 0,
            'payment_id'    => 'pay_002',
            'customer_name' => 'Jane Doe',
        ] );

        $result = LicenseAPI::check_license( 'https://yaycommerce.com/', 4216, 'VALID-KEY' );
        $this->assertTrue( $result['success'] );
        $this->assertSame( 'lifetime', $result['expires'] );

        unset( $GLOBALS['_mock_wp_remote_body'] );
    }

    public function test_check_license_invalid(): void {
        $GLOBALS['_mock_wp_remote_body'] = json_encode( [
            'success' => false,
            'license' => 'invalid',
        ] );

        $result = LicenseAPI::check_license( 'https://yaycommerce.com/', 4216, 'BAD-KEY' );
        $this->assertFalse( $result['success'] );

        unset( $GLOBALS['_mock_wp_remote_body'] );
    }

    /**
     * Regression test for H1: activate_license must handle WP_Error gracefully.
     * Before the fix, is_wp_error() was not checked, so wp_remote_retrieve_body()
     * would be called on a WP_Error object, returning '' and hiding the real error.
     */
    public function test_activate_license_wp_error_returns_server_error(): void {
        $GLOBALS['_mock_wp_remote_is_error'] = true;

        $result = LicenseAPI::activate_license( 'https://yaycommerce.com/', 4216, 'ANY-KEY' );

        $this->assertFalse( $result['success'] );
        $this->assertArrayHasKey( 'is_server_error', $result );
        $this->assertTrue( $result['is_server_error'] );

        unset( $GLOBALS['_mock_wp_remote_is_error'] );
    }
}

// Override WP functions in the LicenseAPI namespace so tests work without WordPress.
namespace YayCommerce\AdminShell\License;

function wp_remote_get( $url, $args = [] ) {
    if ( ! empty( $GLOBALS['_mock_wp_remote_is_error'] ) ) {
        return new \stdClass(); // will trigger is_wp_error check
    }
    $body = $GLOBALS['_mock_wp_remote_body'] ?? '{}';
    return [ 'body' => $body, 'response' => [ 'code' => 200 ] ];
}
function wp_remote_retrieve_body( $response ) {
    if ( ! is_array( $response ) ) {
        return '{}';
    }
    return $response['body'] ?? '{}';
}
function is_wp_error( $thing ) {
    if ( ! empty( $GLOBALS['_mock_wp_remote_is_error'] ) ) {
        return true;
    }
    return false;
}
function home_url( $path = '' ) {
    return 'https://example.com' . $path;
}
function rawurlencode( $str ) {
    return \rawurlencode( $str );
}
