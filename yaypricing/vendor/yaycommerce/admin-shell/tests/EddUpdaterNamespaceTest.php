<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Regression test for C1: EDD_SL_Plugin_Updater namespace mismatch.
 *
 * Before the fix, EDD_SL_Plugin_Updater.php declared namespace YayMail\License,
 * while Composer PSR-4 maps src/License/ to YayCommerce\AdminShell\License\.
 * This caused a fatal "Class not found" error on every admin page load.
 */
class EddUpdaterNamespaceTest extends TestCase {

    /**
     * Verify the class is autoloadable under the correct namespace.
     * If the namespace were still YayMail\License, class_exists() would return false.
     */
    public function test_edd_updater_class_exists_under_correct_namespace(): void {
        $this->assertTrue(
            class_exists( 'YayCommerce\AdminShell\License\EDD_SL_Plugin_Updater' ),
            'EDD_SL_Plugin_Updater must be autoloadable as YayCommerce\AdminShell\License\EDD_SL_Plugin_Updater. ' .
            'Check that EDD_SL_Plugin_Updater.php declares namespace YayCommerce\AdminShell\License (not YayMail\License).'
        );
    }

    /**
     * Verify the old (wrong) namespace is not used.
     */
    public function test_edd_updater_does_not_exist_under_old_yaymail_namespace(): void {
        $this->assertFalse(
            class_exists( 'YayMail\License\EDD_SL_Plugin_Updater', false ),
            'EDD_SL_Plugin_Updater must NOT be declared under YayMail\License namespace.'
        );
    }
}
