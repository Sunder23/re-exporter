<?php
/**
 * Coordinates platform export runs outside the AJAX controller.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Export_Run_Service
 */
class Export_Run_Service {

	/** @var Settings */
	private $settings;

	/** @var Platform_Registry */
	private $platform_registry;

	/**
	 * @param Settings          $settings
	 * @param Platform_Registry $platform_registry
	 */
	public function __construct( Settings $settings, Platform_Registry $platform_registry ) {
		$this->settings          = $settings;
		$this->platform_registry = $platform_registry;
	}

	/**
	 * @param string               $platform
	 * @param int[]                $post_ids
	 * @param array<string,mixed>  $context
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	public function run( $platform, array $post_ids, array $context = array() ) {
		$handler = $this->platform_registry->get_handler( $platform );
		if ( ! $handler ) {
			return new \WP_Error( 'invalid_platform', __( 'Export platform could not be resolved.', 're-exporter' ) );
		}

		$upload    = wp_upload_dir();
		$base_dir  = trailingslashit( $upload['basedir'] ) . 're-exporter/' . $platform;
		$timestamp = gmdate( 'Y-m-d_H-i-s' );
		$out_dir   = trailingslashit( $base_dir ) . $timestamp;
		$request   = new Export_Request( $platform, $post_ids, $out_dir, $context );
		$result    = $handler->export( $request );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$files = $result->to_legacy_files();

		$this->log_files( $platform, $post_ids, $files );

		return $files;
	}

	/**
	 * @param string                     $platform
	 * @param int[]                      $post_ids
	 * @param array<int,array<string,mixed>> $files
	 * @return void
	 */
	private function log_files( $platform, array $post_ids, array $files ) {
		$post_type = $this->settings->get_post_type();

		foreach ( $files as $file ) {
			Export_Logger::log( array(
				'platform'     => $platform,
				'post_type'    => $post_type,
				'record_count' => isset( $file['count'] ) ? $file['count'] : count( $post_ids ),
				'filename'     => $file['filename'],
				'file_path'    => isset( $file['filepath'] ) ? $file['filepath'] : '',
				'user_id'      => get_current_user_id(),
			) );
		}
	}
}
