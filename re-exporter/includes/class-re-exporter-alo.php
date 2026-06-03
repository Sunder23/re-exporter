<?php
/**
 * Generates ALO.bg JSON export files.
 *
 * Follows the official ALO.bg import specification.
 * Posts are grouped by resolved ALO subcategory ID; one JSON file is written
 * per subcategory group.  The field set for each file is driven by the
 * matching JSON template in templates/alo_bg/.
 *
 * Format:
 *  - UTF-8, no BOM
 *  - JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
 *  - Root = array of ad objects (flat + nested map{} and contacts{})
 *  - images = JSON array of URLs, max 15
 *  - contacts.phones = JSON array of strings, max 5
 *  - HTML stripped from desc / desc_en / desc_ru
 *
 * Output path: {uploads}/re-exporter/alo/{timestamp}/{subcategory_id}.json
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Exporter_ALO
 */
class Exporter_ALO extends Abstract_Exporter {

	/** @var Settings */
	private $settings;

	/** @var ALO_Template */
	private $alo_template;

	/** @var Field_Resolver */
	private $resolver;

	/** @var ALO_Category_Resolver */
	private $category_resolver;

	/**
	 * Multi-value (array) field keys per the ALO spec.
	 *
	 * @var string[]
	 */
	private static $array_fields = array(
		'features',
		'suitable_for',
		'prefer',
		'season',
		'is_working',
	);

	/**
	 * @param Settings              $settings
	 * @param ALO_Template          $alo_template
	 * @param ALO_Category_Resolver $category_resolver
	 */
	public function __construct( Settings $settings, ALO_Template $alo_template, ALO_Category_Resolver $category_resolver ) {
		$this->settings          = $settings;
		$this->alo_template      = $alo_template;
		$this->resolver          = new Field_Resolver();
		$this->category_resolver = $category_resolver;
	}

	// =========================================================================
	// Public API
	// =========================================================================

