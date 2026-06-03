<?php
/**
 * Registry that centralizes platform discovery and handler lookup.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Platform_Registry
 */
class Platform_Registry {

	/** @var array<string,Platform_Handler_Interface> */
	private $handlers = array();

	/**
	 * @param Platform_Handler_Interface[] $handlers
	 */
	public function __construct( array $handlers ) {
		foreach ( $handlers as $handler ) {
			if ( ! $handler instanceof Platform_Handler_Interface ) {
				continue;
			}

			$definition                                 = $handler->get_definition();
			$this->handlers[ $definition->get_code() ] = $handler;
		}
	}

	/**
	 * @return string[]
	 */
	public function get_codes() {
		return array_keys( $this->handlers );
	}

	/**
	 * @param string $platform
	 * @return bool
	 */
	public function has( $platform ) {
		return isset( $this->handlers[ sanitize_key( (string) $platform ) ] );
	}

	/**
	 * @param string $platform
	 * @return Platform_Handler_Interface|null
	 */
	public function get_handler( $platform ) {
		$platform = sanitize_key( (string) $platform );

		return isset( $this->handlers[ $platform ] ) ? $this->handlers[ $platform ] : null;
	}

	/**
	 * @param string $platform
	 * @return string
	 */
	public function get_label( $platform ) {
		$handler = $this->get_handler( $platform );

		return $handler ? $handler->get_definition()->get_label() : '';
	}
}
