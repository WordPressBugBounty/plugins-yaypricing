<?php
/**
 * Request-scoped cache service for YayPricing.
 *
 * Holds memoized values for the duration of a single request, grouped into named
 * buckets (rules, applicability, taxonomy, price, products). Storage is a plain
 * in-memory array, so everything is discarded when the request ends. Buckets are
 * reset on the `yaydp_clear_cache` action so an admin save-then-render within one
 * request cannot serve stale data.
 *
 * This is deliberately not persistent: prices vary per visitor (role, currency,
 * tax display) and rules change on save, so cross-request persistence would risk
 * serving one visitor's price to another. Request scope removes the in-page
 * O(products x variations x rules) recomputation without that risk.
 *
 * @since 3.5.8
 *
 * @package YayPricing\Core\Caches
 */

namespace YAYDP\Core\Caches;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Request_Cache {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Cached values grouped by bucket name.
	 *
	 * Shape: array( bucket_name => array( key => value ) ).
	 *
	 * @var array
	 */
	private $buckets = array();

	/**
	 * Register the flush hook so stored values are dropped when the plugin
	 * signals that cached data is no longer valid (e.g. after a rule save).
	 */
	protected function __construct() {
		add_action( 'yaydp_clear_cache', array( $this, 'flush' ) );
	}

	/**
	 * Whether a key exists in a bucket.
	 *
	 * Uses array_key_exists so a legitimately cached null/false (e.g. "rule does
	 * not apply") is treated as a hit, not a miss.
	 *
	 * @param string $bucket Bucket name.
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function has( $bucket, $key ) {
		return isset( $this->buckets[ $bucket ] ) && array_key_exists( $key, $this->buckets[ $bucket ] );
	}

	/**
	 * Read a cached value.
	 *
	 * @param string $bucket Bucket name.
	 * @param string $key Cache key.
	 * @param mixed  $default Value returned when the key is absent.
	 * @return mixed
	 */
	public function get( $bucket, $key, $default = null ) {
		return $this->has( $bucket, $key ) ? $this->buckets[ $bucket ][ $key ] : $default;
	}

	/**
	 * Store a value.
	 *
	 * @param string $bucket Bucket name.
	 * @param string $key Cache key.
	 * @param mixed  $value Value to store.
	 * @return mixed The stored value (for convenient inline use).
	 */
	public function set( $bucket, $key, $value ) {
		if ( ! isset( $this->buckets[ $bucket ] ) ) {
			$this->buckets[ $bucket ] = array();
		}
		$this->buckets[ $bucket ][ $key ] = $value;
		return $value;
	}

	/**
	 * Return the cached value for a key, computing and storing it on a miss.
	 *
	 * @param string   $bucket Bucket name.
	 * @param string   $key Cache key.
	 * @param callable $callback Producer invoked only on a cache miss.
	 * @return mixed
	 */
	public function remember( $bucket, $key, $callback ) {
		if ( $this->has( $bucket, $key ) ) {
			return $this->buckets[ $bucket ][ $key ];
		}
		return $this->set( $bucket, $key, \call_user_func( $callback ) );
	}

	/**
	 * Drop cached values.
	 *
	 * @param string|null $bucket Bucket to clear, or null to clear everything.
	 */
	public function flush( $bucket = null ) {
		if ( is_null( $bucket ) ) {
			$this->buckets = array();
			return;
		}
		unset( $this->buckets[ $bucket ] );
	}
}
