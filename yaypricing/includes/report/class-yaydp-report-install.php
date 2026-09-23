<?php
/**
 * Create and migrate the report statistics table.
 *
 * @package YayPricing\Report
 */

namespace YAYDP\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Declare class
 */
class YAYDP_Report_Install {

	/**
	 * Bump this whenever the table structure changes.
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option holding the installed structure version.
	 */
	const DB_VERSION_OPTION = 'yaydp_report_db_version';

	/**
	 * Returns the statistics table name.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'yaydp_order_rule_stats';
	}

	/**
	 * Create or update the table when the structure version changed.
	 *
	 * The activation hook alone is not enough, because it does not reliably run
	 * when the plugin is updated in the background.
	 */
	public static function maybe_install() {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return false;
		}
		return self::create_table();
	}

	/**
	 * Create the table. Safe to call repeatedly, dbDelta is idempotent.
	 */
	public static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		/**
		 * discount_amount is nullable on purpose. NULL means the amount is not
		 * knowable ( product pricing on orders placed before it was captured ),
		 * which is a different fact from a discount of zero. Never merge the two.
		 *
		 * currency is kept so that report queries can avoid summing amounts from
		 * different currencies together on multi currency stores.
		 */
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			rule_id VARCHAR(64) NOT NULL,
			rule_type VARCHAR(20) NOT NULL,
			rule_name VARCHAR(255) NOT NULL DEFAULT '',
			discount_amount DECIMAL(19,4) NULL DEFAULT NULL,
			order_revenue DECIMAL(19,4) NOT NULL DEFAULT 0,
			order_status VARCHAR(20) NOT NULL DEFAULT '',
			currency VARCHAR(3) NOT NULL DEFAULT '',
			date_created DATETIME NOT NULL,
			date_created_gmt DATETIME NOT NULL,
			is_backfilled TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY order_rule (order_id, rule_id),
			KEY date_created (date_created),
			KEY rule_date (rule_id, date_created)
		) {$charset_collate};";

		dbDelta( $sql );

		if ( ! empty( $wpdb->last_error ) ) {
			// Leave the version option untouched so the install is retried.
			return false;
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		return true;
	}

	/**
	 * Whether the table is present.
	 */
	public static function table_exists() {
		global $wpdb;
		$table = self::get_table_name();
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
