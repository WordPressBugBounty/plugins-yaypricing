# yaycommerce/admin-shell

Shared admin shell for YayCommerce plugins.

Provides: top-level YayCommerce menu, Licenses page, Other Plugins page, Help page, LicenseRegistry, LegacyBridge, and an optional license subsystem (adapter-driven, EDD updater, REST API, state model).

## Requirements

- PHP >= 7.4
- WordPress 5.8+ (runtime only; package has zero hard WP dependencies at composer-level)

---

## 1. Install (first time only)

```bash
cd your-plugin/

# Init Composer if the plugin doesn't have it yet
composer init --name=yaycommerce/your-plugin --type=wordpress-plugin --no-interaction

# Add the private repo + install
composer config repositories.admin-shell vcs https://github.com/YayCommerce/yaycommerce-plugin-admin-shell.git
composer require yaycommerce/admin-shell
composer require --dev humbug/php-scoper

# Run the interactive wizard
php vendor/bin/yaycommerce-init
```

The wizard prompts for all values with smart defaults. Select from the preset plugin list or enter custom values. It auto-generates:

- `{Slug}PluginAdapter.php` — your plugin config, unique class name per plugin (e.g. `YaymailPluginAdapter.php`)
- `scoper.inc.php` — PHP-Scoper config with your unique prefix
- Updates `composer.json` classmap
- Updates `.gitignore`
- Configures GitHub token if needed

Then add these lines to your main plugin file (wizard prints exact code):

```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/YaymailPluginAdapter.php';  // unique per plugin

add_action( 'plugins_loaded', function() {
    \YayMailScoped\YayCommerce\AdminShell\AdminShell::boot();
    \YayMailScoped\YayCommerce\AdminShell\AdminShell::register_plugin(
        new \YaymailPluginAdapter()
    );
}, 5 );
```

> **Note:** The adapter class name is derived from your slug to prevent collisions when multiple YayCommerce plugins are active. E.g. `yaymail` → `YaymailPluginAdapter`, `yay_currency` → `YayCurrencyPluginAdapter`.

Finally, build for the first time:

```bash
./vendor/bin/yaycommerce-update
```

---

## 2. Update (during development)

When the admin-shell has a new version on GitHub:

```bash
./vendor/bin/yaycommerce-update
```

Fetches the latest version + rebuilds `vendor-prefixed/`. Dev environment stays intact.

Both `yaycommerce-update` and `yaycommerce-prerelease` also bump the build tools to their latest versions (`composer require --dev humbug/php-scoper thecodingmachine/safe -W`), so a stale `composer.lock` pin never blocks a PHP-Scoper upgrade.

---

## 3. Release (before packaging)

Add one line to your release script before the ZIP/packaging step:

```bash
./vendor/bin/yaycommerce-prerelease
```

This fetches the latest admin-shell + rebuilds `vendor-prefixed/` in one command. Dev environment stays intact — no need to restore anything after.

Example in your release script:

```bash
#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

./vendor/bin/yaycommerce-prerelease    # ← ensures latest admin-shell + clean build

# ... your existing packaging steps (ZIP, upload, etc.)
```

---

## CLI Commands

| Command | When | What it does |
|---|---|---|
| `vendor/bin/yaycommerce-init` | First time | Wizard → generates adapter, scoper config, .gitignore |
| `vendor/bin/yaycommerce-update` | During dev | Fetch latest + rebuild `vendor-prefixed/` |
| `vendor/bin/yaycommerce-prerelease` | Before release | Fetch latest + rebuild (same as update, for release scripts) |

### `yaycommerce-init` Options

```bash
# Interactive wizard (prompts for all values)
php vendor/bin/yaycommerce-init

# Overwrite existing generated files
php vendor/bin/yaycommerce-init --force

# Non-interactive (all values via flags — useful for CI)
php vendor/bin/yaycommerce-init --slug=yaymail --prefix=YayMailScoped --name="YayMail Pro" --item-id=4216

# With GitHub token
php vendor/bin/yaycommerce-init --github-token=ghp_xxx

# Show all available flags
php vendor/bin/yaycommerce-init --help
```

