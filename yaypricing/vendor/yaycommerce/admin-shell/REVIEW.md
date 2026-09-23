# Code Review: YayCommerce Admin Shell

**Status: APPROVED_WITH_CONCERNS**

**Reviewer:** Staff Engineer (production-readiness review)
**Date:** 2026-04-08
**Scope:** Full package — 21 PHP files, 6 views, 1 JS file, 1 CLI wizard

---

## Critical Findings (must fix)

### C1. Version election race: `elect_version` hook registered by first booter only, but callable points to that booter's class

**File:** `src/AdminShell.php:77`

The `add_action('admin_menu', [static::class, 'elect_version'], 8)` is registered by the FIRST copy to call `boot()`. The `static::class` resolves to the first copy's scoped namespace (e.g., `YayMailScoped\YayCommerce\AdminShell\AdminShell`). If `admin_menu` fires after a later PHP version of the class loads and the first copy's autoloader is broken/unloaded, this action reference becomes stale. In practice this is unlikely with PHP-Scoper (each copy is fully independent), but the architecture is fragile.

**However**, the real bug is subtler: if the first copy registers `elect_version` at priority 8, but the WINNING copy's `do_shell_registration` (line 73 `boot_cb`) calls `self::get_instance()` which returns THAT copy's singleton — this is correct. The election itself always works because `call_user_func($winner['boot_cb'])` invokes the winning copy's static method. **This is actually fine.**

**Downgrade: Not a bug.** The design is correct. The first-copy registers the election hook, but the hook dispatches to whichever copy wins. No action needed.

### C2. Cross-scope registry merge silently drops plugins with duplicate slugs

**File:** `src/AdminShell.php:126-130`

```php
foreach ( $other_registry->all() as $info ) {
    if ( ! $instance->registry->get( $info->slug ) ) {
        $instance->registry->register( $info );
    }
}
```

If two different scoped copies both register a plugin with the same slug (e.g., both YayMail Pro and YayMail Lite register slug `yaymail`), the first one wins silently. This is probably intentional but could cause subtle issues:

- Pro plugin registers via `LicenseConfigAdapter` (has license info)
- Lite plugin registers via `PluginMenuAdapter` (no license info)
- If Lite's copy is the winner, it has `yaymail` in its registry without license data
- Pro's `yaymail` entry gets dropped during merge

**Impact:** License card may not render for pro plugin if lite's copy wins election AND lite registered first. The `enable_license` dedup (line 216) prevents double-registration within the same copy, but cross-copy the merge order matters.

**Severity: CRITICAL** when pro + lite of the same plugin are active simultaneously.

**Fix:** During merge, prefer entries that have `license_key` set (or are non-legacy) over entries without.

### C3. `RecommendedPluginsPage` AJAX handlers missing capability check

**File:** `src/Pages/RecommendedPluginsPage.php:108-217`

All three AJAX handlers (`ajax_get_plugin_data`, `ajax_activate_plugin`, `ajax_upgrade_plugin`) verify nonce but **never check `current_user_can()`**. A subscriber-level user with a valid nonce can:

- Install arbitrary plugins from wordpress.org (`ajax_upgrade_plugin`)
- Activate any installed plugin (`ajax_activate_plugin`)

WordPress AJAX fires for any logged-in user by default (`wp_ajax_*` hooks). The nonce check only proves the request is not forged, not that the user has permission.

**Impact:** Privilege escalation. Any authenticated user can install/activate plugins.

**Fix:** Add `if (!current_user_can('install_plugins')) { wp_send_json_error(...); }` at the top of each handler.

---

## High Findings (should fix)

### H1. License `remove()` swallows EDD deactivation failure

**File:** `src/License/License.php:93-107`

```php
public function remove(): void {
    $license_key = $this->get_license_key();
    if ( ! empty( $license_key ) ) {
        LicenseAPI::deactivate_license(...); // return value ignored
    }
    $this->remove_license_key();
    $this->remove_license_info();
}
```

If `deactivate_license()` fails (network error, server error), the local license is still removed. The user loses their activation slot on the EDD server with no way to recover it — they'd need to contact support.

**Fix:** Check return value. If `!$response['success'] && empty($response['is_server_error'])`, throw or return an error. If it's a server error, proceed but log a warning.

### H2. `License::update()` auto-removes license on any non-server-error failure

**File:** `src/License/License.php:86-88`

```php
} elseif ( empty( $response['is_server_error'] ) ) {
    $this->remove();
}
```

If the EDD check returns `success: false` for a transient reason (e.g., site URL mismatch after migration, temporary API glitch that returns a 200 with error body), the cron job will **auto-deactivate and delete the customer's license key**. The customer loses their license without any action on their part.

**Impact:** Customer-facing data loss during site migrations or EDD API hiccups.

