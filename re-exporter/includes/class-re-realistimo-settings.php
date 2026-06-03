<?php
/**
 * Scoped access to Realistimo configuration.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Realistimo_Settings
 */
class Realistimo_Settings {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_agency() {
		return $this->settings->get_realistimo_agency();
	}

	public function get_broker() {
		return $this->settings->get_realistimo_broker();
	}

	public function get_field_map() {
		return $this->settings->get_realistimo_field_map();
	}

	public function get_value_map() {
		return $this->settings->get_realistimo_value_map();
	}

	public function get_location_label_map() {
		return $this->settings->get_realistimo_location_label_map();
	}

	public function get_category_tax() {
		return $this->settings->get_realistimo_category_tax();
	}

	public function get_category_defaults() {
		return $this->settings->get_realistimo_category_defaults();
	}

	/**
	 * @param array $post
	 * @return void
	 */
	public function save( array $post ) {
		$this->settings->save_realistimo_settings( $post );
	}
}
