<?php
/**
 * Shared exporter utilities for filesystem writes and result descriptors.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Abstract_Exporter
 */
abstract class Abstract_Exporter {

	/**
	 * @param string $platform
	 * @param string $out_dir
	 * @param string $filename
	 * @param int    $count
	 * @param string $category_name
	 * @param bool   $link_only
	 * @return array<string,mixed>
	 */
	protected function build_run_file_descriptor( $platform, $out_dir, $filename, $count, $category_name = '', $link_only = false ) {
		$upload   = wp_upload_dir();
		$base_url = trailingslashit( $upload['baseurl'] ) . 're-exporter/' . $platform . '/';
		$run_id   = basename( $out_dir );

		return array(
			'filename'      => $filename,
			'filepath'      => trailingslashit( $out_dir ) . $filename,
			'url'           => $base_url . $run_id . '/' . rawurlencode( $filename ),
			'count'         => (int) $count,
			'category_name' => $category_name,
			'link_only'     => (bool) $link_only,
		);
	}

	/**
	 * @param string $platform
	 * @param string $filename
	 * @param int    $count
	 * @param string $category_name
	 * @param bool   $link_only
	 * @return array<string,mixed>
	 */
	protected function build_fixed_file_descriptor( $platform, $filename, $count, $category_name = '', $link_only = false ) {
		$upload   = wp_upload_dir();
		$base_dir = trailingslashit( $upload['basedir'] ) . 're-exporter/' . $platform;
		$base_url = trailingslashit( $upload['baseurl'] ) . 're-exporter/' . $platform;

		return array(
			'filename'      => $filename,
			'filepath'      => trailingslashit( $base_dir ) . $filename,
			'url'           => trailingslashit( $base_url ) . $filename,
			'count'         => (int) $count,
			'category_name' => $category_name,
			'link_only'     => (bool) $link_only,
		);
	}

	/**
	 * @param string $filepath
	 * @param string $content
	 * @param string $error_code
	 * @param string $message
	 * @return true|\WP_Error
	 */
	protected function write_file( $filepath, $content, $error_code, $message ) {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$dir = dirname( $filepath );
		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		if ( ! $wp_filesystem->put_contents( $filepath, $content, FS_CHMOD_FILE ) ) {
			return new \WP_Error( $error_code, sprintf( $message, $filepath ) );
		}

		return true;
	}
}
