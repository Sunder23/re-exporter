<?php
/**
 * Platform metadata shared by export orchestration.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Platform_Definition
 */
class Platform_Definition {

	/** @var string */
	private $code;

	/** @var string */
	private $label;

	/**
	 * @param string $code
	 * @param string $label
	 */
	public function __construct( $code, $label ) {
		$this->code  = sanitize_key( (string) $code );
		$this->label = (string) $label;
	}

	/**
	 * @return string
	 */
	public function get_code() {
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}
}