| Flag | Description |
|---|---|
| `--slug` | Plugin slug / wp_options prefix (e.g. `yaymail`, `yay_currency`) |
| `--prefix` | PHP-Scoper namespace prefix (e.g. `YayMailScoped`) |
| `--name` | Full plugin name for license card |
| `--menu-title` | Short name for sidebar submenu |
| `--menu-slug` | Settings page slug (e.g. `yaymail-settings`) |
| `--version-const` | PHP constant for version (e.g. `YAYMAIL_VERSION`) |
| `--path-const` | PHP constant for plugin path |
| `--basename-const` | PHP constant for basename |
| `--main-file` | Main plugin filename (e.g. `yaymail.php`) |
| `--item-id` | EDD download ID (0 for lite plugins) |
| `--store-link` | Product page URL |
| `--settings-label` | Settings action link label |
| `--docs-url` | Documentation URL (blank = hide) |
| `--pro-url` | Go Pro URL (blank = hide, for pro plugins) |
| `--github-token` | GitHub PAT for private repo access |
| `--force` | Overwrite existing generated files |
| `--help` | Show all flags |

**Safe to re-run:** Without `--force`, the wizard skips files that already exist. With `--force`, it overwrites adapter, scoper config, and updates composer.json/gitignore.

**Preset plugins:** The wizard includes 18 presets (9 pro + 9 lite). Select from the list or choose "Custom" for new plugins.

---

## Release Excludes

These files/folders are **build-time only** and should be excluded from your production ZIP:

| Exclude | Reason |
|---|---|
| `vendor/` | Dev dependencies + unscoped source. `vendor-prefixed/` replaces it. |
| `scoper.inc.php` | PHP-Scoper build config |
| `composer.json` | Composer config |
| `composer.lock` | Composer lockfile |
| `node_modules/` | If present |
| `tests/` | If present |
| `.git/` | Git history |

**What ships to customers:**

| Include | Purpose |
|---|---|
| `vendor-prefixed/` | Scoped admin-shell (PHP + views + assets) |
| `vendor/autoload.php` + `vendor/composer/` | Composer autoloader |
| `{Slug}PluginAdapter.php` | Your plugin's config (e.g. `YaymailPluginAdapter.php`) |
| `src/` | Your plugin's own code |
| Main plugin file | Entry point |

Example ZIP exclude flags:

```bash
zip -r plugin.zip plugin/ \
    -x 'plugin/.git/*' \
    -x 'plugin/vendor/*' \
    -x 'plugin/tests/*' \
    -x 'plugin/node_modules/*' \
    -x 'plugin/scoper.inc.php' \
    -x 'plugin/composer.json' \
    -x 'plugin/composer.lock'
```

> **Note:** `vendor/autoload.php` and `vendor/composer/` are needed at runtime. If your release script does `composer install --no-dev` before zipping, include `vendor/` but only the autoloader subset. Alternatively, exclude `vendor/` entirely and rely on `vendor-prefixed/` classmap in `composer.json`.

---

## What You Get

- **YayCommerce top-level menu** (shared, first-to-register wins across all plugins)
- **Plugin-named submenu** with settings page callback (pro: license-gated, lite: direct)
- **Unified Licenses page** across all installed YayCommerce plugins
- **License activate / update / deactivate** via REST API (pro only)
- **EDD auto-updater** for plugin updates from your store (pro only)
- **Plugin action links**: Settings | Enter license key (pro, when inactive) | Go Pro (lite only)
- **Plugin row meta**: Docs | Support
- **Plugin row notifications**: "Please activate your license..." / "Your license has expired..."
- **Other Plugins** recommendation page (hides already-active plugins)
- **Help page** with support links
- **LegacyBridge** compatibility with un-migrated plugins
- **Important notice banner** on Licenses page when any plugin is inactive

---

## Adapter Reference

### PluginMenuAdapter (lite plugins — 9 methods)

