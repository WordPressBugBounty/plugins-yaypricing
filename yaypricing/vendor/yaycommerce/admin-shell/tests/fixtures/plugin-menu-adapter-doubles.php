<?php
/**
 * Shared PluginMenuAdapter test doubles for the Network Admin test suites.
 * Required by tests/bootstrap.php so every test file reuses the same doubles
 * (avoids duplicate class declarations across files).
 */

namespace YayCommerce\AdminShell\Tests\Fixtures;

use YayCommerce\AdminShell\Contracts\PluginMenuAdapter;

/** Legacy adapter: interface only, no opt-in methods (site=true, network=false). */
class LegacyAdapter implements PluginMenuAdapter {
    public function get_menu_title(): string { return 'Test'; }
    public function get_page_title(): string { return 'Test Page'; }
    public function get_menu_slug(): string { return 'test-settings'; }
    public function get_settings_page_callback(): ?callable { return null; }
    public function get_settings_page_position(): ?int { return null; }
    public function get_capability(): string { return 'manage_options'; }
    public function get_plugin_basename(): string { return 'test/test.php'; }
    public function get_settings_label(): string { return 'Settings'; }
    public function get_docs_url(): string { return ''; }
    public function get_pro_url(): string { return ''; }
}

/** Adapter implementing the optional per-context opt-in methods. */
class OptInAdapter extends LegacyAdapter {
    private bool $site;
    private bool $network;
    public function __construct( bool $site, bool $network ) {
        $this->site    = $site;
        $this->network = $network;
    }
    public function wants_site_menu(): bool { return $this->site; }
    public function wants_network_menu(): bool { return $this->network; }
}
