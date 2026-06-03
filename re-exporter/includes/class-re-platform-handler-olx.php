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

	/** @var OLX_Category_Resolver */
	private $category_resolver;

	/**
	 * @param Settings              $settings
	 * @param OLX_Template          $template
	 * @param OLX_Category_Resolver $category_resolver
	 */
	public function __construct( Settings $settings, OLX_Template $template, OLX_Category_Resolver $category_resolver ) {
		$this->settings          = $settings;
		$this->template          = $template;
		$this->category_resolver = $category_resolver;
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
		$exporter = new Exporter_OLX( $this->settings, $this->template, $this->category_resolver );
		$result   = $exporter->generate( $request->get_post_ids(), $request->get_output_directory() );

		return is_wp_error( $result ) ? $result : Export_Result::from_legacy_files( $result );
	}
}
