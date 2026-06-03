<?php
/**
 * Generates OLX.bg CSV export files driven by the client template headers.
 *
 * For each export run:
 *   1. Groups posts by resolved subcategory_id.
 *   2. Per group reads the exact CSV column order from the template file.
 *   3. Resolves each column value using the saved field map + value overrides
 *      + per-category field_map overrides (e.g. houses_sale: floors → house_floors).
 *   4. Writes one UTF-8 BOM + semicolon-delimited CSV per subcategory group.
 *
 * Output path: {uploads}/re-exporter/olx/{timestamp}/{subcategory_id}.csv
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Exporter_OLX
 */
class Exporter_OLX extends Abstract_Exporter {

	/** @var Settings */
	private $settings;

	/** @var OLX_Template */
	private $olx_template;

	/** @var Field_Resolver */
	private $resolver;

	/** @var OLX_Category_Resolver */
	private $category_resolver;

	/**
	 * Per-run in-memory cache: city name (lowercased) → OLX city ID.
	 *
	 * @var array<string,string>
	 */
	private $city_id_cache = array();

	/**
	 * @param Settings              $settings
	 * @param OLX_Template          $olx_template
	 * @param OLX_Category_Resolver $category_resolver
	 */
	public function __construct( Settings $settings, OLX_Template $olx_template, OLX_Category_Resolver $category_resolver ) {
		$this->settings          = $settings;
		$this->olx_template      = $olx_template;
		$this->resolver          = new Field_Resolver();
		$this->category_resolver = $category_resolver;
	}

	// =========================================================================
	// Public API
	// =========================================================================

	/**
	 * Generate CSV files for the given post IDs.
	 *
	 * @param int[]  $post_ids
	 * @param string $out_dir   Absolute path to the output directory.
	 * @return array[]|\WP_Error  On success: array of file descriptors
	 *                            [ 'filename', 'filepath', 'url', 'count' ]
	 */
	public function generate( array $post_ids, $out_dir ) {
		$field_map    = $this->settings->get_olx_field_map();
		$value_map    = $this->settings->get_olx_value_map();
		$req_map      = $this->settings->get_olx_required_map();

		// ── Group posts by subcategory ────────────────────────────────────────
		$groups = array(); // [ subcat_id => WP_Post[] ]

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$subcat_id = $this->category_resolver->resolve_subcategory_id( $post );
			if ( ! $subcat_id ) {
				continue; // Skip posts without a category assignment.
			}

			$groups[ $subcat_id ][] = $post;
		}

		if ( empty( $groups ) ) {
			return new \WP_Error(
				'no_categories',
				__( 'No posts with an OLX category assigned were found.', 're-exporter' )
			);
		}

		// ── Generate one CSV per group ────────────────────────────────────────
		$files = array();

		foreach ( $groups as $subcat_id => $posts ) {
			$csv_path = $this->olx_template->get_csv_path( $subcat_id );
			if ( ! $csv_path ) {
				continue; // Unknown subcategory — skip.
			}

			$headers  = $this->olx_template->get_csv_headers( $csv_path );
			if ( empty( $headers ) ) {
				continue;
			}

			// Merged field map: global B1 + per-category B4 supplemental.
			$cat_req_map  = isset( $req_map[ $subcat_id ] ) ? (array) $req_map[ $subcat_id ] : array();
			$merged_map   = array_merge( $field_map, $cat_req_map );

			// field_map overrides from the template definition
			// (e.g. houses_sale: floors → house_floors for JSON lookup only).
			$json_key_overrides = $this->olx_template->get_field_map_overrides( $subcat_id );

			$rows = array();
			foreach ( $posts as $post ) {
				$rows[] = $this->build_row( $post, $headers, $merged_map, $value_map, $json_key_overrides );
			}

			$filename   = sanitize_file_name( $subcat_id . '.csv' );
			$descriptor = $this->build_run_file_descriptor( 'olx', $out_dir, $filename, count( $posts ) );
			$filepath   = $descriptor['filepath'];

			$write = $this->write_csv( $filepath, $headers, $rows );
			if ( is_wp_error( $write ) ) {
				return $write;
			}

			$subcat     = $this->olx_template->get_subcategory_by_id( $subcat_id );
			$group_name = ( $subcat && ! empty( $subcat['group_id'] ) )
				? $this->olx_template->get_group_name( $subcat['group_id'] )
				: '';
			$cat_label  = $subcat
				? ( $group_name ? $group_name . ' → ' . $subcat['name'] : $subcat['name'] )
				: $subcat_id;

			$files[] = array(
				'filename'      => $descriptor['filename'],
				'filepath'      => $descriptor['filepath'],
				'url'           => $descriptor['url'],
				'count'         => $descriptor['count'],
				'category_name' => $cat_label,
			);
		}

