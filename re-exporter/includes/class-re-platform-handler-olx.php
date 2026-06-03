<?php
/**
 * OLX export handler adapter.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Platform_Handler_OLX
 */
class Platform_Handler_OLX implements Platform_Handler_Interface {

	/** @var Settings */
	private $settings;

	/** @var OLX_Template */
	private $template;

	/**
	 * @param Settings     $settings
	 * @param OLX_Template $template
	 */
	public function __construct( Settings $settings, OLX_Template $template ) {
		$this->settings = $settings;
		$this->template = $template;
	}

	/**
	 * @return Platform_Definition
	 */
	public function get_definition() {
		return new Platform_Definition( 'olx', 'OLX' );
	}

	/**
	 * @param Export_Request $request
	 * @return Export_Result|\WP_Error
	 */
	public function export( Export_Request $request ) {
		$wizard = $request->get_context( 'wizard' );
		if ( ! $wizard instanceof Export_Wizard ) {
			return new \WP_Error( 'missing_wizard', __( 'Export runtime is incomplete for OLX.', 're-exporter' ) );
		}

		$exporter = new Exporter_OLX( $this->settings, $this->template, $wizard );
		$result   = $exporter->generate( $request->get_post_ids(), $request->get_output_directory() );

		return is_wp_error( $result ) ? $result : Export_Result::from_legacy_files( $result );
	}
}