**Fix:** Differentiate between "license is genuinely invalid" (e.g., `response.license === 'disabled'` or `'revoked'`) vs "check failed for unknown reason". Only auto-remove for definitive invalidity. Add a grace period counter before removal.

### H3. Cron hooks accumulate on plugin deactivation/reactivation cycles

**File:** `src/License/LicenseHandler.php:79-86`

```php
public function do_cron_job(): void {
    add_filter( 'cron_schedules', [ $this, 'custom_schedules' ] );
    add_action( 'check_license_cron_' . $this->adapter->get_plugin_slug(), ... );
    if ( ! wp_next_scheduled( $cron_hook ) ) {
        wp_schedule_event( time(), 'daily', $cron_hook );
    }
}
```

When a plugin is deactivated, the cron event for `check_license_cron_{slug}` is never cleared. When re-activated, a new event is scheduled (the old one persists). Over time, multiple identical cron events accumulate. Not harmful (they all run the same check), but wasteful.

**Fix:** Add deactivation hook: `register_deactivation_hook($adapter->get_plugin_file(), function() use ($slug) { wp_clear_scheduled_hook('check_license_cron_' . $slug); });`

### H4. `HelpPage::load_data()` uses `wp_redirect()` to external URL — should use `wp_safe_redirect()` or whitelist

**File:** `src/Pages/HelpPage.php:17`

```php
public static function load_data(): void {
    wp_redirect( 'https://yaycommerce.com/support/' );
    exit;
}
```

`wp_redirect()` to an external URL. The `render()` method correctly uses `wp_safe_redirect()`, but `load_data()` does not. `wp_safe_redirect()` blocks external URLs by default, which is why `render()` probably won't work either — but `load_data()` fires first and exits.

Since `yaycommerce.com` is a trusted domain, use `wp_redirect()` consistently (or add it to `allowed_redirect_hosts` filter). Currently inconsistent.

### H5. REST API double nonce verification — redundant but not harmful

**File:** `src/License/RestAPI.php:57-59`

WP REST API already verifies the `X-WP-Nonce` header automatically when the request includes it. The manual `wp_verify_nonce()` check is redundant. Not a bug, but it means the `permission_callback` (which does the real auth check) runs first, and then the nonce is checked again inside the handler. If someone changes `permission_callback` to `__return_true` for testing and forgets to revert, the manual nonce check is a safety net. **Keep it, but document why.**

### H6. `LicenseAPI` uses `wp_remote_get()` for state-changing operations

**File:** `src/License/LicenseAPI.php:15-48`

`activate_license()` and `deactivate_license()` use `wp_remote_get()` (HTTP GET) to mutate server state. While this is EDD's API design (query-string based), some security proxies, caches, or CDNs may cache GET requests or replay them. If a caching layer sits in front of the EDD store, activation responses could be stale.

**Recommendation:** Switch to `wp_remote_post()` with params in the body. EDD SL supports POST for all actions.

---

## Medium Findings (nice to fix)

### M1. `PluginSubmenu` creates a new `License` object on every `admin_menu` (reads DB twice per pro plugin)

**File:** `src/Menu/PluginSubmenu.php:43-44`

```php
$license = new License( $this->adapter );
if ( ! $license->is_active() || $license->is_expired() ) {
```

The `License` constructor calls `get_option()` twice (key + info). This runs at priority 10 on `admin_menu` for every pro plugin. With 5 pro plugins, that's 10 `get_option()` calls. Options are typically autoloaded, but the license options are stored with `autoload=false` (line 33 of License.php: `update_option(..., false)`), so each is a separate DB query.

**Fix:** Cache License objects per slug, or make the options autoloaded since they're read on every admin page load anyway.

### M2. `PluginLicenseInfo::$license_key` stored in plain text in registry, passed through `apply_filters`

**File:** `src/Registry/LicenseRegistry.php:21`

```php
$info = apply_filters( 'yaycommerce_admin_shell_plugin_info', $info, $info->slug );
```

The `PluginLicenseInfo` object contains the raw license key. Any plugin hooking `yaycommerce_admin_shell_plugin_info` can read all customers' license keys. The `toArray()` method redacts it, but the filter passes the full object.

**Recommendation:** Redact `license_key` before passing through the filter, or document that this filter is for internal use only.

### M3. `LicenseAPI::get_error_message()` returns English strings only — not translatable

**File:** `src/License/LicenseAPI.php:121-133`

Error messages are hardcoded English strings, not wrapped in `__()` or `esc_html__()`.

### M4. JS `license.js` fetch calls have no `.catch()` handler

**File:** `assets/js/license.js:65-75, 84-92, 101-109`

All three fetch calls use `.then()` chains with no `.catch()`. If the REST endpoint returns a non-JSON response (e.g., 500 HTML error page, or site is behind maintenance mode), `r.json()` throws. The button stays in loading state forever.

