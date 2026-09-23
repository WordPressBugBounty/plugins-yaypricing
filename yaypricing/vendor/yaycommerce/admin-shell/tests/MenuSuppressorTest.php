<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\Menu\MenuSuppressor;

/**
 * Tests MenuSuppressor — verifies remove_menu_page() is called for slugs in the list
 * and NOT called for slugs not in the list.
 */
class MenuSuppressorTest extends TestCase {

    protected function setUp(): void {
        _test_reset_menus();
        _test_reset_filters();
    }

    public function test_suppresses_specified_slugs(): void {
        $suppressor = new MenuSuppressor( [ 'old-yaycommerce-menu', 'legacy-yay-plugin' ] );

        // Pre-register menus in global
        $GLOBALS['admin_page_hooks']['old-yaycommerce-menu']  = 'Old YayCommerce Menu';
        $GLOBALS['admin_page_hooks']['legacy-yay-plugin']     = 'Legacy Yay Plugin';
        $GLOBALS['admin_page_hooks']['keep-this-menu']        = 'Keep This';

        $suppressor->suppress_legacy_menus();

        $this->assertContains( 'old-yaycommerce-menu', $GLOBALS['_wp_menu_removed'] );
        $this->assertContains( 'legacy-yay-plugin', $GLOBALS['_wp_menu_removed'] );
        $this->assertNotContains( 'keep-this-menu', $GLOBALS['_wp_menu_removed'] );
    }

    public function test_empty_list_removes_nothing(): void {
        $suppressor = new MenuSuppressor( [] );

        $GLOBALS['admin_page_hooks']['yaycommerce'] = 'YayCommerce';

        $suppressor->suppress_legacy_menus();

        $this->assertEmpty( $GLOBALS['_wp_menu_removed'] );
    }

    public function test_default_list_is_empty(): void {
        $suppressor = new MenuSuppressor();
        $this->assertSame( [], $suppressor->get_slugs() );
    }

    public function test_init_registers_admin_menu_action(): void {
        _test_reset_filters();
        $suppressor = new MenuSuppressor( [ 'old-slug' ] );
        $suppressor->init();

        $this->assertNotEmpty( $GLOBALS['_wp_actions']['admin_menu'] ?? [] );

        // Verify the priority is 5
        $found = false;
        foreach ( $GLOBALS['_wp_actions']['admin_menu'] as $action ) {
            if ( is_array( $action['callback'] ) && $action['callback'][1] === 'suppress_legacy_menus' && $action['priority'] === 5 ) {
                $found = true;
                break;
            }
        }
        $this->assertTrue( $found, 'suppress_legacy_menus should be registered at admin_menu priority 5' );
    }
}