		if ( empty( $files ) ) {
			return new \WP_Error( 'no_output', __( 'No CSV files were generated.', 're-exporter' ) );
		}

		return $files;
	}

	// =========================================================================
	// Row Building
	// =========================================================================

	/**
	 * Build a single CSV row for a post.
	 *
	 * @param \WP_Post $post
	 * @param string[] $headers          Ordered column names from the CSV template.
	 * @param array    $merged_map       Combined global + category field map.
	 * @param array    $value_map        [ column ][ wp_value ] => olx_value
	 * @param array    $json_key_overrides [ field_key => json_key ] for this subcategory.
	 * @return array   Ordered values (same order as $headers).
	 */
	private function build_row(
		\WP_Post $post,
		array $headers,
		array $merged_map,
		array $value_map,
		array $json_key_overrides
	) {
		$row = array();

		foreach ( $headers as $col ) {
			$source = isset( $merged_map[ $col ] ) ? $merged_map[ $col ] : '__skip__';

			if ( '__skip__' === $source || '' === $source ) {
				$row[] = '';
				continue;
			}

			// Per-post manual sources: value is set per-post in the metabox,
			// stored as _re_olx_field_{col}. Already an OLX-native value — skip B2 overrides.
			if ( 0 === strpos( $source, '__json__' )
				|| '__text__'    === $source
				|| '__number__'  === $source
				|| '__city__'    === $source
				|| '__district__' === $source
			) {
				$row[] = (string) get_post_meta( $post->ID, '_re_olx_field_' . $col, true );
				continue;
			}

			$value = $this->resolver->resolve( $post, $source, 'text' );

			// ── Images: join array with pipe, max 24 ────────────────────────
			if ( 'images' === $col ) {
				if ( is_array( $value ) ) {
					$value = implode( '|', array_filter( array_slice( $value, 0, 24 ) ) );
				} else {
					$parts = array_filter( explode( '|', (string) $value ) );
					$value = implode( '|', array_slice( $parts, 0, 24 ) );
				}
			}

			// ── Title: strip HTML, truncate to 70 chars ──────────────────────
			if ( 'title' === $col ) {
				$value = wp_strip_all_tags( (string) $value );
				if ( mb_strlen( $value ) > 70 ) {
					$value = mb_substr( $value, 0, 70 );
				}
			}

			// ── Description: strip HTML, remove emoji/symbols ────────────────
			if ( 'description' === $col ) {
				$value = wp_strip_all_tags( (string) $value );
				// Keep only letters, digits, punctuation and separators (strips emoji, symbols).
				$value = preg_replace( '/[^\p{L}\p{N}\p{P}\p{Z}]/u', '', $value );
				// Collapse multiple spaces/newlines left after stripping.
				$value = preg_replace( '/\s+/', ' ', trim( $value ) );
			}

			$value = (string) $value;

			// ── City: auto-resolve name → OLX numeric ID ──────────────────
			if ( 'location_city' === $col && '' !== $value && ! is_numeric( $value ) ) {
				$value = $this->resolve_city_id( $value );
			}

			// ── Value Override (B2) ───────────────────────────────────────────
			// Determine the JSON-key for this column (may be overridden by field_map).
			$json_key = isset( $json_key_overrides[ $col ] )
				? $json_key_overrides[ $col ]
				: $this->olx_template->get_json_key_for_column( $col );

			// Use the json_key as the value_map lookup key so overrides work correctly.
			$vm_key = isset( $json_key_overrides[ $col ] ) ? $json_key : $col;

			if ( isset( $value_map[ $vm_key ][ $value ] ) ) {
				$value = $value_map[ $vm_key ][ $value ];
			} elseif ( isset( $value_map[ $col ][ $value ] ) ) {
				// Fallback to column-name lookup for backwards compatibility.
				$value = $value_map[ $col ][ $value ];
			}

			$row[] = $value;
		}

		return $row;
	}

	// =========================================================================
	// City Name → ID Resolution
	// =========================================================================

	/**
	 * Resolve a plain city name to its OLX numeric city ID.
	 *
	 * Flow:
	 *   1. Return as-is if already numeric (already an ID).
	 *   2. Check the in-memory per-run cache.
	 *   3. Check the WP-options reverse cache (persisted between runs).
	 *   4. Call the OLX geo-encoder API, cache the result.
	 *   5. Return original name as fallback if the API fails.
	 *
	 * @param string $city_name  Raw city name, e.g. "Варна".
	 * @return string  OLX city ID (numeric string) or original name on failure.
	 */
	private function resolve_city_id( $city_name ) {
		if ( '' === $city_name || is_numeric( $city_name ) ) {
			return $city_name;
		}

		$key     = mb_strtolower( trim( $city_name ) );
		$country = $this->settings->get_olx_country();

		// 1. In-memory cache (per export run).
		if ( isset( $this->city_id_cache[ $key ] ) ) {
			return $this->city_id_cache[ $key ];
		}

		// 2. Persisted reverse cache: lowercased name → OLX ID.
		$opt_key   = 're_exporter_city_name_cache_' . $country;
		$rev_cache = (array) get_option( $opt_key, array() );

		if ( isset( $rev_cache[ $key ] ) ) {
			$this->city_id_cache[ $key ] = $rev_cache[ $key ];
			return $rev_cache[ $key ];
		}

		// 3. OLX geo-encoder API.
		$base_url = $this->settings->get_olx_country_base_url();
		$api_url  = $base_url . '/api/v1/geo-encoder/location-autocomplete/?query=' . rawurlencode( $city_name );

		$response = wp_remote_get( $api_url, array(
			'timeout' => 5,
			'headers' => array( 'Accept' => 'application/json' ),
		) );

		if ( is_wp_error( $response ) ) {
			return $city_name;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return $city_name;
		}

		$city_id      = '';
		$city_id_best = ''; // Exact match candidate.

		foreach ( $data['data'] as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['city'] ) || ! is_array( $entry['city'] ) ) {
				continue;
			}

			$city     = $entry['city'];
			$district = ( ! empty( $entry['district'] ) && is_array( $entry['district'] ) && ! empty( $entry['district']['id'] ) )
				? $entry['district']
				: null;

			$id = $district ? (string) $district['id'] : (string) $city['id'];

			// Keep the first result as a fallback.
			if ( '' === $city_id ) {
				$city_id = $id;
			}

			// Prefer an exact case-insensitive match on city name.
			if ( isset( $city['name'] ) && mb_strtolower( $city['name'] ) === $key ) {
				$city_id_best = $id;
				break;
			}
		}

		$resolved = $city_id_best ?: $city_id;

		if ( '' !== $resolved ) {
			// Store in both caches.
			$this->city_id_cache[ $key ] = $resolved;
			$rev_cache[ $key ]           = $resolved;

			if ( count( $rev_cache ) > 1000 ) {
				$rev_cache = array_slice( $rev_cache, -1000, 1000, true );
			}
			update_option( $opt_key, $rev_cache, false );

			return $resolved;
		}

		return $city_name; // API returned nothing useful — keep original.
	}

	// =========================================================================
	// CSV Writing
	// =========================================================================

	/**
	 * Write a CSV file using WP_Filesystem.
	 *
	 * Format: UTF-8 with BOM, semicolon delimiter, fputcsv quoting rules.
	 *
	 * @param string   $filepath
	 * @param string[] $headers
	 * @param array[]  $rows     Each row is an ordered array of scalar values.
	 * @return true|\WP_Error
	 */
	private function write_csv( $filepath, array $headers, array $rows ) {
		// Build CSV in a temp stream so we can prepend the UTF-8 BOM cleanly.
		$buf = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $buf ) {
			return new \WP_Error( 'csv_stream', __( 'Could not open temp stream.', 're-exporter' ) );
		}

		fputcsv( $buf, $headers, ';' );
		foreach ( $rows as $row ) {
			fputcsv( $buf, $row, ';' );
		}

		rewind( $buf );
		$content = stream_get_contents( $buf );
		fclose( $buf ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		// Prepend UTF-8 BOM so Excel opens the file correctly.
		$content = "\xEF\xBB\xBF" . $content;

		return $this->write_file(
			$filepath,
			$content,
			'csv_write',
			/* translators: %s = file path */
			__( 'Could not write file: %s', 're-exporter' )
		);
	}
}