| Method | Purpose | Example |
|---|---|---|
| `get_menu_title()` | Sidebar submenu label | `'YayMail'` |
| `get_menu_slug()` | Menu slug under YayCommerce | `'yaymail-settings'` |
| `get_settings_page_callback()` | Render callback (null = no page) | `[ $page, 'render' ]` |
| `get_settings_page_position()` | Submenu position (null = default) | `0` |
| `get_capability()` | Required WP capability | `'manage_options'` |
| `get_plugin_basename()` | WP basename | `YAYMAIL_PLUGIN_BASENAME` |
| `get_settings_label()` | Settings action link label | `'Settings'` |
| `get_docs_url()` | Documentation URL (empty = hide) | `'https://docs.yaycommerce.com/...'` |
| `get_pro_url()` | Go Pro URL (empty = hide) | `'https://yaycommerce.com/...'` |

#### Optional methods (Multisite placement)

These are **optional** — detected via `method_exists()`, NOT declared on the interface, so existing adapters need no changes. See [Multisite / Network Admin](#multisite--network-admin).

| Method | Purpose | Default if absent |
|---|---|---|
| `wants_site_menu(): bool` | Show submenu on each site's dashboard | `true` |
| `wants_network_menu(): bool` | Show submenu in the Multisite Network Admin | `false` |

### LicenseConfigAdapter (pro plugins — extends PluginMenuAdapter + 7 methods)

| Method | Purpose | Example |
|---|---|---|
| `get_plugin_slug()` | wp_options key prefix (**byte-exact**) | `'yaymail'` |
| `get_plugin_name()` | License card title (full name) | `'YayMail Pro - WooCommerce Email Customizer'` |
| `get_plugin_version()` | Current version | `YAYMAIL_VERSION` |
| `get_plugin_file()` | Absolute path to main file | `YAYMAIL_PLUGIN_PATH . 'yaymail.php'` |
| `get_item_id()` | EDD download ID | `4216` |
| `get_store_url()` | EDD store URL | `'https://yaycommerce.com/'` |
| `get_store_link()` | Product page URL | `'https://yaycommerce.com/yaymail-...'` |

### Auto-detection

`AdminShell::register_plugin()` auto-detects pro vs lite:

```php
// Lite — PluginMenuAdapter → menu only
AdminShell::register_plugin( new YaymailPluginAdapter() );

// Pro — LicenseConfigAdapter → menu + license subsystem
AdminShell::register_plugin( new YaymailPluginAdapter() );  // same call, adapter type decides
```

**Lite vs Pro**: determined by the interface the adapter implements. `LicenseConfigAdapter` = pro (includes license handler, EDD updater, REST). `PluginMenuAdapter` = lite (menu + action links only).

---

## Multisite / Network Admin

By default the YayCommerce menu and every plugin submenu render only on each
site's dashboard (`admin_menu`). On WordPress Multisite, the shell can also
render in the **Network Admin** (`network_admin_menu`).

**What appears in Network Admin:** the YayCommerce top-level menu, plus **Help**,
**Licenses**, and **Recommended Plugins** — and any plugin submenu whose adapter
opts in via `wants_network_menu()`.

**Per-context opt-in** (both optional, defaults preserve current behavior):

```php
// Site-only (DEFAULT — same as omitting both methods)
public function wants_site_menu(): bool    { return true; }
public function wants_network_menu(): bool { return false; }

// Network-only: hide from per-site dashboards, show in Network Admin
public function wants_site_menu(): bool    { return false; }
public function wants_network_menu(): bool { return true; }

// Both contexts
public function wants_site_menu(): bool    { return true; }
public function wants_network_menu(): bool { return true; }
```

Notes:

- Network pages/submenus require the `manage_network` capability (super-admins only).
- **Licenses in Network Admin are display-only** — keys are still stored/activated
  per site (main-site `wp_options`). The page shows a notice clarifying this.
- Single-site installs are unaffected: `network_admin_menu` never fires.

## Version Election (multi-plugin coexistence)

When multiple YayCommerce plugins are active, each has its own scoped copy of admin-shell (via PHP-Scoper). The version election ensures only the **highest version** registers the shared UI (top-level menu, Licenses page, Other Plugins, Help).

**How it works:**

1. Each plugin's `AdminShell::boot()` registers its version in `$GLOBALS['yaycommerce_admin_shell_versions']`
2. At `admin_menu` priority 8, `elect_version()` picks the highest version
3. The winning version's shell registers menus/pages
4. ALL versions' `register_plugin()` and `enable_license()` still run (per-plugin, global WP hooks)

**Result:** The Licenses page always uses the newest UI. All plugins' license cards appear regardless of which version renders the page (via `do_action('yaycommerce_licenses_page')` which is a global WP hook).

**No action required from developers.** This is handled automatically by `AdminShell::boot()`.

---

## Slug Quirks (CRITICAL — do not normalize)

Three plugins use non-standard slugs. The slug MUST match the existing `wp_options` key prefix
byte-for-byte or customer license keys will be lost on upgrade:

| Plugin         | Slug (exact)           | wp_options keys                                                    |
|----------------|------------------------|--------------------------------------------------------------------|
| YayWholesale   | `yay-wholesale-b2b-pro` | `yay-wholesale-b2b-pro_license_key`, `yay-wholesale-b2b-pro_license_info` |
| YayCurrency    | `yay_currency`         | `yay_currency_license_key`, `yay_currency_license_info`            |
| YaySwatches    | `yay_swatches`         | `yay_swatches_license_key`, `yay_swatches_license_info`            |

Option keys are derived as: `{slug}_license_key` and `{slug}_license_info`.

### Runtime Sanitization (JS + REST sinks)

Raw slugs can contain chars invalid as JS identifiers (e.g. `-` in `yay-wholesale-b2b-pro`). The shell uses `YayCommerce\AdminShell\Support\Slug::to_var_name()` to sanitize **only runtime-derived sinks**:

| Sink | Treatment |
|---|---|
| wp_options keys, DOM `data-plugin`, JS `data.slug` | **Raw** (byte-exact) |
| `wp_localize_script` var name | **Sanitized** (`yay_wholesale_b2b_proLicenseData`) |
| REST namespace + URL (`register_rest_route`, `rest_url`) | **Sanitized** (must stay in lockstep) |

Rule of thumb: sanitize runtime values; keep raw anything persisted or keyed to persisted data.

## Hook Contract

| Hook | Type | Description |
|------|------|-------------|
| `yaycommerce_admin_shell_booted` | action | Fired after `AdminShell::boot()`. |
| `yaycommerce_admin_shell_license_enabled` | action($adapter) | Fired when `enable_license()` is called. |
| `yaycommerce_admin_shell_plugin_info` | filter($info, $slug) | Decorate a `PluginLicenseInfo` before registry stores it. |
| `yaycommerce_licensing_plugins` | filter (LEGACY, read-only) | LegacyBridge reads this at priority 999. New code must NOT emit. |

## Version Policy

This package follows semantic versioning:

- **PATCH** — bug fixes, no API changes
- **MINOR** — new methods/properties added (append-only)
- **MAJOR** — breaking changes (method removed/renamed, interface restructured)

See `CONTRIBUTING.md` for the append-only contract.

## ⚠️ PHP-Scoper Required

**PHP-Scoper is MANDATORY for multi-plugin coexistence.** Each consuming plugin prefixes the package under a unique namespace (e.g. `YayMailScoped`) so every plugin runs its own isolated copy. The version election system (see above) then coordinates which copy registers the shared UI.

The wizard generates `scoper.inc.php` automatically. The `yaycommerce-update` and `yaycommerce-prerelease` scripts handle scoping automatically. Developers never touch scoper config by hand.

> **Multisite note:** the shell calls the global WP functions `is_network_admin()` and `network_admin_url()` (leading-backslash, so PHP-Scoper leaves them global). If a consuming plugin uses a custom `scoper.inc.php`, ensure these stay in `exclude-functions` (WordPress globals are excluded by default in the standard wizard config). Prefixing them would break Network Admin detection.

## EDD_SL_Plugin_Updater

`src/License/EDD_SL_Plugin_Updater.php` is a vendored third-party file. It must not be modified. Original source: Easy Digital Downloads Software Licensing plugin updater class.
