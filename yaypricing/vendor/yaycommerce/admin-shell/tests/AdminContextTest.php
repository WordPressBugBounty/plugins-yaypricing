<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\Support\AdminContext;
use YayCommerce\AdminShell\Tests\Fixtures\LegacyAdapter;
use YayCommerce\AdminShell\Tests\Fixtures\OptInAdapter;

/**
 * Unit tests for AdminContext — context resolution, capability elevation,
 * per-plugin opt-in defaults, and dual-hook binding.
 */
class AdminContextTest extends TestCase {

    protected function setUp(): void {
        _test_reset_menus();   // resets $GLOBALS['_is_network_admin']
        _test_reset_filters();
    }

    // ── Context resolution ───────────────────────────────────────

    public function test_defaults_to_site_context(): void {
        $this->assertFalse( AdminContext::is_network() );
        $this->assertSame( 'admin_menu', AdminContext::hook() );
        $this->assertSame( 'manage_options', AdminContext::capability( 'manage_options' ) );
    }

    public function test_network_context_flips_hook_and_capability(): void {
        $GLOBALS['_is_network_admin'] = true;
        $this->assertTrue( AdminContext::is_network() );
        $this->assertSame( 'network_admin_menu', AdminContext::hook() );
        $this->assertSame( 'manage_network', AdminContext::capability( 'manage_options' ) );
    }

    public function test_capability_preserves_custom_default_on_site(): void {
        $this->assertSame( 'edit_posts', AdminContext::capability( 'edit_posts' ) );
    }

    // ── Opt-in resolution ────────────────────────────────────────

    public function test_legacy_adapter_defaults_site_true_network_false(): void {
        $adapter = new LegacyAdapter();
        $this->assertTrue( AdminContext::wants_site( $adapter ) );
        $this->assertFalse( AdminContext::wants_network( $adapter ) );
    }

    public function test_opt_in_adapter_reports_its_flags(): void {
        $this->assertFalse( AdminContext::wants_site( new OptInAdapter( false, true ) ) );
        $this->assertTrue( AdminContext::wants_network( new OptInAdapter( false, true ) ) );
        $this->assertTrue( AdminContext::wants_site( new OptInAdapter( true, false ) ) );
        $this->assertFalse( AdminContext::wants_network( new OptInAdapter( true, false ) ) );
    }

    // ── Dual-hook binding ────────────────────────────────────────

    public function test_bind_menu_registers_on_both_hooks(): void {
        AdminContext::bind_menu( static function () {}, 7 );

        $this->assertNotEmpty( $GLOBALS['_wp_actions']['admin_menu'] ?? [] );
        $this->assertNotEmpty( $GLOBALS['_wp_actions']['network_admin_menu'] ?? [] );
        $this->assertSame( 7, $GLOBALS['_wp_actions']['admin_menu'][0]['priority'] );
        $this->assertSame( 7, $GLOBALS['_wp_actions']['network_admin_menu'][0]['priority'] );
    }
}
