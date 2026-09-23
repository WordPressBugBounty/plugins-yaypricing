<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\AdminShell;
use YayCommerce\AdminShell\Contracts\PluginMenuAdapter;
use YayCommerce\AdminShell\Menu\SubmenuPositioner;

/**
 * Tests that AdminShell::register_plugin() publishes each plugin's submenu
 * position into the shared cross-scope map consumed by SubmenuPositioner.
 */
class RegisterPluginPositionMapTest extends TestCase {

    protected function setUp(): void {
        AdminShell::reset();
        _test_reset_menus();
        _test_reset_filters();
    }

    protected function tearDown(): void {
        AdminShell::reset();
    }

    private function adapter( string $slug, ?int $position ): PluginMenuAdapter {
        return new class( $slug, $position ) implements PluginMenuAdapter {
            private string $slug;
            private ?int $position;
            public function __construct( string $slug, ?int $position ) {
                $this->slug     = $slug;
                $this->position = $position;
            }
            public function get_menu_title(): string { return 'Title'; }
            public function get_page_title(): string { return 'Page'; }
            public function get_menu_slug(): string { return $this->slug; }
            public function get_settings_page_callback(): ?callable { return null; }
            public function get_settings_page_position(): ?int { return $this->position; }
            public function get_capability(): string { return 'manage_options'; }
            public function get_plugin_basename(): string { return 'plugin/plugin.php'; }
            public function get_settings_label(): string { return 'Settings'; }
            public function get_docs_url(): string { return ''; }
            public function get_pro_url(): string { return ''; }
        };
    }

    public function test_publishes_position_keyed_by_slug(): void {
        AdminShell::register_plugin( $this->adapter( 'yaymail-settings', 20 ) );
        AdminShell::register_plugin( $this->adapter( 'yaycurrency-settings', 10 ) );

        $map = $GLOBALS[ SubmenuPositioner::POSITION_KEY ] ?? [];

        $this->assertSame( 20, $map['yaymail-settings'] );
        $this->assertSame( 10, $map['yaycurrency-settings'] );
    }

    public function test_publishes_null_position(): void {
        AdminShell::register_plugin( $this->adapter( 'no-pos', null ) );

        $map = $GLOBALS[ SubmenuPositioner::POSITION_KEY ] ?? [];

        $this->assertArrayHasKey( 'no-pos', $map );
        $this->assertNull( $map['no-pos'] );
    }

    public function test_empty_slug_is_skipped(): void {
        AdminShell::register_plugin( $this->adapter( '', 30 ) );

        $map = $GLOBALS[ SubmenuPositioner::POSITION_KEY ] ?? [];

        $this->assertArrayNotHasKey( '', $map );
    }
}
