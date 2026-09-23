# Contributing

## Append-Only Public Contract

The following are **append-only contracts**. Once released in a MINOR version, they must
never be renamed, removed, or have their semantics changed without a MAJOR version bump:

### `LicenseConfigAdapter` interface (9 methods)

- `get_plugin_slug(): string`
- `get_plugin_name(): string`
- `get_plugin_version(): string`
- `get_plugin_file(): string`
- `get_plugin_basename(): string`
- `get_item_id(): int`
- `get_store_url(): string`
- `get_store_link(): string`
- `get_capability(): string`

New methods may be added in MINOR versions. Plugins implementing this interface
will need to implement new methods — provide a default implementation or document
the migration path clearly.

### `PluginLicenseInfo` public properties

All 14 public properties are frozen. Renaming or removing any requires a MAJOR bump.
New properties may be added in MINOR versions.

## MAJOR Version Bump Rules

A MAJOR version bump is required when:

1. Any `LicenseConfigAdapter` method is renamed, removed, or its return type changed
2. Any `PluginLicenseInfo` public property is renamed or removed
3. `AdminShell::boot()` or `AdminShell::enable_license()` signature changes
4. Hook names (`yaycommerce_admin_shell_booted`, etc.) are renamed or removed
5. The REST endpoint structure (`{slug}/v1/license/*`) changes in a breaking way

## Adding New Features

- Add new methods to interfaces in MINOR version only if all existing implementations
  can be updated at the same time (or provide default via abstract class).
- Document new methods in README.md and update the hook contract table.
- Add tests for all new public API surface.

## Testing

```bash
composer install
./vendor/bin/phpunit tests/
```

All tests must pass before any PR is merged.

## PHP Version

Minimum supported: PHP 7.4. No PHP 8.0+ syntax (constructor property promotion,
named arguments, match expression in public API) until PHP 7.4 support is dropped.

## PHP-Scoper

Each consuming plugin runs PHP-Scoper with its own prefix before building a ZIP.
Do NOT assume global namespace — the package must work under any prefix.
