<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\Menu\PluginSubmenu;
use YayCommerce\AdminShell\Tests\Fixtures\LegacyAdapter;
use YayCommerce\AdminShell\Tests\Fixtures\OptInAdapter;

/**
 * PluginSubmenu context gating matrix (site/network × opt-in flags).
 *
 * A submenu registers only in the context the plugin opted into; in Network
 * Admin the capability is elevated to manage_network. Defaults (legacy
 * adapters): site=true, network=false.
 */
class NetworkMenuGatingTest extends TestCase {

    protected function setUp(): void {
        _test_reset_menus();
        _test_reset_filters();
    }

    private function register( $adapter ): array {
        ( new PluginSubmenu( $adapter ) )->register();
        return $GLOBALS['submenu']['yaycommerce'] ?? [];
    }

    // ── Site context ─────────────────────────────────────────────

    public function test_site_registers_legacy_adapter(): void {
        $this->assertCount( 1, $this->register( new LegacyAdapter() ) );
    }

    public function test_site_registers_when_wants_site_true(): void {
        $this->assertCount( 1, $this->register( new OptInAdapter( true, false ) ) );
    }

    public function test_site_skips_when_wants_site_false(): void {
        $this->assertEmpty( $this->register( new OptInAdapter( false, true ) ) );
    }

    // ── Network context ──────────────────────────────────────────

    public function test_network_skips_legacy_adapter(): void {
        $GLOBALS['_is_network_admin'] = true;
        $this->assertEmpty( $this->register( new LegacyAdapter() ) );
    }

    public function test_network_skips_when_wants_network_false(): void {
        $GLOBALS['_is_network_admin'] = true;
        $this->assertEmpty( $this->register( new OptInAdapter( true, false ) ) );
    }

    public function test_network_registers_flagged_adapter(): void {
        $GLOBALS['_is_network_admin'] = true;
        $this->assertCount( 1, $this->register( new OptInAdapter( false, true ) ) );
    }

    public function test_network_elevates_capability_to_manage_network(): void {
        $GLOBALS['_is_network_admin'] = true;
        $items = $this->register( new OptInAdapter( false, true ) );
        // add_submenu_page stub stores: [ menu_title, capability, menu_slug, page_title ]
        $this->assertSame( 'manage_network', $items[0][1] );
    }

    public function test_site_keeps_adapter_capability(): void {
        $items = $this->register( new LegacyAdapter() );
        $this->assertSame( 'manage_options', $items[0][1] );
    }
}
