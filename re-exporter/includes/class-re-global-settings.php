<?php
/**
 * Scoped access to global plugin settings.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Global_Settings
 */
class Global_Settings {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return string
	 */
	public function get_post_type() {
		return $this->settings->get_post_type();
	}

	/**
	 * @return bool
	 */
	public function get_only_published() {
		return $this->settings->get_only_published();
	}

	/**
	 * @return bool
	 */
	public function get_use_per_post_flag() {
		return $this->settings->get_use_per_post_flag();
	}

	/**
	 * @return string
	 */
	public function get_olx_country() {
		return $this->settings->get_olx_country();
	}

	/**
	 * @return string
	 */
	public function get_olx_country_base_url() {
		return $this->settings->get_olx_country_base_url();
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public function get_shared_field_definitions() {
		return $this->settings->get_shared_field_definitions();
	}

	/**
	 * @return array<string,string>
	 */
	public function get_shared_field_map() {
		return $this->settings->get_shared_field_map();
	}

	/**
	 * @param array $post
	 * @return void
	 */
	public function save( array $post ) {
		$this->settings->save_global_settings( $post );
	}
}