	/**
	 * Generate JSON files for the given post IDs.
	 *
	 * @param int[]  $post_ids
	 * @param string $out_dir   Absolute path to the output directory.
	 * @return array[]|\WP_Error  On success: array of file descriptors
	 *                            [ 'filename', 'filepath', 'url', 'count' ]
	 */
	public function generate( array $post_ids, $out_dir ) {
		$field_map    = $this->settings->get_alo_field_map();
		$value_map    = $this->settings->get_alo_value_map();
		$req_map      = $this->settings->get_alo_required_map();

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
				__( 'No posts with an ALO category assigned were found.', 're-exporter' )
			);
		}

		// ── Generate one JSON per group ───────────────────────────────────────
		$files      = array();
		$all_output = array();

		foreach ( $groups as $subcat_id => $posts ) {
			$sub = $this->alo_template->get_subcategory_by_id( $subcat_id );
			if ( ! $sub || empty( $sub['template']['path'] ) ) {
				continue; // Unknown subcategory — skip.
			}

			$template_fields = $this->alo_template->get_template_fields( $sub['template']['path'] );
			if ( empty( $template_fields ) ) {
				continue;
			}

			// Merged field map: global + per-category supplemental (required_map).
			$cat_req_map = isset( $req_map[ $subcat_id ] ) ? (array) $req_map[ $subcat_id ] : array();
			$merged_map  = array_merge( $field_map, $cat_req_map );

			$output = array();
			foreach ( $posts as $post ) {
				$output[] = $this->build_object( $post, $template_fields, $subcat_id, $merged_map, $value_map );
			}

			$json = json_encode(
				$output,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

			if ( false === $json ) {
				return new \WP_Error( 'json_encode', __( 'Could not encode export data as JSON.', 're-exporter' ) );
			}

			$filename   = sanitize_file_name( $subcat_id . '.json' );
			$descriptor = $this->build_run_file_descriptor( 'alo', $out_dir, $filename, count( $posts ), $sub['name'] );
			$filepath   = $descriptor['filepath'];

			$write = $this->write_file(
				$filepath,
				$json,
				'json_write',
				/* translators: %s = file path */
				__( 'Could not write file: %s', 're-exporter' )
			);
			if ( is_wp_error( $write ) ) {
				return $write;
			}

			$files[] = $descriptor;

			$all_output = array_merge( $all_output, $output );
		}

		if ( empty( $files ) ) {
			return new \WP_Error( 'no_output', __( 'No JSON files were generated.', 're-exporter' ) );
		}

		// ── Write combined all.json ───────────────────────────────────────────
		$all_json = json_encode(
			$all_output,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		if ( false !== $all_json ) {
			$all_descriptor = $this->build_run_file_descriptor(
				'alo',
				$out_dir,
				'all.json',
				count( $all_output ),
				__( 'All categories (combined)', 're-exporter' )
			);
			$write          = $this->write_file(
				$all_descriptor['filepath'],
				$all_json,
				'json_write',
				/* translators: %s = file path */
				__( 'Could not write file: %s', 're-exporter' )
			);
			if ( ! is_wp_error( $write ) ) {
				$files[] = $all_descriptor;
			}
		}

		return $files;
	}

	// =========================================================================
	// Object Building
	// =========================================================================

	/**
	 * Build a single ALO ad object for one post.
	 *
	 * Only includes fields defined in the subcategory's JSON template.
	 * Nested objects (map, contacts) are assembled from dot-notation field keys.
	 *
	 * @param \WP_Post $post
	 * @param string[] $template_fields  Field keys from the JSON template (may include 'map.lat', 'contacts.phones', …)
	 * @param string   $subcat_id
	 * @param array    $merged_map       Combined field map (global + per-category).
	 * @param array    $value_map        [ field_key => [ wp_value => alo_value ] ]
	 * @return array
	 */
	private function build_object(
		\WP_Post $post,
		array $template_fields,
		$subcat_id,
		array $merged_map,
		array $value_map
	) {
		/**
		 * Resolve an ALO field key to its raw value.
		 *
		 * @param string $key  Flat field key (may use dot notation for nested fields).
		 * @return mixed|null
		 */
		$get = function ( $key ) use ( $post, $merged_map, $value_map, $subcat_id ) {
			// Field-map fields are set per-post in the metabox; read directly from post meta.
			$subcats_field_map = $this->alo_template->get_field_map( $subcat_id );
			if ( isset( $subcats_field_map[ $key ] ) ) {
				$value = get_post_meta( $post->ID, '_re_alo_field_' . $key, true );
				return ( '' !== $value && null !== $value ) ? $value : null;
			}

			$source = isset( $merged_map[ $key ] ) ? $merged_map[ $key ] : '__skip__';
			if ( '__skip__' === $source || '' === $source ) {
				return null;
			}

			// ALO location source types (per-post location widget).
			static $alo_location_meta = array(
				'__alo_region__'         => '_re_alo_region_name',
				'__alo_location__'       => '_re_alo_location_name',
				'__alo_section__'        => '_re_alo_section_name',
				'__alo_section_string__' => '_re_alo_section_string',
			);
			if ( isset( $alo_location_meta[ $source ] ) ) {
				$value = get_post_meta( $post->ID, $alo_location_meta[ $source ], true );
				return ( '' !== $value && null !== $value ) ? $value : null;
			}

			// Per-post manual text/number input (set via Metabox 4).
			if ( '__text__' === $source || '__number__' === $source ) {
				$value = get_post_meta( $post->ID, '_re_alo_manual_' . $key, true );
				return ( '' !== $value && null !== $value ) ? $value : null;
			}

			$value = $this->resolver->resolve( $post, $source, 'raw' );

			// Apply value override using the effective JSON key for this field.
			if ( null !== $value && ! is_array( $value ) ) {
				$str      = (string) $value;
				$json_key = $this->alo_template->get_effective_json_key( $subcat_id, $key );

				if ( isset( $value_map[ $json_key ][ $str ] ) ) {
					$value = $value_map[ $json_key ][ $str ];
				} elseif ( isset( $value_map[ $key ][ $str ] ) ) {
					$value = $value_map[ $key ][ $str ];
				}
			}

			return $value;
		};

		/**
		 * Coerce a resolved value into a clean array of strings.
		 *
		 * @param mixed $value
		 * @return array
		 */
		$to_array = static function ( $value ) {
			if ( ! is_array( $value ) ) {
				$value = ( null !== $value && '' !== $value ) ? array( (string) $value ) : array();
			}
			return array_values( array_filter( $value ) );
		};

		// ── Start assembling ──────────────────────────────────────────────────
		// out_id and subcat_id are always present; out_id comes from the field map
		// with a fallback to the WP post ID; subcat_id is the category definition value.
		$obj          = array( 'out_id' => (string) ( $get( 'out_id' ) ?: $post->ID ) );
		$obj['subcat_id'] = (string) $subcat_id;

		$map_obj = array();

		// ── Static contacts from C6 settings ──────────────────────────────────
		$contacts_obj    = array();
		$static_contacts = $this->settings->get_alo_contacts();

		if ( ! empty( $static_contacts['phones'] ) ) {
			$contacts_obj['phones'] = array_slice( (array) $static_contacts['phones'], 0, 5 );
		}
		if ( '' !== $static_contacts['email'] ) {
			$contacts_obj['email'] = $static_contacts['email'];
		}
		foreach ( array( 'hide_name', 'hide_location', 'hide_address', 'hide_website' ) as $_ct ) {
			if ( 'TRUE' === $static_contacts[ $_ct ] ) {
				$contacts_obj[ $_ct ] = 'TRUE';
			}
		}

		foreach ( $template_fields as $field ) {
			// Already handled above.
			if ( 'out_id' === $field || 'subcat_id' === $field ) {
				continue;
			}

			// ── Nested: map.* ─────────────────────────────────────────────────
			if ( 0 === strpos( $field, 'map.' ) ) {
				$sub_key = substr( $field, 4 );
				$v       = $get( $field );
				if ( null !== $v && '' !== $v ) {
					$map_obj[ $sub_key ] = $v;
				}
				continue;
			}

			// ── Nested: contacts.* — handled by static C6 section; skip. ──────
			if ( 0 === strpos( $field, 'contacts.' ) ) {
				continue;
			}

			// ── images (max 15) ───────────────────────────────────────────────
			if ( 'images' === $field ) {
				$arr = array_slice( $to_array( $get( $field ) ), 0, 15 );
				if ( ! empty( $arr ) ) {
					$obj['images'] = $arr;
				}
				continue;
			}

			// ── Descriptions: strip HTML ──────────────────────────────────────
			if ( in_array( $field, array( 'desc', 'desc_en', 'desc_ru' ), true ) ) {
				$v = $get( $field );
				if ( $v ) {
					$obj[ $field ] = wp_strip_all_tags( (string) $v );
				}
				continue;
			}

			// ── Array fields (multi-select) ───────────────────────────────────
			if ( in_array( $field, self::$array_fields, true ) ) {
				$arr = $to_array( $get( $field ) );
				if ( ! empty( $arr ) ) {
					$obj[ $field ] = $arr;
				}
				continue;
			}

			// ── Scalar field ──────────────────────────────────────────────────
			$v = $get( $field );
			if ( null !== $v && '' !== $v ) {
				$obj[ $field ] = $v;
			} elseif ( 'section_name' === $field ) {
				$obj[ $field ] = "NULL";
			}
		}

		// Append nested objects at the end (spec order: map before contacts).
		if ( ! empty( $map_obj ) ) {
			$obj['map'] = $map_obj;
		}
		if ( ! empty( $contacts_obj ) ) {
			$obj['contacts'] = $contacts_obj;
		}

		return $obj;
	}

}
