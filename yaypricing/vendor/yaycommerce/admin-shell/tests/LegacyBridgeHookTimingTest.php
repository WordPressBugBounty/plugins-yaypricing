<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\Registry\LegacyBridge;
use YayCommerce\AdminShell\Registry\LicenseRegistry;

/**
 * Regression test for C2: LegacyBridge must hook plugins_loaded (not admin_init)
 * so the registry is populated before admin_menu fires.
 *
 * WordPress hook order: plugins_loaded → admin_menu → admin_init.
 * PagesRouter registers the Licenses submenu on admin_menu, gated on
 * !empty($registry->all()). If LegacyBridge only runs on admin_init, the
 * registry is empty at admin_menu time and the submenu never appears.
 */
class LegacyBridgeHookTimingTest extends TestCase {

    protected function setUp(): void {
        _test_reset_options();
        _test_reset_filters();
    }

    /**
     * Verify LegacyBridge::init() hooks plugins_loaded, not admin_init.
     */
    public function test_init_hooks_plugins_loaded_not_admin_init(): void {
        $registry = new LicenseRegistry();
        $bridge   = new LegacyBridge( $registry );
        $bridge->init();

        $plugins_loaded_hooks = $GLOBALS['_wp_actions']['plugins_loaded'] ?? [];
        $admin_init_hooks     = $GLOBALS['_wp_actions']['admin_init'] ?? [];

        // Must be registered on plugins_loaded
        $registered_on_plugins_loaded = false;
        foreach ( $plugins_loaded_hooks as $hook ) {
            if ( is_array( $hook['callback'] ) && $hook['callback'][0] === $bridge && $hook['callback'][1] === 'load_legacy_plugins' ) {
                $registered_on_plugins_loaded = true;
                break;
            }
        }
        $this->assertTrue(
            $registered_on_plugins_loaded,
            'LegacyBridge::init() must register load_legacy_plugins on plugins_loaded hook.'
        );

        // Must NOT be registered on admin_init
        $registered_on_admin_init = false;
        foreach ( $admin_init_hooks as $hook ) {
            if ( is_array( $hook['callback'] ) && $hook['callback'][0] === $bridge && $hook['callback'][1] === 'load_legacy_plugins' ) {
                $registered_on_admin_init = true;
                break;
            }
        }
        $this->assertFalse(
            $registered_on_admin_init,
            'LegacyBridge::init() must NOT register load_legacy_plugins on admin_init (runs after admin_menu).'
        );
    }

    /**
     * Verify the plugins_loaded hook uses priority 9999 (after VersionedLoader at 999).
     */
    public function test_init_registers_at_priority_9999(): void {
        $registry = new LicenseRegistry();
        $bridge   = new LegacyBridge( $registry );
        $bridge->init();

        $plugins_loaded_hooks = $GLOBALS['_wp_actions']['plugins_loaded'] ?? [];

        $found_priority = null;
        foreach ( $plugins_loaded_hooks as $hook ) {
            if ( is_array( $hook['callback'] ) && $hook['callback'][0] === $bridge && $hook['callback'][1] === 'load_legacy_plugins' ) {
                $found_priority = $hook['priority'];
                break;
            }
        }

        $this->assertSame(
            9999,
            $found_priority,
            'LegacyBridge must hook plugins_loaded at priority 9999 to run after VersionedLoader (999) and legacy filters (100).'
        );
    }

    /**
     * Simulate the hook order: plugins_loaded fires → LegacyBridge populates registry
     * BEFORE admin_menu would run. Verifies registry is non-empty after plugins_loaded.
     */
    public function test_registry_populated_before_admin_menu_fires(): void {
        add_filter( 'yaycommerce_licensing_plugins', function( $plugins ) {
            return array_merge( $plugins, [ [
                'slug'     => 'yaytestplugin',
                'name'     => 'YayTest Plugin',
                'basename' => 'yaytest/yaytest.php',
                'file'     => '/path/to/yaytest.php',
                'url'      => 'https://yaycommerce.com/yaytest',
                'item_id'  => 99999,
                'dir_path' => '/path/to/',
            ] ] );
        }, 100 );

        $registry = new LicenseRegistry();
        $bridge   = new LegacyBridge( $registry );
        $bridge->init();

        // Simulate: at admin_menu time, registry must already be populated.
        // In real WP, plugins_loaded fires first. We simulate by calling load_legacy_plugins directly.
        // The hook being on plugins_loaded (not admin_init) ensures this order is correct.
        $bridge->load_legacy_plugins();

        // Registry populated — Licenses submenu gate passes.
        $this->assertFalse(
            empty( $registry->all() ),
            'Registry must be non-empty after plugins_loaded fires, so Licenses submenu is visible at admin_menu time.'
        );
        $this->assertNotNull( $registry->get( 'yaytestplugin' ) );
    }
}
