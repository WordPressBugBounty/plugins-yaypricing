<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\Menu\SubmenuPositioner;

/**
 * Tests SubmenuPositioner — the late post-sort that orders the final
 * $submenu['yaycommerce'] array by declared position.
 *
 * Regression: WP's add_submenu_page() treats $position as an array insertion
 * index and silently appends out-of-range values, so sparse positions like
 * 10,20,21,22,40 produced plugin-load order instead of position order.
 */
class SubmenuPositionerTest extends TestCase {

    protected function setUp(): void {
        _test_reset_menus();
    }

    /** Build a $submenu['yaycommerce'] entry. */
    private function item( string $slug ): array {
        return [ $slug, 'manage_options', $slug, $slug ];
    }

    /**
     * Seed the WP submenu array (in given registration order) and the shared
     * position map, then run the reorder.
     *
     * @param array<string,?int> $positions slug => position
     * @param string[]           $order     slugs in registration order
     */
    private function reorder( array $positions, array $order ): array {
        $GLOBALS[ SubmenuPositioner::POSITION_KEY ] = $positions;
        foreach ( $order as $slug ) {
            $GLOBALS['submenu']['yaycommerce'][] = $this->item( $slug );
        }

        ( new SubmenuPositioner() )->reorder();

        return array_column( $GLOBALS['submenu']['yaycommerce'], 2 );
    }

    public function test_sparse_positions_sort_ascending(): void {
        $positions = [ 'a' => 10, 'b' => 20, 'c' => 21, 'd' => 22, 'e' => 23, 'f' => 40 ];
        // Registration order deliberately scrambled.
        $result = $this->reorder( $positions, [ 'b', 'f', 'a', 'd', 'c', 'e' ] );

        $this->assertSame( [ 'a', 'b', 'c', 'd', 'e', 'f' ], $result );
    }

    public function test_unknown_and_null_positions_go_last_stable(): void {
        // 'x' has no map entry (e.g. registered by an old shell copy), 'n' is null.
        $positions = [ 'pos5' => 5, 'pos30' => 30, 'n' => null ];
        $result    = $this->reorder( $positions, [ 'x', 'pos30', 'n', 'pos5' ] );

        // Positioned first (5, 30), then unknown/null in original registration order.
        $this->assertSame( [ 'pos5', 'pos30', 'x', 'n' ], $result );
    }

    public function test_equal_positions_keep_registration_order(): void {
        $positions = [ 'first' => 10, 'second' => 10, 'third' => 10 ];
        $result    = $this->reorder( $positions, [ 'first', 'second', 'third' ] );

        $this->assertSame( [ 'first', 'second', 'third' ], $result );
    }

    public function test_all_null_positions_preserve_registration_order(): void {
        // Empty/absent map → every item unknown → original order preserved.
        $result = $this->reorder( [], [ 'gamma', 'alpha', 'beta' ] );

        $this->assertSame( [ 'gamma', 'alpha', 'beta' ], $result );
    }

    public function test_null_position_plugins_rank_before_shell_pages(): void {
        // Shell pages (Help/Licenses/Recommended) are registered by PagesRouter at
        // admin_menu priority 11 — after plugins (priority 10) — and are NOT in the
        // position map, so they share the null tier with positionless plugins. The
        // stable sort keeps registration order, so null-position plugin pages still
        // rank above the shell pages.
        $positions = [ 'yaycurrency' => 10 ]; // one positioned plugin; 'yaymail' is null
        $order     = [
            'yaycurrency',                 // positioned plugin   (prio 10)
            'yaymail',                     // null-position plugin (prio 10)
            'yaycommerce-help',            // shell page          (prio 11)
            'yaycommerce-licenses',        // shell page          (prio 11)
            'yaycommerce-other-plugins',   // shell page          (prio 11)
        ];

        $result = $this->reorder( $positions, $order );

        $this->assertSame(
            [ 'yaycurrency', 'yaymail', 'yaycommerce-help', 'yaycommerce-licenses', 'yaycommerce-other-plugins' ],
            $result
        );
    }

    public function test_reindexes_to_sequential_keys(): void {
        $this->reorder( [ 'a' => 2, 'b' => 1 ], [ 'a', 'b' ] );

        $this->assertSame( [ 0, 1 ], array_keys( $GLOBALS['submenu']['yaycommerce'] ) );
    }

    public function test_no_yaycommerce_submenu_is_noop(): void {
        $GLOBALS[ SubmenuPositioner::POSITION_KEY ] = [ 'a' => 1 ];
        // No $submenu['yaycommerce'] seeded.

        ( new SubmenuPositioner() )->reorder();

        $this->assertArrayNotHasKey( 'yaycommerce', $GLOBALS['submenu'] );
    }

    public function test_init_hooks_reorder_at_late_priority(): void {
        _test_reset_filters();
        ( new SubmenuPositioner() )->init();

        $found = false;
        foreach ( $GLOBALS['_wp_actions']['admin_menu'] ?? [] as $action ) {
            if ( is_array( $action['callback'] ) && $action['callback'][1] === 'reorder' && $action['priority'] === 9999 ) {
                $found = true;
                break;
            }
        }
        $this->assertTrue( $found, 'reorder should be hooked at admin_menu priority 9999' );
    }
}
