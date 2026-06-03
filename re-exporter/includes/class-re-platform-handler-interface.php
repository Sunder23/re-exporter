<?php
/**
 * Platform handler contract for export orchestration.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Platform_Handler_Interface
 */
interface Platform_Handler_Interface {

	/**
	 * @return Platform_Definition
	 */
	public function get_definition();

	/**
	 * @param Export_Request $request
	 * @return Export_Result|\WP_Error
	 */
	public function export( Export_Request $request );
}
