<?php
/**
 * Export file result value object.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Export_File_Result
 */
class Export_File_Result {

	/** @var array<string,mixed> */
	private $data;

	/**
	 * @param array<string,mixed> $data
	 */
	public function __construct( array $data ) {
		$this->data = array(
			'filename'      => isset( $data['filename'] ) ? (string) $data['filename'] : '',
			'filepath'      => isset( $data['filepath'] ) ? (string) $data['filepath'] : '',
			'url'           => isset( $data['url'] ) ? (string) $data['url'] : '',
			'count'         => isset( $data['count'] ) ? (int) $data['count'] : 0,
			'category_name' => isset( $data['category_name'] ) ? (string) $data['category_name'] : '',
			'link_only'     => ! empty( $data['link_only'] ),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return $this->data;
	}
}
