<?php
/**
 * Runs only when the plugin is deleted from the WordPress admin.
 *
 * Drops the log table, removes all stored options, and deletes
 * the generated export files from the uploads directory.
 *
 * @package RE_Exporter
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ── Drop log table ────────────────────────────────────────────────────────────

$table = $wpdb->prefix . 're_exporter_logs';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

// ── Remove all plugin options ─────────────────────────────────────────────────

$options = array(
	're_exporter_post_type',
	're_exporter_only_published',
	're_exporter_use_per_post_flag',
	're_exporter_olx_field_map',
	're_exporter_olx_value_map',
	're_exporter_olx_category_tax',
	're_exporter_olx_category_map',
	're_exporter_olx_required_map',
	're_exporter_olx_deal_type_field',
	're_exporter_olx_deal_type_map',
	're_exporter_alo_field_map',
	're_exporter_alo_value_map',
	're_exporter_alo_category_map',
	're_exporter_db_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// ── Remove generated export files ─────────────────────────────────────────────

$upload = wp_upload_dir();
$dir    = trailingslashit( $upload['basedir'] ) . 're-exporter';

if ( is_dir( $dir ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	global $wp_filesystem;
	if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
		$wp_filesystem->delete( $dir, true );
	}
}
