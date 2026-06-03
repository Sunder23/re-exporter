<?php
/**
 * ALO export handler adapter.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Platform_Handler_ALO
 */
class Platform_Handler_ALO implements Platform_Handler_Interface {

	/** @var Settings */
	private $settings;

	/** @var ALO_Template */
	private $template;

	/**
	 * @param Settings     $settings
	 * @param ALO_Template $template
	 */
	public function __construct( Settings $settings, ALO_Template $template ) {
		$this->settings = $settings;
		$this->template = $template;
	}

	/**
	 * @return Platform_Definition
	 */
	public function get_definition() {
		return new Platform_Definition( 'alo', 'ALO' );
	}

	/**
	 * @param Export_Request $request
	 * @return Export_Result|\WP_Error
	 */
	public function export( Export_Request $request ) {
		$wizard = $request->get_context( 'wizard' );
		if ( ! $wizard instanceof Export_Wizard ) {
			return new \WP_Error( 'missing_wizard', __( 'Export runtime is incomplete for ALO.', 're-exporter' ) );
		}

		$exporter = new Exporter_ALO( $this->settings, $this->template, $wizard );
		$result   = $exporter->generate( $request->get_post_ids(), $request->get_output_directory() );

		return is_wp_error( $result ) ? $result : Export_Result::from_legacy_files( $result );
	}
}
