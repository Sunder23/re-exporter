<?php
/**
 * Reads and parses the ALO.bg template files.
 *
 * Responsibilities:
 *  - Load $categories from templates/alo_bg/alo_categorys.php (read-only)
 *  - Parse JSON template files to discover the field set for each subcategory
 *  - Read JSON allowed-value lists from templates/alo_bg/json/
 *
 * ⚠️  NEVER modifies any file inside templates/alo_bg/.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ALO_Template
 */
class ALO_Template {

	/**
	 * Internal cache for the parsed $categories array.
	 *
	 * @var array|null
	 */
	private $categories_cache = null;

	/**
	 * Internal cache for template field lists, keyed by template path.
	 *
	 * @var array<string,string[]>
	 */
	private $fields_cache = array();

	/**
	 * Internal cache for JSON values, keyed by field key.
	 *
	 * @var array<string,array>
	 */
	private $json_cache = array();

	// =========================================================================
	// Categories
	// =========================================================================

	/**
	 * Return the full $categories array from alo_categorys.php.
	 *
	 * Each top-level entry has:
	 *   'id', 'name', 'template' (null for groups), 'subcategories' (array)
	 *
	 * @return array
	 */
	public function get_categories() {
		if ( null !== $this->categories_cache ) {
			return $this->categories_cache;
		}

		$file = RE_EXPORTER_ALO_TEMPLATES . 'alo_categorys.php';

		if ( ! file_exists( $file ) ) {
			$this->categories_cache = array();
			return $this->categories_cache;
		}

		// Include inside a function scope so $categories is captured cleanly.
		$categories = ( static function ( $path ) {
			$categories = array(); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis
			include $path;
			return $categories;
		} )( $file );

		$this->categories_cache = is_array( $categories ) ? $categories : array();
		return $this->categories_cache;
	}

	/**
	 * Return a flat list of all subcategories from both 'sales' and 'rent' groups.
	 *
	 * Each entry is the original subcategory array, extended with 'group_id'.
	 *
	 * @return array[]
	 */
	public function get_all_subcategories() {
		$result = array();

		foreach ( $this->get_categories() as $group ) {
			if ( empty( $group['subcategories'] ) || ! is_array( $group['subcategories'] ) ) {
				continue;
			}
			foreach ( $group['subcategories'] as $sub ) {
				$result[] = array_merge( $sub, array( 'group_id' => $group['id'] ) );
			}
		}

		return $result;
	}

	/**
	 * Find and return a subcategory definition by its numeric ID.
	 *
	 * @param string $id  Numeric subcategory ID, e.g. '456'.
	 * @return array|null  Full subcategory array or null if not found.
	 */
	public function get_subcategory_by_id( $id ) {
		foreach ( $this->get_all_subcategories() as $sub ) {
			if ( isset( $sub['id'] ) && (string) $sub['id'] === (string) $id ) {
				return $sub;
			}
		}
		return null;
	}

	/**
	 * Return subcategories grouped for display (Продажби / Наеми).
	 *
	 * @return array[]  Same structure as get_categories() but subcategory arrays
	 *                  are enriched with 'group_id'.
	 */
	public function get_grouped_subcategories() {
		$groups = array();

		foreach ( $this->get_categories() as $group ) {
			if ( empty( $group['subcategories'] ) ) {
				continue;
			}
			$subs = array();
			foreach ( $group['subcategories'] as $sub ) {
				$subs[] = array_merge( $sub, array( 'group_id' => $group['id'] ) );
			}
			$groups[] = array(
				'id'            => $group['id'],
				'name'          => $group['name'],
				'subcategories' => $subs,
			);
		}

		return $groups;
	}

	// =========================================================================
	// Template Fields (JSON template parsing)
	// =========================================================================

