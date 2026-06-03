<?php
/**
 * Typed CRUD wrapper for all RE Exporter WordPress options.
 *
 * Centralises sanitisation on write and safe defaults on read.
 * All callers (Admin_Page, exporters, metaboxes) use this class
 * instead of calling get_option() / update_option() directly.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	// ── Option key constants ──────────────────────────────────────────────────

	const OPT_POST_TYPE        = 're_exporter_post_type';
	const OPT_ONLY_PUBLISHED   = 're_exporter_only_published';
	const OPT_USE_FLAG         = 're_exporter_use_per_post_flag';

	// Country
	const OPT_OLX_COUNTRY      = 're_exporter_olx_country';

	/** @var array<string,string> */
	private static $country_base_urls = array(
		'bg' => 'https://www.olx.bg',
		'pl' => 'https://www.olx.pl',
		'ro' => 'https://www.olx.ro',
		'pt' => 'https://www.olx.pt',
		'ua' => 'https://www.olx.ua',
	);

	// OLX
	const OPT_OLX_FIELD_MAP    = 're_exporter_olx_field_map';
	const OPT_OLX_VALUE_MAP    = 're_exporter_olx_value_map';
	const OPT_OLX_CATEGORY_TAX = 're_exporter_olx_category_tax';
	const OPT_OLX_CATEGORY_MAP = 're_exporter_olx_category_map';
	const OPT_OLX_REQUIRED_MAP = 're_exporter_olx_required_map';
	const OPT_OLX_DEAL_FIELD        = 're_exporter_olx_deal_type_field';
	const OPT_OLX_DEAL_MAP          = 're_exporter_olx_deal_type_map';
	const OPT_OLX_CATEGORY_RENT_MAP = 're_exporter_olx_category_rent_map';

	// OLX city label cache (persists resolved city ID → display label for settings page)
	const OPT_OLX_CITY_LABEL_MAP = 're_exporter_olx_city_label_map';

	// ALO
	const OPT_ALO_FIELD_MAP    = 're_exporter_alo_field_map';
	const OPT_ALO_VALUE_MAP    = 're_exporter_alo_value_map';
	const OPT_ALO_CATEGORY_TAX = 're_exporter_alo_category_tax';
	const OPT_ALO_CATEGORY_MAP = 're_exporter_alo_category_map';
	const OPT_ALO_REQUIRED_MAP = 're_exporter_alo_required_map';
	const OPT_ALO_DEAL_FIELD        = 're_exporter_alo_deal_type_field';
	const OPT_ALO_DEAL_MAP          = 're_exporter_alo_deal_type_map';
	const OPT_ALO_CATEGORY_RENT_MAP = 're_exporter_alo_category_rent_map';
	const OPT_ALO_LOCATION_LABEL_MAP = 're_exporter_alo_location_label_map';
	const OPT_ALO_CONTACTS           = 're_exporter_alo_contacts';

	// Realistimo
	const OPT_REALISTIMO_AGENCY           = 're_exporter_realistimo_agency';
	const OPT_REALISTIMO_BROKER           = 're_exporter_realistimo_broker';
	const OPT_REALISTIMO_FIELD_MAP        = 're_exporter_realistimo_field_map';
	const OPT_REALISTIMO_VALUE_MAP        = 're_exporter_realistimo_value_map';
	const OPT_REALISTIMO_LOCATION_LABEL_MAP  = 're_exporter_realistimo_location_label_map';
	const OPT_REALISTIMO_CATEGORY_TAX        = 're_exporter_realistimo_category_tax';
	const OPT_REALISTIMO_CATEGORY_DEFAULTS   = 're_exporter_realistimo_category_defaults';

	// Imoti
	const OPT_IMOTI_AGENCY_ID    = 're_exporter_imoti_agency_id';
	const OPT_IMOTI_AGENCY_TITLE = 're_exporter_imoti_agency_title';
	const OPT_IMOTI_BROKER       = 're_exporter_imoti_broker';
	const OPT_IMOTI_FIELD_MAP         = 're_exporter_imoti_field_map';
	const OPT_IMOTI_VALUE_MAP         = 're_exporter_imoti_value_map';
	const OPT_IMOTI_CATEGORY_TAX        = 're_exporter_imoti_category_tax';
	const OPT_IMOTI_CATEGORY_DEFAULTS   = 're_exporter_imoti_category_defaults';
	const OPT_IMOTI_LOCATION_LABEL_MAP  = 're_exporter_imoti_location_label_map';

	// =========================================================================
	// Global Settings
	// =========================================================================

	/**
	 * @return string  CPT slug, or '' if not yet configured.
	 */
	public function get_post_type() {
		return sanitize_key( (string) get_option( self::OPT_POST_TYPE, '' ) );
	}

	/**
	 * @return bool  Default true.
	 */
	public function get_only_published() {
		$v = get_option( self::OPT_ONLY_PUBLISHED, '1' );
		return '1' === (string) $v || true === $v;
	}

	/**
	 * @return bool  Default true.
	 */
	public function get_use_per_post_flag() {
		$v = get_option( self::OPT_USE_FLAG, '1' );
		return '1' === (string) $v || true === $v;
	}

	/**
	 * @return string  Country code: 'bg' | 'pl' | 'ro' | 'pt' | 'ua'. Default 'bg'.
	 */
	public function get_olx_country() {
		$c = sanitize_key( (string) get_option( self::OPT_OLX_COUNTRY, 'bg' ) );
		return array_key_exists( $c, self::$country_base_urls ) ? $c : 'bg';
	}

	/**
	 * @return string  Base URL for the configured OLX country portal.
	 */
	public function get_olx_country_base_url() {
		return self::$country_base_urls[ $this->get_olx_country() ];
	}

	/**
	 * Validate and persist Section A (Global Settings).
	 *
	 * @param array $post  Raw $_POST data.
	 * @return void
	 */
	public function save_global_settings( array $post ) {
		$post_type = isset( $post['re_post_type'] )
			? sanitize_key( wp_unslash( $post['re_post_type'] ) )
			: '';

		// Validate that the post type actually exists and is public.
		if ( $post_type ) {
			$cpt = get_post_type_object( $post_type );
			if ( ! $cpt || ! $cpt->public ) {
				$post_type = '';
			}
		}

		$country = isset( $post['re_olx_country'] )
			? sanitize_key( wp_unslash( $post['re_olx_country'] ) )
			: 'bg';
		if ( ! array_key_exists( $country, self::$country_base_urls ) ) {
			$country = 'bg';
		}

		update_option( self::OPT_POST_TYPE,      $post_type );
		update_option( self::OPT_ONLY_PUBLISHED, isset( $post['re_only_published'] )   ? '1' : '0' );
		update_option( self::OPT_USE_FLAG,        isset( $post['re_use_per_post_flag'] ) ? '1' : '0' );
		update_option( self::OPT_OLX_COUNTRY,    $country );
	}

	// =========================================================================
	// OLX Settings — getters
	// =========================================================================

	/**
	 * @return array<string,string>  [ csv_column => source_key ]
	 */
	public function get_olx_field_map() {
		return $this->get_array_option( self::OPT_OLX_FIELD_MAP );
	}

	/**
	 * @return array<string,array<string,string>>  [ csv_column => [ wp_value => olx_value ] ]
	 */
	public function get_olx_value_map() {
		return $this->get_array_option( self::OPT_OLX_VALUE_MAP );
	}

	/**
	 * Persisted city/district labels for the settings page value-override table.
	 *
	 * @return array<string,array<string,string>>  [ csv_column => [ wp_value => display_label ] ]
	 */
	public function get_olx_city_label_map() {
		return $this->get_array_option( self::OPT_OLX_CITY_LABEL_MAP );
	}

	/**
	 * @return string  Taxonomy slug used for OLX category mapping, or ''.
	 */
	public function get_olx_category_tax() {
		return sanitize_key( (string) get_option( self::OPT_OLX_CATEGORY_TAX, '' ) );
	}

	/**
	 * @return array<string,string>  [ term_slug => subcategory_id ]
	 */
	public function get_olx_category_map() {
		return $this->get_array_option( self::OPT_OLX_CATEGORY_MAP );
	}

	/**
	 * @return array<string,string>  [ term_slug => subcategory_id ] for rent routing
	 */
	public function get_olx_category_rent_map() {
		return $this->get_array_option( self::OPT_OLX_CATEGORY_RENT_MAP );
	}

	/**
	 * @return array<string,array<string,string>>  [ subcategory_id => [ field_key => source_key ] ]
	 */
	public function get_olx_required_map() {
		return $this->get_array_option( self::OPT_OLX_REQUIRED_MAP );
	}

	/**
	 * @return string  Source key for deal-type routing, or '' / '__always_sales__' / '__always_rent__'.
	 */
	public function get_olx_deal_type_field() {
		return sanitize_text_field( (string) get_option( self::OPT_OLX_DEAL_FIELD, '' ) );
	}

	/**
	 * @return array<string,string>  [ wp_value => 'sales'|'rent' ]
	 */
	public function get_olx_deal_type_map() {
		return $this->get_array_option( self::OPT_OLX_DEAL_MAP );
	}

	// =========================================================================
	// OLX Settings — save
	// =========================================================================

	/**
	 * Validate and persist all OLX Settings (B1 – B5).
	 *
	 * Expected $_POST keys:
	 *   olx_field_map        array  [col] => source_key
	 *   olx_value_map        array  [col][wp_value] => olx_value
	 *   olx_category_tax     string taxonomy slug
	 *   olx_category_map     array  [term_slug] => subcategory_id
	 *   olx_required_map     array  [subcat_id][field_key] => source_key
	 *   olx_deal_type_field  string source key
	 *   olx_deal_type_map    array  [wp_value] => 'sales'|'rent'
	 *
	 * @param array $post  Raw $_POST data.
	 * @return void
	 */
	public function save_olx_settings( array $post ) {

		// B1 — Field Map
		$field_map = array();
		if ( ! empty( $post['olx_field_map'] ) && is_array( $post['olx_field_map'] ) ) {
			foreach ( $post['olx_field_map'] as $col => $source ) {
				$col              = sanitize_text_field( wp_unslash( $col ) );
				$source           = sanitize_text_field( wp_unslash( $source ) );
				$field_map[ $col ] = $source;
			}
		}
		update_option( self::OPT_OLX_FIELD_MAP, $field_map );

		// B2 — Value Map
		$value_map = array();
		if ( ! empty( $post['olx_value_map'] ) && is_array( $post['olx_value_map'] ) ) {
			foreach ( $post['olx_value_map'] as $col => $mappings ) {
				if ( ! is_array( $mappings ) ) {
					continue;
				}
				$col                = sanitize_text_field( wp_unslash( $col ) );
				$value_map[ $col ]  = array();
				foreach ( $mappings as $wp_val => $olx_val ) {
					$wp_val                        = sanitize_text_field( wp_unslash( $wp_val ) );
					$olx_val                       = sanitize_text_field( wp_unslash( $olx_val ) );
					$value_map[ $col ][ $wp_val ]  = $olx_val;
				}
			}
		}
		update_option( self::OPT_OLX_VALUE_MAP, $value_map );

		// B2 — City Label Map (display labels for city/district widgets; avoids per-load resolve AJAX)
		$city_label_map = array();
		if ( ! empty( $post['olx_city_label_map'] ) && is_array( $post['olx_city_label_map'] ) ) {
			foreach ( $post['olx_city_label_map'] as $col => $mappings ) {
				if ( ! is_array( $mappings ) ) {
					continue;
				}
				$col                     = sanitize_text_field( wp_unslash( $col ) );
				$city_label_map[ $col ]  = array();
				foreach ( $mappings as $wp_val => $label ) {
					$wp_val                              = sanitize_text_field( wp_unslash( $wp_val ) );
					$label                               = sanitize_text_field( wp_unslash( $label ) );
					$city_label_map[ $col ][ $wp_val ]   = $label;
				}
			}
		}
		update_option( self::OPT_OLX_CITY_LABEL_MAP, $city_label_map );

		// B3 — Category Taxonomy + Map
		$cat_tax = isset( $post['olx_category_tax'] )
			? sanitize_key( wp_unslash( $post['olx_category_tax'] ) )
			: '';
		update_option( self::OPT_OLX_CATEGORY_TAX, $cat_tax );

		$cat_map = array();
		if ( ! empty( $post['olx_category_map'] ) && is_array( $post['olx_category_map'] ) ) {
			foreach ( $post['olx_category_map'] as $term_slug => $subcat_id ) {
				$term_slug            = sanitize_text_field( wp_unslash( $term_slug ) );
				$subcat_id            = sanitize_text_field( wp_unslash( $subcat_id ) );
				$cat_map[ $term_slug ] = $subcat_id;
			}
		}
		update_option( self::OPT_OLX_CATEGORY_MAP, $cat_map );

		$cat_rent_map = array();
		if ( ! empty( $post['olx_category_rent_map'] ) && is_array( $post['olx_category_rent_map'] ) ) {
			foreach ( $post['olx_category_rent_map'] as $term_slug => $subcat_id ) {
				$term_slug                    = sanitize_text_field( wp_unslash( $term_slug ) );
				$subcat_id                    = sanitize_text_field( wp_unslash( $subcat_id ) );
				$cat_rent_map[ $term_slug ]   = $subcat_id;
			}
		}
		update_option( self::OPT_OLX_CATEGORY_RENT_MAP, $cat_rent_map );

		// B4 — Required Map (per-category supplemental field mappings)
		$req_map = array();
		if ( ! empty( $post['olx_required_map'] ) && is_array( $post['olx_required_map'] ) ) {
			foreach ( $post['olx_required_map'] as $subcat_id => $field_mappings ) {
				if ( ! is_array( $field_mappings ) ) {
					continue;
				}
				$subcat_id             = sanitize_text_field( wp_unslash( $subcat_id ) );
				$req_map[ $subcat_id ] = array();
				foreach ( $field_mappings as $field_key => $source ) {
					$field_key                            = sanitize_text_field( wp_unslash( $field_key ) );
					$source                               = sanitize_text_field( wp_unslash( $source ) );
					$req_map[ $subcat_id ][ $field_key ]  = $source;
				}
			}
		}
		update_option( self::OPT_OLX_REQUIRED_MAP, $req_map );

		// B5 — Deal Type Field + Map
		$deal_field = isset( $post['olx_deal_type_field'] )
			? sanitize_text_field( wp_unslash( $post['olx_deal_type_field'] ) )
			: '';
		update_option( self::OPT_OLX_DEAL_FIELD, $deal_field );

		$deal_map = array();
		if ( ! empty( $post['olx_deal_type_map'] ) && is_array( $post['olx_deal_type_map'] ) ) {
			foreach ( $post['olx_deal_type_map'] as $wp_val => $direction ) {
				$wp_val   = sanitize_text_field( wp_unslash( $wp_val ) );
				$direction = in_array( $direction, array( 'sales', 'rent' ), true )
					? $direction
					: 'sales';
				$deal_map[ $wp_val ] = $direction;
			}
		}
		update_option( self::OPT_OLX_DEAL_MAP, $deal_map );
	}

	// =========================================================================
	// ALO Settings — getters
	// =========================================================================

	/**
	 * @return array<string,string>  [ alo_field => source_key ]
	 */
	public function get_alo_field_map() {
		return $this->normalize_alo_dot_keys( $this->get_array_option( self::OPT_ALO_FIELD_MAP ) );
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	public function get_alo_value_map() {
		return $this->get_array_option( self::OPT_ALO_VALUE_MAP );
	}

	/**
	 * @return string  Taxonomy slug used for ALO category mapping, or ''.
	 */
	public function get_alo_category_tax() {
		return sanitize_key( (string) get_option( self::OPT_ALO_CATEGORY_TAX, '' ) );
	}

	/**
	 * @return array<string,string>  [ term_slug => alo_subcategory_id ]
	 */
	public function get_alo_category_map() {
		return $this->get_array_option( self::OPT_ALO_CATEGORY_MAP );
	}

	/**
	 * @return array<string,array<string,string>>  [ subcategory_id => [ field_key => source_key ] ]
	 */
	public function get_alo_required_map() {
		$raw = $this->get_array_option( self::OPT_ALO_REQUIRED_MAP );
		foreach ( $raw as $subcat_id => $fields ) {
			if ( is_array( $fields ) ) {
				$raw[ $subcat_id ] = $this->normalize_alo_dot_keys( $fields );
			}
		}
		return $raw;
	}

	public function get_alo_deal_type_field() {
		return sanitize_text_field( (string) get_option( self::OPT_ALO_DEAL_FIELD, '' ) );
	}

	public function get_alo_deal_type_map() {
		return $this->get_array_option( self::OPT_ALO_DEAL_MAP );
	}

	public function get_alo_category_rent_map() {
		return $this->get_array_option( self::OPT_ALO_CATEGORY_RENT_MAP );
	}

	/**
	 * Persisted location labels for the settings page C2 value-override table.
	 *
	 * @return array<string,array<string,string>>  [ field => [ wp_value => display_label ] ]
	 */
	public function get_alo_location_label_map() {
		return $this->get_array_option( self::OPT_ALO_LOCATION_LABEL_MAP );
	}

	/**
	 * Static contacts injected into every ALO export (C6 section).
	 *
	 * @return array{phones:string[],email:string,hide_name:string,hide_location:string,hide_address:string,hide_website:string}
	 */
	public function get_alo_contacts() {
		$saved = $this->get_array_option( self::OPT_ALO_CONTACTS );
		return array(
			'phones'        => isset( $saved['phones'] ) && is_array( $saved['phones'] )
				? array_values( array_filter( array_map( 'strval', $saved['phones'] ) ) )
				: array(),
			'email'         => isset( $saved['email'] ) ? (string) $saved['email'] : '',
			'hide_name'     => isset( $saved['hide_name'] )     ? (string) $saved['hide_name']     : '',
			'hide_location' => isset( $saved['hide_location'] ) ? (string) $saved['hide_location'] : '',
			'hide_address'  => isset( $saved['hide_address'] )  ? (string) $saved['hide_address']  : '',
			'hide_website'  => isset( $saved['hide_website'] )  ? (string) $saved['hide_website']  : '',
		);
	}

	// =========================================================================
	// ALO Settings — save
	// =========================================================================

	/**
	 * Validate and persist ALO Settings.
	 *
	 * @param array $post  Raw $_POST data.
	 * @return void
	 */
	public function save_alo_settings( array $post ) {

		// Field Map
		$field_map = array();
		if ( ! empty( $post['alo_field_map'] ) && is_array( $post['alo_field_map'] ) ) {
			foreach ( $post['alo_field_map'] as $alo_key => $source ) {
				// Restore dot notation: '|' was used in form names to avoid PHP dot→underscore conversion.
				$alo_key              = str_replace( '|', '.', sanitize_text_field( wp_unslash( $alo_key ) ) );
				$source               = sanitize_text_field( wp_unslash( $source ) );
				$field_map[ $alo_key ] = $source;
			}
		}
		update_option( self::OPT_ALO_FIELD_MAP, $field_map );

		// Value Map
		$value_map = array();
		if ( ! empty( $post['alo_value_map'] ) && is_array( $post['alo_value_map'] ) ) {
			foreach ( $post['alo_value_map'] as $col => $mappings ) {
				if ( ! is_array( $mappings ) ) {
					continue;
				}
				$col               = sanitize_text_field( wp_unslash( $col ) );
				$value_map[ $col ] = array();
				foreach ( $mappings as $wp_val => $alo_val ) {
					$wp_val                       = sanitize_text_field( wp_unslash( $wp_val ) );
					$alo_val                      = sanitize_text_field( wp_unslash( $alo_val ) );
					$value_map[ $col ][ $wp_val ] = $alo_val;
				}
			}
		}
		update_option( self::OPT_ALO_VALUE_MAP, $value_map );

		// Category Taxonomy + Map
		$cat_tax = isset( $post['alo_category_tax'] )
			? sanitize_key( wp_unslash( $post['alo_category_tax'] ) )
			: '';
		update_option( self::OPT_ALO_CATEGORY_TAX, $cat_tax );

		$cat_map = array();
		if ( ! empty( $post['alo_category_map'] ) && is_array( $post['alo_category_map'] ) ) {
			foreach ( $post['alo_category_map'] as $term_slug => $alo_cat ) {
				$term_slug            = sanitize_text_field( wp_unslash( $term_slug ) );
				$alo_cat              = sanitize_text_field( wp_unslash( $alo_cat ) );
				$cat_map[ $term_slug ] = $alo_cat;
			}
		}
		update_option( self::OPT_ALO_CATEGORY_MAP, $cat_map );

		// Required Map (per-category supplemental field mappings)
		$req_map = array();
		if ( ! empty( $post['alo_required_map'] ) && is_array( $post['alo_required_map'] ) ) {
			foreach ( $post['alo_required_map'] as $subcat_id => $field_mappings ) {
				if ( ! is_array( $field_mappings ) ) {
					continue;
				}
				$subcat_id             = sanitize_text_field( wp_unslash( $subcat_id ) );
				$req_map[ $subcat_id ] = array();
				foreach ( $field_mappings as $field_key => $source ) {
					// Restore dot notation: '|' was used in form names to avoid PHP dot→underscore conversion.
					$field_key                            = str_replace( '|', '.', sanitize_text_field( wp_unslash( $field_key ) ) );
					$source                               = sanitize_text_field( wp_unslash( $source ) );
					$req_map[ $subcat_id ][ $field_key ]  = $source;
				}
			}
		}
		update_option( self::OPT_ALO_REQUIRED_MAP, $req_map );

		// Rent alternative map (per-term rent subcat_id for B5 routing)
		$rent_map = array();
		if ( ! empty( $post['alo_category_rent_map'] ) && is_array( $post['alo_category_rent_map'] ) ) {
			foreach ( $post['alo_category_rent_map'] as $term_slug => $subcat_id ) {
				$term_slug            = sanitize_text_field( wp_unslash( $term_slug ) );
				$subcat_id            = sanitize_text_field( wp_unslash( $subcat_id ) );
				$rent_map[ $term_slug ] = $subcat_id;
			}
		}
		update_option( self::OPT_ALO_CATEGORY_RENT_MAP, $rent_map );

		// Deal Type Field + Map
		$deal_field = isset( $post['alo_deal_type_field'] )
			? sanitize_text_field( wp_unslash( $post['alo_deal_type_field'] ) )
			: '';
		update_option( self::OPT_ALO_DEAL_FIELD, $deal_field );

		$deal_map = array();
		if ( ! empty( $post['alo_deal_type_map'] ) && is_array( $post['alo_deal_type_map'] ) ) {
			foreach ( $post['alo_deal_type_map'] as $wp_val => $direction ) {
				$wp_val    = sanitize_text_field( wp_unslash( $wp_val ) );
				$direction = in_array( $direction, array( 'sales', 'rent' ), true ) ? $direction : 'sales';
				$deal_map[ $wp_val ] = $direction;
			}
		}
		update_option( self::OPT_ALO_DEAL_MAP, $deal_map );

		// C2 — Location Label Map (display labels for location search widgets; avoids per-load resolve AJAX)
		$loc_label_map = array();
		if ( ! empty( $post['alo_location_label_map'] ) && is_array( $post['alo_location_label_map'] ) ) {
			foreach ( $post['alo_location_label_map'] as $col => $mappings ) {
				if ( ! is_array( $mappings ) ) {
					continue;
				}
				$col                       = sanitize_text_field( wp_unslash( $col ) );
				$loc_label_map[ $col ]     = array();
				foreach ( $mappings as $wp_val => $label ) {
					$wp_val                             = sanitize_text_field( wp_unslash( $wp_val ) );
					$label                              = sanitize_text_field( wp_unslash( $label ) );
					$loc_label_map[ $col ][ $wp_val ]   = $label;
				}
			}
		}
		update_option( self::OPT_ALO_LOCATION_LABEL_MAP, $loc_label_map );

		// C6 — Static Contacts
		$contacts = array(
			'phones'        => array(),
			'email'         => '',
			'hide_name'     => '',
			'hide_location' => '',
			'hide_address'  => '',
			'hide_website'  => '',
		);
		if ( ! empty( $post['alo_contacts_phones'] ) && is_array( $post['alo_contacts_phones'] ) ) {
			foreach ( $post['alo_contacts_phones'] as $phone ) {
				$phone = sanitize_text_field( wp_unslash( $phone ) );
				if ( '' !== $phone ) {
					$contacts['phones'][] = $phone;
				}
			}
			$contacts['phones'] = array_slice( $contacts['phones'], 0, 5 );
		}
		if ( ! empty( $post['alo_contacts_email'] ) ) {
			$contacts['email'] = sanitize_email( wp_unslash( $post['alo_contacts_email'] ) );
		}
		foreach ( array( 'hide_name', 'hide_location', 'hide_address', 'hide_website' ) as $toggle ) {
			$contacts[ $toggle ] = isset( $post[ 'alo_contacts_' . $toggle ] ) ? 'TRUE' : 'FALSE';
		}
		update_option( self::OPT_ALO_CONTACTS, $contacts );
	}

	// =========================================================================
	// Imoti Settings — getters
	// =========================================================================

	/**
	 * @return string  imoti.net AgencyID, or ''.
	 */
	public function get_imoti_agency_id() {
		return sanitize_text_field( (string) get_option( self::OPT_IMOTI_AGENCY_ID, '' ) );
	}

	/**
	 * @return string  Feed <title> (agency name), or ''.
	 */
	public function get_imoti_agency_title() {
		return sanitize_text_field( (string) get_option( self::OPT_IMOTI_AGENCY_TITLE, '' ) );
	}

	/**
	 * Static broker info injected into every imoti export item.
	 *
	 * @return array{id:string,name:string,phone:string,email:string}
	 */
	public function get_imoti_broker() {
		$saved = $this->get_array_option( self::OPT_IMOTI_BROKER );
		return array(
			'id'    => isset( $saved['id'] )    ? (string) $saved['id']    : '',
			'name'  => isset( $saved['name'] )  ? (string) $saved['name']  : '',
			'phone' => isset( $saved['phone'] ) ? (string) $saved['phone'] : '',
			'email' => isset( $saved['email'] ) ? (string) $saved['email'] : '',
		);
	}

	/**
	 * @return string  Taxonomy slug for imoti per-category defaults, or ''.
	 */
	public function get_imoti_category_tax() {
		return sanitize_key( (string) get_option( self::OPT_IMOTI_CATEGORY_TAX, '' ) );
	}

	/**
	 * Per-category default values for D4 fields.
	 *
	 * @return array<string,array<string,string>>  [ term_slug => [ field => value ] ]
	 */
	public function get_imoti_category_defaults() {
		return $this->get_array_option( self::OPT_IMOTI_CATEGORY_DEFAULTS );
	}

	/**
	 * @return array<string,array<string,string>>  [ field => [ wp_value => display_label ] ]
	 */
	public function get_imoti_location_label_map() {
		return $this->get_array_option( self::OPT_IMOTI_LOCATION_LABEL_MAP );
	}

	/**
	 * @return array<string,string>  [ imoti_field => source_key ]
	 */
	public function get_imoti_field_map() {
		return $this->get_array_option( self::OPT_IMOTI_FIELD_MAP );
	}

	/**
	 * @return array<string,array<string,string>>  [ imoti_field => [ wp_value => imoti_value ] ]
	 */
	public function get_imoti_value_map() {
		return $this->get_array_option( self::OPT_IMOTI_VALUE_MAP );
	}

	// =========================================================================
	// Imoti Settings — save
	// =========================================================================

	/**
	 * Validate and persist imoti.net settings.
	 *
	 * @param array $post  Raw $_POST data.
	 * @return void
	 */
	public function save_imoti_settings( array $post ) {

		// Agency
		$agency_id    = isset( $post['imoti_agency_id'] )
			? sanitize_text_field( wp_unslash( $post['imoti_agency_id'] ) )
			: '';
		$agency_title = isset( $post['imoti_agency_title'] )
			? sanitize_text_field( wp_unslash( $post['imoti_agency_title'] ) )
			: '';
		update_option( self::OPT_IMOTI_AGENCY_ID,    $agency_id );
		update_option( self::OPT_IMOTI_AGENCY_TITLE, $agency_title );

		// Broker
		$broker = array(
			'id'    => isset( $post['imoti_broker_id'] )    ? sanitize_text_field( wp_unslash( $post['imoti_broker_id'] ) )    : '',
			'name'  => isset( $post['imoti_broker_name'] )  ? sanitize_text_field( wp_unslash( $post['imoti_broker_name'] ) )  : '',
			'phone' => isset( $post['imoti_broker_phone'] ) ? sanitize_text_field( wp_unslash( $post['imoti_broker_phone'] ) ) : '',
			'email' => isset( $post['imoti_broker_email'] ) ? sanitize_email( wp_unslash( $post['imoti_broker_email'] ) )      : '',
		);
		update_option( self::OPT_IMOTI_BROKER, $broker );

		// Category Tax
		$cat_tax = isset( $post['imoti_category_tax'] )
			? sanitize_key( wp_unslash( $post['imoti_category_tax'] ) )
			: '';
		update_option( self::OPT_IMOTI_CATEGORY_TAX, $cat_tax );

		// Category Defaults
		$cat_defaults = array();
		if ( ! empty( $post['imoti_category_defaults'] ) && is_array( $post['imoti_category_defaults'] ) ) {
			foreach ( $post['imoti_category_defaults'] as $term_slug => $fields ) {
				if ( ! is_array( $fields ) ) {
					continue;
				}
				$term_slug = sanitize_text_field( wp_unslash( $term_slug ) );
				$cat_defaults[ $term_slug ] = array();

				foreach ( $fields as $field => $value ) {
					$field = sanitize_text_field( wp_unslash( $field ) );

					// Extras_cb[] — checkboxes come as array, store as comma-separated under 'Extras'.
					if ( 'Extras_cb' === $field ) {
						if ( is_array( $value ) ) {
							$codes = array_filter( array_map( 'sanitize_text_field', wp_unslash( $value ) ) );
							$cat_defaults[ $term_slug ]['Extras'] = implode( ',', $codes );
						}
						continue;
					}

					$value = sanitize_text_field( wp_unslash( (string) $value ) );
					if ( '' !== $value ) {
						$cat_defaults[ $term_slug ][ $field ] = $value;
					}
				}
			}
		}
		update_option( self::OPT_IMOTI_CATEGORY_DEFAULTS, $cat_defaults );

		// Field Map
		$field_map = array();
		if ( ! empty( $post['imoti_field_map'] ) && is_array( $post['imoti_field_map'] ) ) {
			foreach ( $post['imoti_field_map'] as $field => $source ) {
				$field              = sanitize_text_field( wp_unslash( $field ) );
				$source             = sanitize_text_field( wp_unslash( $source ) );
				$field_map[ $field ] = $source;
			}
		}
		update_option( self::OPT_IMOTI_FIELD_MAP, $field_map );

		// Value Map
		$value_map = array();
		if ( ! empty( $post['imoti_value_map'] ) && is_array( $post['imoti_value_map'] ) ) {
			foreach ( $post['imoti_value_map'] as $field => $mappings ) {
				if ( ! is_array( $mappings ) ) {
					continue;
				}
				$field               = sanitize_text_field( wp_unslash( $field ) );
				$value_map[ $field ] = array();
				foreach ( $mappings as $wp_val => $imoti_val ) {
					$wp_val = sanitize_text_field( wp_unslash( $wp_val ) );
					if ( is_array( $imoti_val ) ) {
						// Multi-select (e.g. Extras): join selected codes as comma-separated string.
						$imoti_val = implode( ',', array_map( 'sanitize_text_field', array_map( 'wp_unslash', $imoti_val ) ) );
					} else {
						$imoti_val = sanitize_text_field( wp_unslash( $imoti_val ) );
					}
					$value_map[ $field ][ $wp_val ] = $imoti_val;
				}
			}
		}
		update_option( self::OPT_IMOTI_VALUE_MAP, $value_map );

		// Location Label Map (display labels for EstateRegion / EstateSubRegion AJAX widgets)
		$loc_label_map = array();
		if ( ! empty( $post['imoti_location_label_map'] ) && is_array( $post['imoti_location_label_map'] ) ) {
			foreach ( $post['imoti_location_label_map'] as $field => $mappings ) {
				if ( ! is_array( $mappings ) ) {
					continue;
				}
				$field                    = sanitize_text_field( wp_unslash( $field ) );
				$loc_label_map[ $field ]  = array();
				foreach ( $mappings as $wp_val => $label ) {
					$wp_val                            = sanitize_text_field( wp_unslash( $wp_val ) );
					$label                             = sanitize_text_field( wp_unslash( $label ) );
					$loc_label_map[ $field ][ $wp_val ] = $label;
				}
			}
		}
		update_option( self::OPT_IMOTI_LOCATION_LABEL_MAP, $loc_label_map );
	}

	// =========================================================================
	// Realistimo Settings — getters
	// =========================================================================

	/**
	 * Agency information for the Realistimo feed header.
	 *
	 * @return array{uid:string,name:string,phone:string,email:string,result_url:string}
	 */
	public function get_realistimo_agency() {
		$saved = $this->get_array_option( self::OPT_REALISTIMO_AGENCY );
		return array(
			'uid'        => isset( $saved['uid'] )        ? (string) $saved['uid']        : '',
			'name'       => isset( $saved['name'] )       ? (string) $saved['name']       : '',
			'phone'      => isset( $saved['phone'] )      ? (string) $saved['phone']      : '',
			'email'      => isset( $saved['email'] )      ? (string) $saved['email']      : '',
			'result_url' => isset( $saved['result_url'] ) ? (string) $saved['result_url'] : '',
		);
	}

	/**
	 * Static broker info injected into every Realistimo export offer.
	 *
	 * @return array{id:string,name:string,phone:string,email:string,photo:string}
	 */
	public function get_realistimo_broker() {
		$saved = $this->get_array_option( self::OPT_REALISTIMO_BROKER );
		return array(
			'id'    => isset( $saved['id'] )    ? (string) $saved['id']    : '',
			'name'  => isset( $saved['name'] )  ? (string) $saved['name']  : '',
			'phone' => isset( $saved['phone'] ) ? (string) $saved['phone'] : '',
			'email' => isset( $saved['email'] ) ? (string) $saved['email'] : '',
			'photo' => isset( $saved['photo'] ) ? (string) $saved['photo'] : '',
		);
	}

	/**
	 * @return array<string,string>  [ xml_field => source_key ]
	 */
	public function get_realistimo_field_map() {
		return $this->get_array_option( self::OPT_REALISTIMO_FIELD_MAP );
	}

	/**
	 * @return array<string,array<string,string>>  [ xml_field => [ wp_value => realistimo_value ] ]
	 */
	public function get_realistimo_value_map() {
		return $this->get_array_option( self::OPT_REALISTIMO_VALUE_MAP );
	}

	/**
	 * Display labels for the internal_id AJAX geo widget.
	 *
	 * @return array<string,array<string,string>>  [ 'internal_id' => [ wp_value => label ] ]
	 */
	public function get_realistimo_location_label_map() {
		return $this->get_array_option( self::OPT_REALISTIMO_LOCATION_LABEL_MAP );
	}

	public function get_realistimo_category_tax() {
		return (string) get_option( self::OPT_REALISTIMO_CATEGORY_TAX, '' );
	}

	public function get_realistimo_category_defaults() {
		return $this->get_array_option( self::OPT_REALISTIMO_CATEGORY_DEFAULTS );
	}

	// =========================================================================
	// Realistimo Settings — save
	// =========================================================================

	/**
	 * Validate and persist Realistimo settings.
	 *
	 * @param array $post  Raw $_POST data.
	 * @return void
	 */
	public function save_realistimo_settings( array $post ) {

		// Agency
		$agency = array(
			'uid'        => isset( $post['realistimo_agency_uid'] )        ? sanitize_text_field( wp_unslash( $post['realistimo_agency_uid'] ) )        : '',
			'name'       => isset( $post['realistimo_agency_name'] )       ? sanitize_text_field( wp_unslash( $post['realistimo_agency_name'] ) )       : '',
			'phone'      => isset( $post['realistimo_agency_phone'] )      ? sanitize_text_field( wp_unslash( $post['realistimo_agency_phone'] ) )      : '',
			'email'      => isset( $post['realistimo_agency_email'] )      ? sanitize_email( wp_unslash( $post['realistimo_agency_email'] ) )           : '',
			'result_url' => isset( $post['realistimo_agency_result_url'] ) ? esc_url_raw( wp_unslash( $post['realistimo_agency_result_url'] ) )         : '',
		);
		update_option( self::OPT_REALISTIMO_AGENCY, $agency );

		// Broker
		$broker = array(
			'id'    => isset( $post['realistimo_broker_id'] )    ? sanitize_text_field( wp_unslash( $post['realistimo_broker_id'] ) )    : '',
			'name'  => isset( $post['realistimo_broker_name'] )  ? sanitize_text_field( wp_unslash( $post['realistimo_broker_name'] ) )  : '',
			'phone' => isset( $post['realistimo_broker_phone'] ) ? sanitize_text_field( wp_unslash( $post['realistimo_broker_phone'] ) ) : '',
			'email' => isset( $post['realistimo_broker_email'] ) ? sanitize_email( wp_unslash( $post['realistimo_broker_email'] ) )      : '',
			'photo' => isset( $post['realistimo_broker_photo'] ) ? esc_url_raw( wp_unslash( $post['realistimo_broker_photo'] ) )         : '',
		);
		update_option( self::OPT_REALISTIMO_BROKER, $broker );

		// Field Map
		$field_map = array();
		if ( ! empty( $post['realistimo_field_map'] ) && is_array( $post['realistimo_field_map'] ) ) {
			foreach ( $post['realistimo_field_map'] as $field => $source ) {
				$field               = sanitize_text_field( wp_unslash( $field ) );
				$source              = sanitize_text_field( wp_unslash( $source ) );
				$field_map[ $field ] = $source;
			}
		}
		update_option( self::OPT_REALISTIMO_FIELD_MAP, $field_map );

		// Value Map
		$value_map = array();
		if ( ! empty( $post['realistimo_value_map'] ) && is_array( $post['realistimo_value_map'] ) ) {
			foreach ( $post['realistimo_value_map'] as $field => $mappings ) {
				if ( ! is_array( $mappings ) ) {
					continue;
				}
				$field               = sanitize_text_field( wp_unslash( $field ) );
				$value_map[ $field ] = array();
				foreach ( $mappings as $wp_val => $rval ) {
					$wp_val = sanitize_text_field( wp_unslash( $wp_val ) );
					if ( is_array( $rval ) ) {
						// Multi-select: join selected values as comma-separated.
						$rval = implode( ',', array_map( 'sanitize_text_field', array_map( 'wp_unslash', $rval ) ) );
					} else {
						$rval = sanitize_text_field( wp_unslash( $rval ) );
					}
					$value_map[ $field ][ $wp_val ] = $rval;
				}
			}
		}
		update_option( self::OPT_REALISTIMO_VALUE_MAP, $value_map );

		// Location Label Map (display labels for internal_id AJAX widget)
		$loc_label_map = array();
		if ( ! empty( $post['realistimo_location_label_map'] ) && is_array( $post['realistimo_location_label_map'] ) ) {
			foreach ( $post['realistimo_location_label_map'] as $field => $mappings ) {
				if ( ! is_array( $mappings ) ) {
					continue;
				}
				$field                     = sanitize_text_field( wp_unslash( $field ) );
				$loc_label_map[ $field ]   = array();
				foreach ( $mappings as $wp_val => $label ) {
					$wp_val                              = sanitize_text_field( wp_unslash( $wp_val ) );
					$label                               = sanitize_text_field( wp_unslash( $label ) );
					$loc_label_map[ $field ][ $wp_val ]  = $label;
				}
			}
		}
		update_option( self::OPT_REALISTIMO_LOCATION_LABEL_MAP, $loc_label_map );

		// Category taxonomy + per-term subcategory defaults.
		$category_tax = isset( $post['realistimo_category_tax'] ) ? sanitize_text_field( wp_unslash( $post['realistimo_category_tax'] ) ) : '';
		update_option( self::OPT_REALISTIMO_CATEGORY_TAX, $category_tax );

		$category_defaults = array();
		if ( ! empty( $post['realistimo_category_defaults'] ) && is_array( $post['realistimo_category_defaults'] ) ) {
			foreach ( $post['realistimo_category_defaults'] as $term_slug => $fields ) {
				if ( ! is_array( $fields ) ) {
					continue;
				}
				$term_slug = sanitize_text_field( wp_unslash( $term_slug ) );
				$subcategory = isset( $fields['subcategory'] ) ? sanitize_text_field( wp_unslash( $fields['subcategory'] ) ) : '';
				$category_defaults[ $term_slug ] = array( 'subcategory' => $subcategory );
			}
		}
		update_option( self::OPT_REALISTIMO_CATEGORY_DEFAULTS, $category_defaults );
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Fix ALO field keys that were stored with PHP's dot→underscore conversion.
	 *
	 * PHP converts dots in POST array subscripts to underscores, so
	 * 'contacts.phones' becomes 'contacts_phones' in $_POST.
	 * This normalises both forms so the exporter always sees dots.
	 *
	 * @param array $map  Raw key→value map.
	 * @return array
	 */
	private function normalize_alo_dot_keys( array $map ) {
		$out = array();
		foreach ( $map as $k => $v ) {
			// contacts_* → contacts.*
			if ( 0 === strpos( $k, 'contacts_' ) && false === strpos( $k, '.' ) ) {
				$k = 'contacts.' . substr( $k, 9 );
			// map_lat / map_long → map.lat / map.long
			} elseif ( 0 === strpos( $k, 'map_' ) && false === strpos( $k, '.' ) ) {
				$k = 'map.' . substr( $k, 4 );
			}
			$out[ $k ] = $v;
		}
		return $out;
	}

	/**
	 * Retrieve a serialised array option, defaulting to [].
	 *
	 * @param string $option_name
	 * @return array
	 */
	private function get_array_option( $option_name ) {
		$value = get_option( $option_name, array() );
		return is_array( $value ) ? $value : array();
	}
}
