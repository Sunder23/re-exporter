<?php
/**
 * Scoped access to imoti.net configuration.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Imoti_Settings
 */
class Imoti_Settings {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_agency_id() {
		return $this->settings->get_imoti_agency_id();
	}

	public function get_agency_title() {
		return $this->settings->get_imoti_agency_title();
	}

	public function get_broker() {
		return $this->settings->get_imoti_broker();
	}

	public function get_category_tax() {
		return $this->settings->get_imoti_category_tax();
	}

	public function get_category_defaults() {
		return $this->settings->get_imoti_category_defaults();
	}

	public function get_location_label_map() {
		return $this->settings->get_imoti_location_label_map();
	}

	public function get_field_map() {
		return $this->settings->get_imoti_field_map();
	}

	public function get_field_map_overrides() {
		return $this->settings->get_imoti_field_map_overrides();
	}

	public function get_value_map() {
		return $this->settings->get_imoti_value_map();
	}

	/**
	 * @param array $post
	 * @return void
	 */
	public function save( array $post ) {
		$this->settings->save_imoti_settings( $post );
	}
}
