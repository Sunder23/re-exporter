<?php
/**
 * Export request value object shared by platform handlers.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Export_Request
 */
class Export_Request {

	/** @var string */
	private $platform;

	/** @var int[] */
	private $post_ids;

	/** @var string */
	private $output_directory;

	/** @var array<string,mixed> */
	private $context;

	/**
	 * @param string               $platform
	 * @param int[]                $post_ids
	 * @param string               $output_directory
	 * @param array<string,mixed>  $context
	 */
	public function __construct( $platform, array $post_ids, $output_directory, array $context = array() ) {
		$this->platform         = sanitize_key( (string) $platform );
		$this->post_ids         = array_values( array_map( 'intval', $post_ids ) );
		$this->output_directory = (string) $output_directory;
		$this->context          = $context;
	}

	/**
	 * @return string
	 */
	public function get_platform() {
		return $this->platform;
	}

	/**
	 * @return int[]
	 */
	public function get_post_ids() {
		return $this->post_ids;
	}

	/**
	 * @return string
	 */
	public function get_output_directory() {
		return $this->output_directory;
	}

	/**
	 * Transitional context bag used while callers are moved off wizard internals.
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get_context( $key, $default = null ) {
		return array_key_exists( $key, $this->context ) ? $this->context[ $key ] : $default;
	}
}
