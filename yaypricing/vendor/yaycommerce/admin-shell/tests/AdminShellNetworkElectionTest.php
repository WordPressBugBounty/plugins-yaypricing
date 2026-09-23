<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\AdminShell;

/**
 * Regression test for the mixed-version Network Admin bug.
 *
 * The version-election hook was wired once by whichever scoped copy booted
 * first. When that copy was ≤2.6.x it bound `admin_menu` only (no network
 * support), and the first-boot guard blocked any newer copy from adding the
 * missing `network_admin_menu` binding — so Network Admin stayed dead in
 * mixed-version installs. The fix wires the network election via a dedicated
 * flag, independent of the first-boot guard.
 */
class AdminShellNetworkElectionTest extends TestCase {

    protected function setUp(): void {
        _test_reset_filters(); // clears $GLOBALS['_wp_actions']
        AdminShell::reset();
    }

    protected function tearDown(): void {
        AdminShell::reset();
        _test_reset_filters();
    }

    /** Priorities at which elect_version is bound to a given hook. */
    private function election_priorities( string $hook ): array {
        $found = [];
        foreach ( $GLOBALS['_wp_actions'][ $hook ] ?? [] as $action ) {
            if ( is_array( $action['callback'] ) && ( $action['callback'][1] ?? '' ) === 'elect_version' ) {
                $found[] = $action['priority'];
            }
        }
        return $found;
    }

    /**
     * Single (first) boot: count==1, so bind_menu() wires BOTH hooks and the
     * count-guard sets the network flag. Covers the bind_menu path + flag-set
     * (the safety-net block is intentionally skipped here — see the dedup test).
     */
    public function test_single_boot_wires_election_on_both_hooks_and_sets_flag(): void {
        AdminShell::boot();

        $this->assertSame( [ 8 ], $this->election_priorities( 'admin_menu' ) );
        $this->assertSame( [ 8 ], $this->election_priorities( 'network_admin_menu' ) );
        $this->assertTrue( $GLOBALS['yaycommerce_network_election_wired'] );
    }

    public function test_network_election_wired_even_when_older_copy_won_first_boot(): void {
        // Simulate an older copy having booted first: a version entry already
        // present, so THIS copy sees count==2 and skips the admin_menu guard.
        // The older copy predates network support, so it left the flag unset.
        $GLOBALS['yaycommerce_admin_shell_versions'] = [
            'OldPrefix' => [ 'version' => '2.6.6', 'registry' => null, 'boot_cb' => '__return_false' ],
        ];

        AdminShell::boot();

        // This copy skipped admin_menu wiring (guard) — the old copy owns it —
        // but the network safety net still binds the network election.
        $this->assertSame( [], $this->election_priorities( 'admin_menu' ) );
        $this->assertSame( [ 8 ], $this->election_priorities( 'network_admin_menu' ) );
    }

    /**
     * Two new (2.7.x) copies in one request must yield EXACTLY ONE network
     * election binding — elect_version is not idempotent, so a duplicate would
     * run do_shell_registration twice. Models a first new copy that already
     * booted (flag set + its network@8 binding present), then boots the second.
     */
    public function test_second_new_copy_does_not_duplicate_network_election(): void {
        // State left by a first 2.7.x copy: it registered itself, wired the
        // network election once (via bind_menu), and set the flag.
        $GLOBALS['yaycommerce_admin_shell_versions'] = [
            'FirstNew' => [ 'version' => '2.7.1', 'registry' => null, 'boot_cb' => '__return_false' ],
        ];
        $GLOBALS['yaycommerce_network_election_wired']      = true;
        $GLOBALS['_wp_actions']['network_admin_menu'][]     = [
            'callback' => [ AdminShell::class, 'elect_version' ],
            'priority' => 8,
        ];

        // Second new copy boots (count becomes 2): must NOT add a duplicate.
        AdminShell::boot();

        $this->assertSame( [ 8 ], $this->election_priorities( 'network_admin_menu' ) );
    }
}
