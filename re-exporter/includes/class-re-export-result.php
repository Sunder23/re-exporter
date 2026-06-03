<?php
/**
 * Export result aggregate shared by platform handlers.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Export_Result
 */
class Export_Result {

	/** @var Export_File_Result[] */
	private $files;

	/**
	 * @param Export_File_Result[] $files
	 */
	public function __construct( array $files ) {
		$this->files = $files;
	}

	/**
	 * @param array<int,array<string,mixed>> $legacy_files
	 * @return self
	 */
	public static function from_legacy_files( array $legacy_files ) {
		$files = array();

		foreach ( $legacy_files as $file ) {
			$files[] = new Export_File_Result( $file );
		}

		return new self( $files );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function to_legacy_files() {
		return array_map(
			static function ( Export_File_Result $file ) {
				return $file->to_array();
			},
			$this->files
		);
	}
}
