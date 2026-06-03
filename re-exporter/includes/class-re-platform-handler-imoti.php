<?php
/**
 * imoti.net export handler adapter.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Platform_Handler_Imoti
 */
class Platform_Handler_Imoti implements Platform_Handler_Interface {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return Platform_Definition
	 */
	public function get_definition() {
		return new Platform_Definition( 'imoti', 'imoti.net' );
	}

	/**
	 * @param Export_Request $request
	 * @return Export_Result|\WP_Error
	 */
	public function export( Export_Request $request ) {
		$exporter = new Exporter_Imoti( $this->settings );
		$result   = $exporter->generate( $request->get_post_ids(), $request->get_output_directory() );

		return is_wp_error( $result ) ? $result : Export_Result::from_legacy_files( $result );
	}
}
