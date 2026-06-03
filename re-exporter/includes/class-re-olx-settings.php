<?php
/**
 * Scoped access to OLX configuration.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OLX_Settings
 */
class OLX_Settings {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_field_map() {
		return $this->settings->get_olx_field_map();
	}

	public function get_value_map() {
		return $this->settings->get_olx_value_map();
	}

	public function get_city_label_map() {
		return $this->settings->get_olx_city_label_map();
	}

	public function get_category_tax() {
		return $this->settings->get_olx_category_tax();
	}

	public function get_category_map() {
		return $this->settings->get_olx_category_map();
	}

	public function get_category_rent_map() {
		return $this->settings->get_olx_category_rent_map();
	}

	public function get_required_map() {
		return $this->settings->get_olx_required_map();
	}

	public function get_deal_type_field() {
		return $this->settings->get_olx_deal_type_field();
	}

	public function get_deal_type_map() {
		return $this->settings->get_olx_deal_type_map();
	}

	/**
	 * @param array $post
	 * @return void
	 */
	public function save( array $post ) {
		$this->settings->save_olx_settings( $post );
	}
}
