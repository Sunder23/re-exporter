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

	/** @var ALO_Category_Resolver */
	private $category_resolver;

	/**
	 * @param Settings              $settings
	 * @param ALO_Template          $template
	 * @param ALO_Category_Resolver $category_resolver
	 */
	public function __construct( Settings $settings, ALO_Template $template, ALO_Category_Resolver $category_resolver ) {
		$this->settings          = $settings;
		$this->template          = $template;
		$this->category_resolver = $category_resolver;
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
		$exporter = new Exporter_ALO( $this->settings, $this->template, $this->category_resolver );
		$result   = $exporter->generate( $request->get_post_ids(), $request->get_output_directory() );

		return is_wp_error( $result ) ? $result : Export_Result::from_legacy_files( $result );
	}
}
