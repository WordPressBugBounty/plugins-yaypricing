<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\Registry\PluginLicenseInfo;

/**
 * Regression test for C3: PluginLicenseInfo must not throw when toArray()
 * is called on an instance with no properties explicitly set.
 *
 * Before the fix, all 14 typed properties were uninitialized. Any access
 * (including in toArray()) would throw:
 *   Error: Typed property PluginLicenseInfo::$slug must not be accessed
 *   before initialization.
 *
 * This scenario is reachable via the yaycommerce_admin_shell_plugin_info filter,
 * which exposes PluginLicenseInfo to third-party code that may create partial instances.
 */
class PluginLicenseInfoDefaultsTest extends TestCase {

    /**
     * A freshly-constructed instance with no property assignment must not throw.
     */
    public function test_toarray_on_empty_instance_does_not_throw(): void {
        $info = new PluginLicenseInfo();

        // Must not throw Error: Typed property must not be accessed before initialization
        $array = $info->toArray();

        $this->assertIsArray( $array );
    }

    /**
     * Verify default values match the expected safe defaults.
     */
    public function test_default_values_are_safe(): void {
        $info  = new PluginLicenseInfo();
        $array = $info->toArray();

        $this->assertSame( '', $array['slug'] );
        $this->assertSame( '', $array['name'] );
        $this->assertSame( '', $array['version'] );
        $this->assertSame( '', $array['basename'] );
        $this->assertSame( 0, $array['item_id'] );
        $this->assertSame( '', $array['store_link'] );
        $this->assertSame( 'unknown', $array['status'] );
        $this->assertNull( $array['expires_at'] );
        $this->assertSame( 0, $array['activations_used'] );
        $this->assertSame( 0, $array['activations_limit'] );
        $this->assertFalse( $array['is_legacy'] );
        // license_key is redacted to '' when empty
        $this->assertSame( '', $array['license_key'] );
    }

    /**
     * Verify that partial assignment (only some fields set) also does not throw.
     * Models the yaycommerce_admin_shell_plugin_info filter mutating only one field.
     */
    public function test_partial_assignment_does_not_throw(): void {
        $info         = new PluginLicenseInfo();
        $info->status = 'override'; // partial mutation, like a filter might do

        $array = $info->toArray();

        $this->assertSame( 'override', $array['status'] );
        $this->assertSame( '', $array['slug'] ); // other fields still have defaults
    }
}
