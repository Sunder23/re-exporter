<?php
/**
 * Registers the top-level admin menu, handles settings form saves (via
 * admin_init before output), enqueues assets, and dispatches rendering
 * to the correct tab template.
 *
 * URL structure:
 *   admin.php?page=re-exporter               → Settings tab, OLX sub-tab
 *   admin.php?page=re-exporter&tab=settings&sub=olx
 *   admin.php?page=re-exporter&tab=settings&sub=alo
 *   admin.php?page=re-exporter&tab=export
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Page
 */
class Admin_Page {

	/** @var Settings */
	private $settings;

	/** @var OLX_Template */
	private $olx_template;

	/** @var ALO_Template */
	private $alo_template;

	/** @var Field_Scanner */
	private $scanner;

	/** @var Platform_Registry */
	private $platform_registry;

	/** @var OLX_Category_Resolver */
	private $olx_category_resolver;

	/** @var Export_Wizard */
	private $wizard;

	/**
	 * @param Settings      $settings
	 * @param OLX_Template  $olx_template
	 * @param ALO_Template  $alo_template
	 * @param Field_Scanner    $scanner
	 * @param Platform_Registry    $platform_registry
	 * @param OLX_Category_Resolver $olx_category_resolver
	 * @param Export_Run_Service    $run_service
	 * @param Export_Review_Service $review_service
	 */
	public function __construct(
		Settings $settings,
		OLX_Template $olx_template,
		ALO_Template $alo_template,
		Field_Scanner $scanner,
		Platform_Registry $platform_registry,
		OLX_Category_Resolver $olx_category_resolver,
		Export_Run_Service $run_service,
		Export_Review_Service $review_service
	) {
		$this->settings              = $settings;
		$this->olx_template          = $olx_template;
		$this->alo_template          = $alo_template;
		$this->scanner               = $scanner;
		$this->platform_registry     = $platform_registry;
		$this->olx_category_resolver = $olx_category_resolver;
		$this->wizard                = new Export_Wizard( $settings, $platform_registry, $olx_category_resolver, $run_service, $review_service );

		add_action( 'admin_menu',            array( $this, 'register_menu' ) );
		add_action( 'admin_init',            array( $this, 'handle_form_saves' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_re_exp_search_cities',               array( $this, 'ajax_search_cities' ) );
		add_action( 'wp_ajax_re_exp_imoti_search_locations',     array( $this, 'ajax_imoti_search_locations' ) );
		add_action( 'wp_ajax_re_exp_realistimo_search_location', array( $this, 'ajax_realistimo_search_location' ) );
	}

	// =========================================================================
	// Menu
	// =========================================================================

	public function register_menu() {
		add_menu_page(
			__( 'Real Estate Exporter', 're-exporter' ),
			__( 'RE Exporter', 're-exporter' ),
			'manage_options',
			're-exporter',
			array( $this, 'render_page' ),
			'dashicons-upload',
			30
		);
	}

	// =========================================================================
	// Form Saves (admin_init — before any output)
	// =========================================================================

	/**
	 * Intercept POST submissions and redirect after saving.
	 * Runs on admin_init so headers are still available.
	 */
	public function handle_form_saves() {
		// Only act on our own page.
		if (
			! is_admin() ||
			! isset( $_GET['page'] ) ||
			're-exporter' !== sanitize_key( wp_unslash( $_GET['page'] ) )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_POST['re_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['re_action'] ) );

		switch ( $action ) {

			case 'save_global':
				check_admin_referer( 're_exporter_save_global' );
				$this->settings->global_settings()->save( $_POST );
				wp_safe_redirect( $this->settings_url( array( 'saved' => 'global' ) ) );
				exit;

			case 'save_olx':
				check_admin_referer( 're_exporter_save_olx' );
				$this->settings->olx()->save( $_POST );
				wp_safe_redirect( $this->settings_url( array( 'sub' => 'olx', 'saved' => 'olx' ) ) );
				exit;

			case 'save_alo':
				check_admin_referer( 're_exporter_save_alo' );
				$this->settings->alo()->save( $_POST );
				wp_safe_redirect( $this->settings_url( array( 'sub' => 'alo', 'saved' => 'alo' ) ) );
				exit;

			case 'save_imoti':
				check_admin_referer( 're_exporter_save_imoti' );
				$this->settings->imoti()->save( $_POST );
				wp_safe_redirect( $this->settings_url( array( 'sub' => 'imoti', 'saved' => 'imoti' ) ) );
				exit;

			case 'save_realistimo':
				check_admin_referer( 're_exporter_save_realistimo' );
				$this->settings->realistimo()->save( $_POST );
				wp_safe_redirect( $this->settings_url( array( 'sub' => 'realistimo', 'saved' => 'realistimo' ) ) );
				exit;

			case 'delete_run':
				check_admin_referer( 're_exporter_delete_run' );
				$platform = isset( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';
				$run      = isset( $_POST['run'] )      ? sanitize_file_name( wp_unslash( $_POST['run'] ) ) : '';
				if ( $platform && $run ) {
					$this->delete_run_dir( $platform, $run );
				}
				wp_safe_redirect( $this->files_url( array( 'deleted' => '1' ) ) );
				exit;

			case 'delete_all':
				check_admin_referer( 're_exporter_delete_all' );
				$platform = isset( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';
				if ( $platform ) {
					$this->delete_all_runs( $platform );
				}
				wp_safe_redirect( $this->files_url( array( 'deleted' => 'all' ) ) );
				exit;
		}
	}

	// =========================================================================
	// Render
	// =========================================================================

	/**
	 * Main page callback — dispatches to Settings or Export tab.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 're-exporter' ) );
		}

		$active_tab = $this->get_active_tab();

		// Prepare shared data for templates.
		$post_type    = $this->settings->global_settings()->get_post_type();
		$cpt_list     = $this->scanner->get_post_types();
		$active_sub   = $this->get_active_sub();
		$saved        = isset( $_GET['saved'] )   ? sanitize_key( wp_unslash( $_GET['saved'] ) )   : '';
		$deleted      = isset( $_GET['deleted'] ) ? sanitize_key( wp_unslash( $_GET['deleted'] ) ) : '';

		include RE_EXPORTER_DIR . 'templates/admin/page-wrapper.php';
	}

	// =========================================================================
	// Assets
	// =========================================================================

	/**
	 * Enqueue CSS and JS only on the plugin's own admin page.
	 *
	 * @param string $hook  Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_re-exporter' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			're-exporter-admin',
			RE_EXPORTER_URL . 'assets/css/re-admin.css',
			array(),
			RE_EXPORTER_VERSION
		);

		wp_enqueue_script(
			're-exporter-city-widget',
			RE_EXPORTER_URL . 'assets/js/re-city-widget.js',
			array( 'jquery' ),
			RE_EXPORTER_VERSION,
			true
		);

		wp_enqueue_script(
			're-exporter-admin',
			RE_EXPORTER_URL . 'assets/js/re-admin.js',
			array( 'jquery', 're-exporter-city-widget' ),
			RE_EXPORTER_VERSION,
			true
		);

		wp_localize_script(
			're-exporter-admin',
			'reExporter',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 're_exporter_nonce' ),
				'i18n'    => array(
					'loading'         => __( 'Loading…', 're-exporter' ),
					'exporting'       => __( 'Generating export…', 're-exporter' ),
					'loadingReview'   => __( 'Building review…', 're-exporter' ),
					'error'           => __( 'An error occurred. Please try again.', 're-exporter' ),
					'selectRecord'    => __( 'Please select at least one record.', 're-exporter' ),
					'selectPlatform'  => __( 'Please select a platform.', 're-exporter' ),
					'confirmUnmapped' => __( 'required field(s) are not mapped. Continue anyway?', 're-exporter' ),
					'of'              => __( 'of', 're-exporter' ),
					'selected'        => __( 'selected', 're-exporter' ),
					'keepOriginal'    => __( '— Keep original —', 're-exporter' ),
					'noResults'       => __( 'No results', 're-exporter' ),
					'typeToSearch'    => __( 'Type to search city…', 're-exporter' ),
				),
			)
		);
	}

	// =========================================================================
	// Template Data Helpers (called from templates)
	// =========================================================================

	/**
	 * Build all data needed to render the Settings tab.
	 * Returns an associative array for use in the settings template.
	 *
	 * @return array
	 */
	public function get_settings_data() {
		$post_type    = $this->settings->get_post_type();
		$source_fields = array();
		$taxonomies    = array();
		$tax_terms     = array();

		if ( $post_type ) {
			$sample_ids    = $this->scanner->get_sample_post_ids( $post_type, 5 );
			$source_fields = $this->scanner->get_field_definitions( $post_type, $sample_ids );
			$taxonomies    = $this->scanner->get_taxonomies( $post_type );

			foreach ( $taxonomies as $tax ) {
				$tax_terms[ $tax->name ] = $this->scanner->get_taxonomy_terms( $tax->name );
			}
		}

		return array(
			'post_type'         => $post_type,
			'source_fields'     => $source_fields,
			'taxonomies'        => $taxonomies,
			'tax_terms'         => $tax_terms,
			'all_csv_columns'   => $this->olx_template->get_all_csv_columns(),
			'grouped_subcats'    => $this->olx_template->get_grouped_subcategories(),
			'base_subcats'       => $this->olx_template->get_base_subcategories(),
			'all_subcats'        => $this->olx_template->get_all_subcategories(),
			'olx_field_map'     => $this->settings->olx()->get_field_map(),
			'olx_value_map'      => $this->settings->olx()->get_value_map(),
			'olx_city_label_map' => $this->settings->olx()->get_city_label_map(),
			'olx_category_tax'   => $this->settings->olx()->get_category_tax(),
			'olx_category_map'       => $this->settings->olx()->get_category_map(),
			'olx_category_rent_map'  => $this->settings->olx()->get_category_rent_map(),
			'olx_required_map'       => $this->settings->olx()->get_required_map(),
			'olx_deal_field'    => $this->settings->olx()->get_deal_type_field(),
			'olx_deal_map'      => $this->settings->olx()->get_deal_type_map(),
			'alo_field_map'          => $this->settings->alo()->get_field_map(),
			'alo_value_map'          => $this->settings->alo()->get_value_map(),
			'alo_category_tax'       => $this->settings->alo()->get_category_tax(),
			'alo_category_map'       => $this->settings->alo()->get_category_map(),
			'alo_required_map'       => $this->settings->alo()->get_required_map(),
			'alo_grouped_subcats'    => $this->alo_template->get_grouped_subcategories(),
			'alo_all_subcats'        => $this->alo_template->get_all_subcategories(),
			'alo_all_fields'         => $this->alo_template->get_all_fields(),
			'alo_deal_field'         => $this->settings->alo()->get_deal_type_field(),
			'alo_deal_map'           => $this->settings->alo()->get_deal_type_map(),
			'alo_category_rent_map'      => $this->settings->alo()->get_category_rent_map(),
		'alo_location_label_map'     => $this->settings->alo()->get_location_label_map(),
		'alo_contacts'               => $this->settings->alo()->get_contacts(),
			'imoti_agency_id'            => $this->settings->imoti()->get_agency_id(),
			'imoti_agency_title'         => $this->settings->imoti()->get_agency_title(),
			'imoti_broker'               => $this->settings->imoti()->get_broker(),
			'imoti_field_map'            => $this->settings->imoti()->get_field_map(),
			'imoti_value_map'            => $this->settings->imoti()->get_value_map(),
			'imoti_category_tax'         => $this->settings->imoti()->get_category_tax(),
			'imoti_category_defaults'    => $this->settings->imoti()->get_category_defaults(),
			'imoti_location_label_map'   => $this->settings->imoti()->get_location_label_map(),
			'realistimo_agency'              => $this->settings->realistimo()->get_agency(),
			'realistimo_broker'              => $this->settings->realistimo()->get_broker(),
			'realistimo_field_map'           => $this->settings->realistimo()->get_field_map(),
			'realistimo_value_map'           => $this->settings->realistimo()->get_value_map(),
			'realistimo_location_label_map'  => $this->settings->realistimo()->get_location_label_map(),
			'realistimo_category_tax'        => $this->settings->realistimo()->get_category_tax(),
			'realistimo_category_defaults'   => $this->settings->realistimo()->get_category_defaults(),
		);
	}

	/**
	 * Return source values (WP values) for a field — used in B2 Value Overrides.
	 *
	 * @param string $source_key
	 * @param array  $source_fields  From get_field_definitions().
	 * @param string $post_type
	 * @return array[]  [ 'label', 'value' ]
	 */
	public function get_source_values_for( $source_key, array $source_fields, $post_type ) {
		if ( ! isset( $source_fields[ $source_key ] ) ) {
			return array();
		}
		return $this->scanner->get_source_values( $source_key, $source_fields[ $source_key ], $post_type );
	}

	// =========================================================================
	// URL Helpers
	// =========================================================================

	/**
	 * Build the settings tab URL with optional extra args.
	 *
	 * @param array $extra
	 * @return string
	 */
	public function settings_url( array $extra = array() ) {
		return add_query_arg(
			array_merge( array( 'page' => 're-exporter', 'tab' => 'settings' ), $extra ),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Build the export tab URL.
	 *
	 * @return string
	 */
	public function export_url() {
		return add_query_arg( array( 'page' => 're-exporter', 'tab' => 'export' ), admin_url( 'admin.php' ) );
	}

	/**
	 * Build the instructions tab URL.
	 *
	 * @return string
	 */
	public function instructions_url() {
		return add_query_arg( array( 'page' => 're-exporter', 'tab' => 'instructions' ), admin_url( 'admin.php' ) );
	}

	/**
	 * Build the files tab URL with optional extra args.
	 *
	 * @param array $extra
	 * @return string
	 */
	public function files_url( array $extra = array() ) {
		return add_query_arg(
			array_merge( array( 'page' => 're-exporter', 'tab' => 'files' ), $extra ),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Scan uploads/re-exporter/olx/ and .../alo/ and return all existing export runs.
	 *
	 * @return array  [ 'olx' => [ run, ... ], 'alo' => [ run, ... ] ]
	 *   Each run: [ 'run', 'dir', 'date', 'files' => [ [ 'name', 'size', 'url' ] ], 'total_size' ]
	 */
	public function get_export_runs() {
		$upload   = wp_upload_dir();
		$base_dir = trailingslashit( $upload['basedir'] ) . 're-exporter/';
		$base_url = trailingslashit( $upload['baseurl'] ) . 're-exporter/';
		$result   = array();

		foreach ( $this->platform_registry->get_codes() as $platform ) {
			$plat_dir = $base_dir . $platform . '/';
			$plat_url = $base_url . $platform . '/';
			$runs     = array();

			if ( is_dir( $plat_dir ) ) {
				$items = scandir( $plat_dir, SCANDIR_SORT_DESCENDING );
				foreach ( (array) $items as $item ) {
					if ( '.' === $item || '..' === $item || ! is_dir( $plat_dir . $item ) ) {
						continue;
					}
					$run_dir = trailingslashit( $plat_dir . $item );
					$ts      = is_numeric( $item ) ? (int) $item : 0;
					$date    = $ts
						? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $ts ), 'd.m.Y H:i:s' )
						: $item;

					$files      = array();
					$total_size = 0;
					foreach ( (array) glob( $run_dir . '*' ) as $fpath ) {
						if ( is_file( $fpath ) ) {
							$fsize       = (int) filesize( $fpath );
							$total_size += $fsize;
							$files[]     = array(
								'name' => basename( $fpath ),
								'size' => $fsize,
								'url'  => $plat_url . $item . '/' . rawurlencode( basename( $fpath ) ),
							);
						}
					}

					$runs[] = array(
						'run'        => $item,
						'dir'        => $run_dir,
						'date'       => $date,
						'files'      => $files,
						'total_size' => $total_size,
					);
				}
			}

			$result[ $platform ] = $runs;
		}

		return $result;
	}

	// =========================================================================
	// Private Helpers
	// =========================================================================

	/**
	 * @return string 'settings' | 'export' | 'instructions'
	 */
	private function get_active_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
		return in_array( $tab, array( 'settings', 'export', 'instructions', 'files' ), true ) ? $tab : 'settings';
	}

	/**
	 * Delete a single timestamped run directory (files + folder).
	 *
	 * @param string $platform  'olx' | 'alo'
	 * @param string $run       Folder name (timestamp string).
	 */
	private function delete_run_dir( $platform, $run ) {
		if ( ! $this->platform_registry->has( $platform ) ) {
			return;
		}
		// $run must be alphanumeric/underscores/dashes only (no path traversal).
		if ( ! preg_match( '/^[a-zA-Z0-9_\-]+$/', $run ) ) {
			return;
		}

		$upload  = wp_upload_dir();
		$run_dir = trailingslashit( $upload['basedir'] ) . 're-exporter/' . $platform . '/' . $run;

		if ( ! is_dir( $run_dir ) ) {
			return;
		}

		foreach ( (array) glob( $run_dir . '/*' ) as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@rmdir( $run_dir );
	}

	/**
	 * Delete all run directories for a given platform.
	 *
	 * @param string $platform  'olx' | 'alo'
	 */
	private function delete_all_runs( $platform ) {
		if ( ! $this->platform_registry->has( $platform ) ) {
			return;
		}

		$upload   = wp_upload_dir();
		$plat_dir = trailingslashit( $upload['basedir'] ) . 're-exporter/' . $platform;

		if ( ! is_dir( $plat_dir ) ) {
			return;
		}

		$items = scandir( $plat_dir );
		foreach ( (array) $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$this->delete_run_dir( $platform, $item );
		}
	}

	/**
	 * @return string 'olx' | 'alo'
	 */
	private function get_active_sub() {
		$sub = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'olx';
		return $this->platform_registry->has( $sub ) ? $sub : 'olx';
	}

	// =========================================================================
	// AJAX: City search (for location_city widget)
	// =========================================================================

	/**
	 * AJAX handler: search cities via the OLX geo-encoder API.
	 *
	 * POST params:
	 *   q    string  Search term (empty = first 10 results).
	 *   val  string  Resolve a single city ID → label (page-load display).
	 *
	 * Returns: { items: [ { label, value }, … ] }
	 *
	 * Resolved labels are cached in the WP option
	 * 're_exporter_city_label_cache_{country}' (array of id => label, max 2000).
	 */
	public function ajax_search_cities() {
		check_ajax_referer( 're_exporter_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden' );
		}

		$q        = isset( $_POST['q'] )   ? sanitize_text_field( wp_unslash( $_POST['q'] ) )   : '';
		$val      = isset( $_POST['val'] ) ? sanitize_text_field( wp_unslash( $_POST['val'] ) ) : '';
		$country  = $this->settings->get_olx_country();
		$base_url = $this->settings->get_olx_country_base_url();
		$opt_key  = 're_exporter_city_label_cache_' . $country;

		// ── Resolve: val → label (page-load) ─────────────────────────────────
		if ( '' !== $val && '' === $q ) {
			$cache = (array) get_option( $opt_key, array() );
			if ( isset( $cache[ $val ] ) ) {
				wp_send_json_success( array( 'items' => array(
					array( 'label' => $cache[ $val ], 'value' => $val ),
				) ) );
			}
			// ID not in cache — JS will fall back to showing the raw ID.
			wp_send_json_success( array( 'items' => array() ) );
		}

		// ── Search via OLX location-autocomplete API ──────────────────────────
		$api_url  = $base_url . '/api/v1/geo-encoder/location-autocomplete/?query=' . rawurlencode( $q );
		$response = wp_remote_get( $api_url, array(
			'timeout' => 5,
			'headers' => array( 'Accept' => 'application/json' ),
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		$items = array();
		$cache = (array) get_option( $opt_key, array() );

		foreach ( $data['data'] as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['city'] ) || ! is_array( $entry['city'] ) ) {
				continue;
			}

			$city     = $entry['city'];
			$district = ( ! empty( $entry['district'] ) && is_array( $entry['district'] ) && ! empty( $entry['district']['id'] ) )
				? $entry['district']
				: null;

			if ( $district ) {
				$label = $district['name'] . ', ' . $city['name'];
			} else {
				$label = $city['name'];
			}

			$city_id     = (string) $city['id'];
			$district_id = $district ? (string) $district['id'] : '';

			// 'value' kept for backward-compat with the old __json__citys widget.
			$items[] = array(
				'label'       => $label,
				'value'       => $district_id ?: $city_id,
				'city_id'     => $city_id,
				'district_id' => $district_id,
			);

			// Cache label by city ID (used for resolve-on-load).
			$cache[ $city_id ] = $label;

			if ( count( $items ) >= 10 ) {
				break;
			}
		}

		// Persist cache (cap at 2000 entries to avoid option bloat).
		if ( count( $cache ) > 2000 ) {
			$cache = array_slice( $cache, -2000, 2000, true );
		}
		update_option( $opt_key, $cache, false );

		wp_send_json_success( array( 'items' => $items ) );
	}

	// =========================================================================
	// AJAX: imoti.net location search (EstateRegion / EstateSubRegion)
	// =========================================================================

	/**
	 * AJAX handler: search imoti.net regions or subareas.
	 *
	 * POST params:
	 *   field  string  'EstateRegion' or 'EstateSubRegion'
	 *   q      string  Search term.
	 *
	 * Returns: { items: [ { label, value }, … ] }
	 *
	 * API responses are cached in transients for 24 h.
	 */
	public function ajax_imoti_search_locations() {
		check_ajax_referer( 're_exporter_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden' );
		}

		$field = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';
		$q     = isset( $_POST['q'] )     ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

		if ( ! in_array( $field, array( 'EstateRegion', 'EstateSubRegion' ), true ) ) {
			wp_send_json_error( 'Invalid field', $field );
		}

		$regions = $this->imoti_get_cached_locations( 'cities' );

		if ( 'EstateRegion' === $field ) {
			$items = $this->imoti_filter_regions( $regions, $q );
		} else {
			$subareas = $this->imoti_get_cached_locations( 'subareas' );
			$items    = $this->imoti_filter_subareas( $subareas, $regions, $q );
		}

		wp_send_json_success( array( 'items' => $items ) );
	}

	/**
	 * Fetch and cache the full imoti.net location list for a given type.
	 *
	 * @param string $type  'cities' | 'subareas'
	 * @return array[]  [ [ 'id', 'title', 'extra' ], … ]
	 */
	private function imoti_get_cached_locations( $type ) {
		$cache_key = 're_exporter_imoti_' . $type . '_cache';
		$cached    = get_transient( $cache_key );
		if ( false !== $cached && ! empty( $cached ) ) {
			return (array) $cached;
		}

		$api_url  = 'https://www.imoti.net/rss/getData.php?u=rss&p=par0La&get=' . rawurlencode( $type );
		$response = wp_remote_get( $api_url, array(
			'timeout' => 10,
			'headers' => array( 'Accept' => 'application/xml, text/xml, */*' ),
		) );
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$items = $this->imoti_parse_location_xml( wp_remote_retrieve_body( $response ) );
		set_transient( $cache_key, $items, DAY_IN_SECONDS );

		return $items;
	}

	/**
	 * Parse imoti.net getData XML into a flat array of location items.
	 *
	 * Expected XML structure: root element containing child elements with
	 * 'id', 'title', and optionally 'extra' (parent region ID) attributes.
	 *
	 * @param string $xml_body
	 * @return array[]  [ [ 'id' => string, 'title' => string, 'extra' => string ], … ]
	 */
	private function imoti_parse_location_xml( $xml_body ) {
		if ( empty( $xml_body ) ) {
			return array();
		}

		$items = array();

		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_body );
		libxml_clear_errors();

		if ( false === $xml ) {
			return array();
		}

		// XML structure: <rss><channel><item><title>ID</title><description>Name</description><extra>ParentID</extra></item>…
		$channel = isset( $xml->channel ) ? $xml->channel : $xml;
		foreach ( $channel->item as $node ) {
			$id    = (string) $node->title;
			$title = (string) $node->description;
			$extra = (string) $node->extra;
			if ( '' !== $id ) {
				$items[] = array(
					'id'    => $id,
					'title' => $title,
					'extra' => $extra,
				);
			}
		}

		return $items;
	}

	/**
	 * Filter regions by search term and return formatted items.
	 * Label format: "{id} {title}"
	 *
	 * @param array[]  $regions
	 * @param string   $q
	 * @return array[]  [ [ 'label', 'value' ], … ]
	 */
	private function imoti_filter_regions( array $regions, $q ) {
		$items = array();
		foreach ( $regions as $r ) {
			if ( '' !== $q ) {
				$q_lower = mb_strtolower( $q );
				if (
					false === mb_stripos( $r['title'], $q_lower ) &&
					false === strpos( $r['id'], $q_lower )
				) {
					continue;
				}
			}
			$items[] = array(
				'label' => $r['id'] . ' ' . $r['title'],
				'value' => $r['id'],
			);
			if ( count( $items ) >= 15 ) {
				break;
			}
		}
		return $items;
	}

	/**
	 * Filter subareas by search term, attaching parent region name.
	 * Label format: "{region_id} {region_name} -> {subarea_id} {subarea_title}"
	 *
	 * @param array[]  $subareas
	 * @param array[]  $regions   Full regions list (for label lookup).
	 * @param string   $q
	 * @return array[]  [ [ 'label', 'value' ], … ]
	 */
	private function imoti_filter_subareas( array $subareas, array $regions, $q ) {
		$region_map = array();
		foreach ( $regions as $r ) {
			$region_map[ $r['id'] ] = $r['title'];
		}

		$items = array();
		foreach ( $subareas as $s ) {
			if ( '' !== $q ) {
				$q_lower = mb_strtolower( $q );
				if (
					false === mb_stripos( $s['title'], $q_lower ) &&
					false === strpos( $s['id'], $q_lower )
				) {
					continue;
				}
			}
			$region_id   = $s['extra'];
			$region_name = isset( $region_map[ $region_id ] ) ? $region_map[ $region_id ] : $region_id;
			$items[]     = array(
				'label' => $region_id . ' ' . $region_name . ' -> ' . $s['id'] . ' ' . $s['title'],
				'value' => $s['id'],
			);
			if ( count( $items ) >= 15 ) {
				break;
			}
		}
		return $items;
	}

	// =========================================================================
	// AJAX: Realistimo geo location search
	// =========================================================================

	/**
	 * AJAX handler: search Realistimo geo locations from the bundled CSV.
	 *
	 * POST params:
	 *   q    string  Search term.
	 *   val  string  Resolve a single internal_id → label (page-load display).
	 *
	 * Returns: { items: [ { label, value }, … ] }
	 */
	public function ajax_realistimo_search_location() {
		check_ajax_referer( 're_exporter_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden' );
		}

		$q   = isset( $_POST['q'] )   ? sanitize_text_field( wp_unslash( $_POST['q'] ) )   : '';
		$val = isset( $_POST['val'] ) ? sanitize_text_field( wp_unslash( $_POST['val'] ) ) : '';

		// Resolve: val → label (page-load).
		if ( '' !== $val && '' === $q ) {
			$label = Realistimo_Geo::get_label( $val );
			wp_send_json_success( array( 'items' => array(
				array( 'label' => $label, 'value' => $val ),
			) ) );
		}

		$items = Realistimo_Geo::search( $q, 15 );
		wp_send_json_success( array( 'items' => $items ) );
	}
}
