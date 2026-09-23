<?php

namespace YayCommerce\AdminShell\Tests;

use PHPUnit\Framework\TestCase;
use YayCommerce\AdminShell\VersionedLoader;

/**
 * Tests VersionedLoader — highest version wins the election.
 */
class VersionedLoaderTest extends TestCase {

    protected function setUp(): void {
        VersionedLoader::reset();
        _test_reset_filters();
    }

    protected function tearDown(): void {
        VersionedLoader::reset();
    }

    public function test_single_candidate_runs(): void {
        $ran = false;
        VersionedLoader::register( '1.0.0', function() use ( &$ran ) {
            $ran = true;
        } );
        VersionedLoader::elect();
        $this->assertTrue( $ran );
    }

    public function test_highest_version_wins(): void {
        $winner = null;
        VersionedLoader::register( '1.0.0', function() use ( &$winner ) {
            $winner = '1.0.0';
        } );
        VersionedLoader::register( '1.2.0', function() use ( &$winner ) {
            $winner = '1.2.0';
        } );
        VersionedLoader::register( '0.9.5', function() use ( &$winner ) {
            $winner = '0.9.5';
        } );
        VersionedLoader::elect();
        $this->assertSame( '1.2.0', $winner );
    }

    public function test_election_is_idempotent(): void {
        $count = 0;
        VersionedLoader::register( '1.0.0', function() use ( &$count ) {
            $count++;
        } );
        VersionedLoader::elect();
        VersionedLoader::elect(); // Second call must be no-op
        $this->assertSame( 1, $count );
    }

    public function test_semver_comparison_correct(): void {
        $winner = null;
        VersionedLoader::register( '1.10.0', function() use ( &$winner ) {
            $winner = '1.10.0';
        } );
        VersionedLoader::register( '1.9.0', function() use ( &$winner ) {
            $winner = '1.9.0';
        } );
        VersionedLoader::elect();
        $this->assertSame( '1.10.0', $winner, '1.10.0 should beat 1.9.0 (semver, not lexicographic)' );
    }

    public function test_get_elected_version_before_election_returns_null(): void {
        VersionedLoader::register( '1.0.0', function() {} );
        // elect() not called yet
        $this->assertNull( VersionedLoader::get_elected_version() );
    }

    public function test_get_elected_version_after_election(): void {
        VersionedLoader::register( '2.1.0', function() {} );
        VersionedLoader::register( '1.0.0', function() {} );
        VersionedLoader::elect();
        $this->assertSame( '2.1.0', VersionedLoader::get_elected_version() );
    }

    /**
     * Regression test for H4: same-version collision — first registration wins.
     *
     * Before the fix, self::$candidates[$version] = $callback silently overwrote
     * the first registration with the second. This was non-deterministic under
     * multi-plugin scenarios where both ship the same admin-shell version.
     */
    public function test_same_version_first_registration_wins(): void {
        $winner = null;

        VersionedLoader::register( '1.0.0', function() use ( &$winner ) {
            $winner = 'first';
        } );
        // Second registration with the same version — must be ignored.
        VersionedLoader::register( '1.0.0', function() use ( &$winner ) {
            $winner = 'second';
        } );

        VersionedLoader::elect();

        $this->assertSame(
            'first',
            $winner,
            'When two plugins register the same version, the FIRST callback must run (first-wins, deterministic).'
        );
    }
}
