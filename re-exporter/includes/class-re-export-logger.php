<?php
/**
 * Manages the export log DB table — creation, insertion, retrieval.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Export_Logger
 */
class Export_Logger {

	const SCHEMA_VERSION    = '2';
	const SCHEMA_OPTION_KEY = 're_exporter_db_version';

	// =========================================================================
	// Schema
	// =========================================================================

	/**
	 * Create or upgrade the log table using dbDelta.
	 * Safe to call on every activation.
	 */
	public static function create_table() {
		global $wpdb;

		$table   = $wpdb->prefix . 're_exporter_logs';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id            BIGINT(20)   NOT NULL AUTO_INCREMENT,
			export_date   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			platform      VARCHAR(20)  NOT NULL DEFAULT '',
			post_type     VARCHAR(100) NOT NULL DEFAULT '',
			record_count  INT(11)      NOT NULL DEFAULT 0,
			filename      VARCHAR(255) NOT NULL DEFAULT '',
			file_path     VARCHAR(500) NOT NULL DEFAULT '',
			user_id       BIGINT(20)   NOT NULL DEFAULT 0,
			PRIMARY KEY  (id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::SCHEMA_OPTION_KEY, self::SCHEMA_VERSION );
	}

	// =========================================================================
	// Write
	// =========================================================================

	/**
	 * Insert a log entry.
	 *
	 * @param array $data {
	 *   @type string $platform      'olx' | 'alo'
	 *   @type string $post_type     CPT slug
	 *   @type int    $record_count  Number of exported posts
	 *   @type string $filename      Generated filename
	 *   @type string $file_path     Absolute server path to the file
	 *   @type int    $user_id       WP user ID
	 * }
	 */
	public static function log( array $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 're_exporter_logs',
			array(
				'platform'     => sanitize_text_field( isset( $data['platform'] )     ? $data['platform']     : '' ),
				'post_type'    => sanitize_text_field( isset( $data['post_type'] )    ? $data['post_type']    : '' ),
				'record_count' => absint( isset( $data['record_count'] )              ? $data['record_count'] : 0 ),
				'filename'     => sanitize_file_name( isset( $data['filename'] )      ? $data['filename']     : '' ),
				'file_path'    => sanitize_text_field( isset( $data['file_path'] )    ? $data['file_path']    : '' ),
				'user_id'      => absint( isset( $data['user_id'] )                   ? $data['user_id']      : 0 ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d' )
		);
	}

	// =========================================================================
	// Read
	// =========================================================================

	/**
	 * Retrieve the most recent log entries.
	 *
	 * @param int $limit Maximum rows (default 20).
	 * @return array[]   Associative rows.
	 */
	public static function get_logs( $limit = 20 ) {
		global $wpdb;

		$table = $wpdb->prefix . 're_exporter_logs';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY export_date DESC LIMIT %d",
				absint( $limit )
			),
			ARRAY_A
		);
	}
}
