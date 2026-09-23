<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\AdminShell;
use YayCommerce\AdminShell\License\Contracts\LicenseConfigAdapter;

/**
 * Regression test for H3: AdminShell::enable_license() must be idempotent.
 *
 * Before the fix, calling enable_license() twice with the same adapter slug
 * would instantiate LicenseHandler twice, registering admin_notices, cron,
 * and admin_init hooks twice — leading to duplicate notices, duplicate cron
 * firings, and duplicate EDD HTTP calls.
 *
 * The fix tracks enabled slugs in a static array and early-returns on repeat calls.
 */
class AdminShellEnableLicenseIdempotencyTest extends TestCase {

    private LicenseConfigAdapter $adapter;

    protected function setUp(): void {
        _test_reset_options();
        _test_reset_filters();
        AdminShell::reset();

        $this->adapter = new class implements LicenseConfigAdapter {
            public function get_plugin_slug(): string    { return 'yayidempotent'; }
            public function get_plugin_name(): string    { return 'YayIdempotent Pro'; }
            public function get_menu_title(): string     { return 'YayIdempotent'; }
            public function get_page_title(): string      { return 'YayIdempotent Settings'; }
            public function get_menu_slug(): string   { return ''; }
            public function get_settings_page_callback(): ?callable { return null; }
            public function get_settings_page_position(): ?int { return null; }
            public function get_settings_label(): string { return 'Settings'; }
            public function get_docs_url(): string       { return ''; }
            public function get_pro_url(): string        { return ''; }
            public function get_plugin_version(): string { return '1.0'; }
            public function get_plugin_file(): string    { return '/tmp/yayidempotent.php'; }
            public function get_plugin_basename(): string { return 'yayidempotent/yayidempotent.php'; }
            public function get_item_id(): int           { return 1234; }
            public function get_store_url(): string      { return 'https://yaycommerce.com/'; }
            public function get_store_link(): string     { return 'https://yaycommerce.com/yayidempotent'; }
            public function get_capability(): string     { return 'manage_options'; }
        };
    }

    protected function tearDown(): void {
        AdminShell::reset();
    }

    /**
     * Calling enable_license() twice must only register hooks once.
     * We count 'admin_init' hooks in _wp_actions to detect double-registration.
     */
    public function test_enable_license_called_twice_registers_hooks_once(): void {
        AdminShell::enable_license( $this->adapter );
        $hooks_after_first = $GLOBALS['_wp_actions']['admin_init'] ?? [];

        AdminShell::enable_license( $this->adapter ); // second call — must be no-op
        $hooks_after_second = $GLOBALS['_wp_actions']['admin_init'] ?? [];

        $this->assertCount(
            count( $hooks_after_first ),
            $hooks_after_second,
            'Calling enable_license() twice must not add more admin_init hooks — LicenseHandler must only be instantiated once.'
        );
    }

    /**
     * Second call with same slug must not re-register in the registry.
     * The registry should only hold one entry for the slug.
     */
    public function test_enable_license_called_twice_registers_once_in_registry(): void {
        AdminShell::enable_license( $this->adapter );
        AdminShell::enable_license( $this->adapter );

        $all = AdminShell::registry()->all();
        $count = 0;
        foreach ( $all as $info ) {
            if ( $info->slug === 'yayidempotent' ) {
                $count++;
            }
        }

        $this->assertSame( 1, $count, 'Registry must contain exactly one entry for a slug, even if enable_license() is called twice.' );
    }

    /**
     * Two different slugs must both be registered normally.
     */
    public function test_two_different_slugs_both_register(): void {
        $adapter2 = new class implements LicenseConfigAdapter {
            public function get_plugin_slug(): string    { return 'yayother'; }
            public function get_plugin_name(): string    { return 'YayOther Pro'; }
            public function get_menu_title(): string     { return 'YayOther'; }
            public function get_page_title(): string      { return 'YayOther Settings'; }
            public function get_menu_slug(): string   { return ''; }
            public function get_settings_page_callback(): ?callable { return null; }
            public function get_settings_page_position(): ?int { return null; }
            public function get_settings_label(): string { return 'Settings'; }
            public function get_docs_url(): string       { return ''; }
            public function get_pro_url(): string        { return ''; }
            public function get_plugin_version(): string { return '1.0'; }
            public function get_plugin_file(): string    { return '/tmp/yayother.php'; }
            public function get_plugin_basename(): string { return 'yayother/yayother.php'; }
            public function get_item_id(): int           { return 5678; }
            public function get_store_url(): string      { return 'https://yaycommerce.com/'; }
            public function get_store_link(): string     { return 'https://yaycommerce.com/yayother'; }
            public function get_capability(): string     { return 'manage_options'; }
        };

        AdminShell::enable_license( $this->adapter );
        AdminShell::enable_license( $adapter2 );

        $this->assertNotNull( AdminShell::registry()->get( 'yayidempotent' ) );
        $this->assertNotNull( AdminShell::registry()->get( 'yayother' ) );
    }
}