**Fix:** Add `.catch()` to re-enable buttons and show an error message.

### M5. `custom_schedules` filter adds `3hours` schedule but cron uses `daily`

**File:** `src/License/LicenseHandler.php:88-94, 84`

The `custom_schedules()` method registers a `3hours` interval, but `do_cron_job()` uses `daily`. The `3hours` schedule is dead code — registered but never used. Likely a leftover from development.

### M6. `format_license_key()` hides the LAST N characters, exposing the prefix

**File:** `src/License/License.php:124-141`

The method hides the last 20 characters and exposes the first part of the key. EDD license keys are typically 32-char hex strings. Exposing the first 12 characters of a 32-char key reveals 37.5% of the key. The `PluginLicenseInfo::toArray()` does the opposite (shows first 8, hides rest). Inconsistent redaction strategies.

### M7. Wizard `bin/yaycommerce-init` — no slug sanitization

**File:** `bin/yaycommerce-init:202`

User-provided slug is used directly in file content generation (adapter class, scoper config). Slug like `'; rm -rf /; echo '` would break the generated PHP file. While this is a developer-facing CLI tool (not user-facing), it's still worth sanitizing:

```php
$slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
```

---

## Edge Cases Identified

### E1. Three plugins, all same admin-shell version, different scoped copies

When all three have the same `VERSION`, `version_compare($data['version'], $winner_ver, '>')` uses strict greater-than. Result: the first copy iterated in `$GLOBALS['yaycommerce_admin_shell_versions']` wins (PHP array insertion order). This is deterministic but depends on plugin load order, which WordPress does not guarantee across updates.

### E2. `VersionedLoader` vs `AdminShell::boot()` — two election mechanisms

`VersionedLoader` handles pre-scoper election (same namespace), `AdminShell::boot()` handles post-scoper election (different namespaces). If a site has BOTH scoped and unscoped copies, only one election mechanism runs per copy. This appears intentional and correct.

### E3. License check during `plugins_loaded` — options table not yet fully loaded

`LicenseHandler` constructor creates a `License` object (reads options) during `plugins_loaded`. This is fine — `get_option()` works at this point. No issue.

### E4. `PluginSubmenu` redirect — all unlicensed pro plugins redirect to same Licenses page

Correct behavior. The redirect is per-submenu-click, not global. No cross-plugin confusion.

### E5. `home_url()` in license activation URL — multisite considerations

`LicenseAPI` sends `home_url()` to EDD. On multisite with domain mapping, `home_url()` returns the mapped domain. If the domain changes (migration), the existing activation is tied to the old domain. The cron check would then fail and auto-remove the license (see H2).

---

## Dead Code

1. `LicenseHandler::do_post_requests()` — empty method, no-op (acknowledged in comment)
2. `LicenseHandler::custom_schedules()` — registers `3hours` schedule never used
3. `LicenseHandler::is_license_inactive()` — no internal callers found (may be used by consuming plugins)
4. `MenuSuppressor` — default constructor passes empty slug list; suppressor does nothing unless overridden

---

## PHP 7.4 Compatibility

No issues found. The codebase uses:

- Typed properties (PHP 7.4+) -- OK
- Null coalescing (`??`) -- OK (PHP 7.0+)
- Nullable types (`?self`, `?int`) -- OK (PHP 7.1+)
- `match` expressions -- NOT used (would require PHP 8.0)
- Union types -- NOT used (would require PHP 8.0)

**PHP 7.4 compatible.**

---

## Positive Observations

1. **Clean version election design** — the global registry + highest-version-wins pattern is well thought out and handles the scoped-copies problem elegantly.
2. **Proper escaping throughout views** — `esc_html()`, `esc_attr()`, `esc_url()` used consistently.
3. **Adapter pattern** — clean separation between shell and consuming plugins. Adding new plugins requires only implementing the interface.
4. **Legacy bridge** — smooth migration path from old filter-based registration.
5. **`toArray()` redacts license key** — PII protection for REST/JS consumers.
6. **Validation in `validate_adapter()`** — catches misconfigured adapters early with clear error messages.

---

## Summary

The package is well-architected with a clean adapter pattern and solid version election mechanism. The main concerns are:

1. **C3 (CRITICAL):** Missing capability checks in RecommendedPluginsPage AJAX handlers = privilege escalation
2. **C2 (CRITICAL):** Cross-scope registry merge can drop pro plugin's license entry when lite copy wins
3. **H1-H2 (HIGH):** License removal is fire-and-forget, and auto-removal on check failure is too aggressive — risks customer data loss
4. **H3 (HIGH):** Cron hooks accumulate across deactivation/reactivation cycles

C3 is a security vulnerability that should be fixed before any release. C2 is a correctness issue that manifests only when pro+lite of the same plugin are both active. H1/H2 are customer-impact issues that should be addressed soon.
