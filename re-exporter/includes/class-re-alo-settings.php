<?php
/**
 * Scoped access to ALO configuration.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ALO_Settings
 */
class ALO_Settings {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_field_map() {
		return $this->settings->get_alo_field_map();
	}

	public function get_value_map() {
		return $this->settings->get_alo_value_map();
	}

	public function get_category_tax() {
		return $this->settings->get_alo_category_tax();
	}

	public function get_category_map() {
		return $this->settings->get_alo_category_map();
	}

	public function get_required_map() {
		return $this->settings->get_alo_required_map();
	}

	public function get_deal_type_field() {
		return $this->settings->get_alo_deal_type_field();
	}

	public function get_deal_type_map() {
		return $this->settings->get_alo_deal_type_map();
	}

	public function get_category_rent_map() {
		return $this->settings->get_alo_category_rent_map();
	}

	public function get_location_label_map() {
		return $this->settings->get_alo_location_label_map();
	}

	public function get_contacts() {
		return $this->settings->get_alo_contacts();
	}

	/**
	 * @param array $post
	 * @return void
	 */
	public function save( array $post ) {
		$this->settings->save_alo_settings( $post );
	}
}