	/**
	 * Return the flat list of field keys defined in a JSON template file.
	 *
	 * Nested objects (map, contacts) are expanded with dot notation:
	 *   map.lat, map.long
	 *   contacts.phones, contacts.email, contacts.hide_name, …
	 *
	 * Indexed arrays (images, features) are kept as single flat keys.
	 *
	 * @param string $template_path  Relative path to plugin root,
	 *                               e.g. 'templates/alo_bg/sales/apartments.json'
	 * @return string[]  Ordered field key list (empty on failure).
	 */
	public function get_template_fields( $template_path ) {
		if ( isset( $this->fields_cache[ $template_path ] ) ) {
			return $this->fields_cache[ $template_path ];
		}

		$filepath = RE_EXPORTER_DIR . $template_path;

		if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) ) {
			$this->fields_cache[ $template_path ] = array();
			return array();
		}

		$raw  = file_get_contents( $filepath ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = $raw ? json_decode( $raw, true ) : null;

		if ( ! is_array( $data ) ) {
			$this->fields_cache[ $template_path ] = array();
			return array();
		}

		$fields = array();

		foreach ( $data as $key => $value ) {
			// Skip comments and meta-only keys.
			if ( '_comment' === $key ) {
				continue;
			}

			if ( is_array( $value ) && ! $this->is_indexed_array( $value ) ) {
				// Associative nested object (map{}, contacts{}) — flatten with dot notation.
				foreach ( $value as $sub_key => $sub_val ) {
					$fields[] = $key . '.' . $sub_key;
				}
			} else {
				// Scalar or indexed array (images[], features[], etc.) — keep as-is.
				$fields[] = $key;
			}
		}

		$this->fields_cache[ $template_path ] = $fields;
		return $fields;
	}

	/**
	 * Return the union of all field keys across every JSON template.
	 *
	 * @return string[]
	 */
	public function get_all_fields() {
		$fields = array();

		foreach ( $this->get_all_subcategories() as $sub ) {
			if ( empty( $sub['template']['path'] ) ) {
				continue;
			}
			foreach ( $this->get_template_fields( $sub['template']['path'] ) as $f ) {
				if ( '' !== $f && ! in_array( $f, $fields, true ) ) {
					$fields[] = $f;
				}
			}
		}

		return $fields;
	}

	/**
	 * Return the JSON template path (relative to plugin root) for a subcategory.
	 *
	 * @param string $subcategory_id
	 * @return string|null
	 */
	public function get_json_path( $subcategory_id ) {
		$sub = $this->get_subcategory_by_id( $subcategory_id );
		if ( $sub && ! empty( $sub['template']['path'] ) ) {
			return $sub['template']['path'];
		}
		return null;
	}

	// =========================================================================
	// Required Fields
	// =========================================================================

	/**
	 * Return the required_fields array for a subcategory.
	 *
	 * @param string $subcategory_id
	 * @return string[]
	 */
	public function get_required_fields( $subcategory_id ) {
		$sub = $this->get_subcategory_by_id( $subcategory_id );
		if ( $sub && ! empty( $sub['template']['required_fields'] ) ) {
			return (array) $sub['template']['required_fields'];
		}
		return array();
	}

	// =========================================================================
	// Field Map (ALO key → JSON lookup file key)
	// =========================================================================

	/**
	 * Return the field_map for a subcategory.
	 *
	 * Maps ALO field keys to their JSON lookup filenames (without .json extension).
	 * e.g. for subcat 456: [ 'type' => 'type_apartments_sale', 'features' => 'features_apartments_sale' ]
	 *
	 * @param string $subcategory_id
	 * @return array<string,string>  Empty array if no map defined.
	 */
	public function get_field_map( $subcategory_id ) {
		$sub = $this->get_subcategory_by_id( $subcategory_id );
		if ( $sub && ! empty( $sub['template']['field_map'] ) && is_array( $sub['template']['field_map'] ) ) {
			return $sub['template']['field_map'];
		}
		return array();
	}

	/**
	 * Get the effective JSON lookup key for a field within a specific subcategory.
	 *
	 * Falls back to the field key itself if no override is defined.
	 *
	 * e.g. subcat 456 / 'type' → 'type_apartments_sale'
	 *      subcat 456 / 'area' → 'area'
	 *
	 * @param string $subcategory_id
	 * @param string $field_key
	 * @return string  Effective JSON key to use for value lookups.
	 */
	public function get_effective_json_key( $subcategory_id, $field_key ) {
		$field_map = $this->get_field_map( $subcategory_id );
		return isset( $field_map[ $field_key ] ) ? $field_map[ $field_key ] : $field_key;
	}

	// =========================================================================
	// JSON Value Files
	// =========================================================================

	/**
	 * Check whether a JSON allowed-values file exists for a field key.
	 *
	 * @param string $field_key  Exact JSON filename key (no .json extension).
	 * @return bool
	 */
	public function has_json_values( $field_key ) {
		return file_exists( RE_EXPORTER_ALO_TEMPLATES . 'json/' . $field_key . '.json' );
	}

	/**
	 * Return the allowed values from a JSON file.
	 *
	 * Parses plain string arrays:
	 *   ["value1", "value2", ...]
	 *
	 * Each item is returned as [ 'label' => string, 'value' => string ].
	 *
	 * @param string $field_key  JSON filename without extension.
	 * @return array[]  Array of [ 'label' => string, 'value' => string ]
	 */
	public function get_json_values( $field_key ) {
		if ( isset( $this->json_cache[ $field_key ] ) ) {
			return $this->json_cache[ $field_key ];
		}

		$filepath = RE_EXPORTER_ALO_TEMPLATES . 'json/' . $field_key . '.json';

		if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) ) {
			$this->json_cache[ $field_key ] = array();
			return array();
		}

		$raw = file_get_contents( $filepath ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! $raw ) {
			$this->json_cache[ $field_key ] = array();
			return array();
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			$this->json_cache[ $field_key ] = array();
			return array();
		}

		// Plain array of strings or objects.
		if ( isset( $data[0] ) ) {
			$result = array();
			foreach ( $data as $item ) {
				if ( is_string( $item ) ) {
					$result[] = array( 'label' => $item, 'value' => $item );
				} elseif ( is_array( $item ) ) {
					$v        = isset( $item['value'] ) ? (string) $item['value'] : (string) reset( $item );
					$l        = isset( $item['label'] ) ? (string) $item['label'] : $v;
					$result[] = array( 'label' => $l, 'value' => $v );
				}
			}
			$this->json_cache[ $field_key ] = $result;
			return $result;
		}

		$this->json_cache[ $field_key ] = array();
		return array();
	}

	/**
	 * Return all unique JSON lookup keys referenced for a field across all subcategories.
	 *
	 * Checks the field_map of every subcategory; also checks if a file named
	 * after the field key itself exists.  Used by the B2 Value Overrides section.
	 *
	 * @param string $field_key  ALO field key (e.g. 'type', 'construction_type').
	 * @return string[]  Unique JSON keys (filename without .json extension).
	 */
	public function get_json_keys_for_field( $field_key ) {
		$json_keys = array();

		// Field key itself may have a direct JSON file.
		if ( $this->has_json_values( $field_key ) ) {
			$json_keys[] = $field_key;
		}

		// Scan subcategory field_maps.
		foreach ( $this->get_all_subcategories() as $sub ) {
			if ( empty( $sub['template']['field_map'][ $field_key ] ) ) {
				continue;
			}
			$jk = $sub['template']['field_map'][ $field_key ];
			if ( ! in_array( $jk, $json_keys, true ) && $this->has_json_values( $jk ) ) {
				$json_keys[] = $jk;
			}
		}

		return $json_keys;
	}

	// =========================================================================
	// Locations (region_name / location_name / section_name)
	// =========================================================================

	/**
	 * Internal cache for locations.json data.
	 *
	 * @var array|null
	 */
	private $locations_data_cache = null;

	/**
	 * Load and cache the full locations.json data.
	 *
	 * @return array  Keyed by region_id.
	 */
	private function get_locations_data() {
		if ( null === $this->locations_data_cache ) {
			$file = RE_EXPORTER_ALO_TEMPLATES . 'json/locations.json';
			$raw  = ( file_exists( $file ) && is_readable( $file ) )
				? file_get_contents( $file ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				: false;
			$decoded                   = $raw ? json_decode( $raw, true ) : null;
			$this->locations_data_cache = is_array( $decoded ) ? $decoded : array();
		}
		return $this->locations_data_cache;
	}

	/**
	 * Whether a field key is a location field with ALO-standard values.
	 *
	 * @param string $field_key
	 * @return bool
	 */
	public function is_location_field( $field_key ) {
		return in_array( $field_key, array( 'region_name', 'location_name', 'section_name' ), true );
	}

	/**
	 * Return a flat list of allowed values for a location field.
	 *
	 * region_name  → all 28 region name strings
	 * location_name → all location name strings (label includes region prefix for disambiguation)
	 * section_name → all unique section name strings + 'NULL'
	 *
	 * @param string $field_key  'region_name' | 'location_name' | 'section_name'
	 * @return array[]  [ 'label' => string, 'value' => string ]
	 */
	public function get_location_values( $field_key ) {
		$data   = $this->get_locations_data();
		$result = array();

		if ( 'region_name' === $field_key ) {
			foreach ( $data as $region ) {
				$name     = (string) $region['region_name'];
				$result[] = array( 'label' => $name, 'value' => $name );
			}
			return $result;
		}

		if ( 'location_name' === $field_key ) {
			foreach ( $data as $region ) {
				$region_name = (string) $region['region_name'];
				if ( empty( $region['locations'] ) ) {
					continue;
				}
				foreach ( $region['locations'] as $loc ) {
					$loc_name = (string) $loc['location_name'];
					$result[] = array(
						'label' => $region_name . ' › ' . $loc_name,
						'value' => $loc_name,
					);
				}
			}
			return $result;
		}

		if ( 'section_name' === $field_key ) {
			foreach ( $data as $region ) {
				if ( empty( $region['locations'] ) ) {
					continue;
				}
				$region_name = (string) $region['region_name'];
				foreach ( $region['locations'] as $loc ) {
					if ( empty( $loc['sections'] ) ) {
						continue;
					}
					$loc_name = (string) $loc['location_name'];
					foreach ( $loc['sections'] as $sec_name ) {
						$sec_name = (string) $sec_name;
						$result[] = array(
							'label' => $region_name . ' › ' . $loc_name . ' › ' . $sec_name,
							'value' => $sec_name,
						);
					}
				}
			}
			$result[] = array(
				'label' => 'NULL — ' . __( 'Other (no section)', 're-exporter' ),
				'value' => 'NULL',
			);
			return $result;
		}

		return $result;
	}

	/**
	 * Return location_name options grouped by region for use in <optgroup> selects.
	 *
	 * @return array[]  [ [ 'group' => region_name, 'options' => [ ['label', 'value'], … ] ], … ]
	 */
	public function get_location_name_optgroups() {
		$data   = $this->get_locations_data();
		$groups = array();

		foreach ( $data as $region ) {
			$region_name = (string) $region['region_name'];
			if ( empty( $region['locations'] ) ) {
				continue;
			}
			$opts = array();
			foreach ( $region['locations'] as $loc ) {
				$loc_name = (string) $loc['location_name'];
				$opts[]   = array( 'label' => $loc_name, 'value' => $loc_name );
			}
			if ( ! empty( $opts ) ) {
				$groups[] = array( 'group' => $region_name, 'options' => $opts );
			}
		}

		return $groups;
	}

	/**
	 * Return all unique field keys that appear in any subcategory's field_map.
	 *
	 * These fields have subcategory-specific JSON allowed-value files and are
	 * configured per-post in the metabox rather than globally in C1.
	 *
	 * @return string[]
	 */
	public function get_all_field_map_keys() {
		$keys = array();

		foreach ( $this->get_all_subcategories() as $sub ) {
			if ( empty( $sub['template']['field_map'] ) || ! is_array( $sub['template']['field_map'] ) ) {
				continue;
			}
			foreach ( array_keys( $sub['template']['field_map'] ) as $k ) {
				if ( ! in_array( $k, $keys, true ) ) {
					$keys[] = $k;
				}
			}
		}

		return $keys;
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Check whether an array is numerically indexed (i.e., a JSON array, not an object).
	 *
	 * @param array $arr
	 * @return bool
	 */
	private function is_indexed_array( array $arr ) {
		if ( empty( $arr ) ) {
			return true;
		}
		return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
	}
}
