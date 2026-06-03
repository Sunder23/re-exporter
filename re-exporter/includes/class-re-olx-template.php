<?php
/**
 * Reads and parses the client-provided OLX template files.
 *
 * Responsibilities:
 *  - Load $categories from templates/olx/olx_categorys.php (read-only)
 *  - Parse semicolon-delimited CSV headers from template files
 *  - Read JSON allowed-value lists from templates/olx/json/
 *
 * ⚠️  NEVER modifies any file inside templates/olx/.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OLX_Template
 */
class OLX_Template {

	/**
	 * Columns whose JSON file name differs from the CSV column name.
	 *
	 * key   = CSV column name
	 * value = JSON filename (without .json)
	 *
	 * @var array<string,string>
	 */
	private static $column_json_map = array(
		'location_city' => 'citys',
	);

	/**
	 * Internal cache for the parsed $categories array.
	 *
	 * @var array|null
	 */
	private $categories_cache = null;

	/**
	 * Internal cache for CSV headers, keyed by template path.
	 *
	 * @var array<string,string[]>
	 */
	private $header_cache = array();

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
	 * Return the full $categories array from olx_categorys.php.
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

		$file = RE_EXPORTER_OLX_TEMPLATES . 'olx_categorys.php';

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
	 * Find and return a subcategory definition by its ID.
	 *
	 * @param string $id  Subcategory slug, e.g. 'apartments_sale'.
	 * @return array|null  Full subcategory array or null if not found.
	 */
	public function get_subcategory_by_id( $id ) {
		foreach ( $this->get_all_subcategories() as $sub ) {
			if ( isset( $sub['id'] ) && $sub['id'] === $id ) {
				return $sub;
			}
		}
		return null;
	}

	/**
	 * Return the display name of a top-level category group by its ID.
	 *
	 * @param string $group_id  e.g. 'sales', 'rent'.
	 * @return string  Group name, or empty string if not found.
	 */
	public function get_group_name( $group_id ) {
		foreach ( $this->get_categories() as $group ) {
			if ( isset( $group['id'] ) && $group['id'] === $group_id ) {
				return (string) $group['name'];
			}
		}
		return '';
	}

	/**
	 * Return deduplicated base subcategories (IDs without _sale/_rent suffix)
	 * for use in the B3 "Auto-route via B5" optgroup.
	 *
	 * Each entry: [ 'id' => 'apartments', 'name' => '...' ]
	 * Entries where both _sale and _rent variants exist appear once.
	 * IDs that have no suffix (e.g. new_construction) are excluded — they
	 * are already fully resolved and belong in the regular groups.
	 *
	 * @return array[]
	 */
	public function get_base_subcategories() {
		$seen  = array();
		$items = array();

		foreach ( $this->get_all_subcategories() as $sub ) {
			$id = $sub['id'];

			if ( substr( $id, -5 ) === '_sale' ) {
				$base_id = substr( $id, 0, -5 );
			} elseif ( substr( $id, -5 ) === '_rent' ) {
				$base_id = substr( $id, 0, -5 );
			} else {
				continue; // No suffix — skip (e.g. new_construction).
			}

			if ( isset( $seen[ $base_id ] ) ) {
				continue;
			}
			$seen[ $base_id ] = true;
			$items[]          = array(
				'id'   => $base_id,
				'name' => $sub['name'],
			);
		}

		return $items;
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
	// CSV Headers
	// =========================================================================

	/**
	 * Read the semicolon-delimited column headers from a CSV template file.
	 *
	 * @param string $template_path  Path relative to RE_EXPORTER_DIR,
	 *                               e.g. 'templates/olx/sales/apartments_sale.csv'
	 * @return string[]  Ordered array of column names (empty on failure).
	 */
	public function get_csv_headers( $template_path ) {
		if ( isset( $this->header_cache[ $template_path ] ) ) {
			return $this->header_cache[ $template_path ];
		}

		$filepath = RE_EXPORTER_DIR . $template_path;

		if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) ) {
			$this->header_cache[ $template_path ] = array();
			return array();
		}

		$handle = fopen( $filepath, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			$this->header_cache[ $template_path ] = array();
			return array();
		}

		$line = fgets( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( false === $line || '' === trim( $line ) ) {
			$this->header_cache[ $template_path ] = array();
			return array();
		}

		// Strip UTF-8 BOM and whitespace, then split on semicolons.
		$line    = str_replace( "\xEF\xBB\xBF", '', trim( $line ) );
		$headers = array_map( 'trim', explode( ';', $line ) );
		$headers = array_values( array_filter( $headers ) );

		$this->header_cache[ $template_path ] = $headers;
		return $headers;
	}

	/**
	 * Return the union of all column names across every CSV template.
	 *
	 * Common columns (id, title, description, …) appear first because they
	 * occur in the first template processed. Order is stable.
	 *
	 * @return string[]
	 */
	public function get_all_csv_columns() {
		$columns = array();

		foreach ( $this->get_all_subcategories() as $sub ) {
			if ( empty( $sub['template']['path'] ) ) {
				continue;
			}
			foreach ( $this->get_csv_headers( $sub['template']['path'] ) as $col ) {
				if ( '' !== $col && ! in_array( $col, $columns, true ) ) {
					$columns[] = $col;
				}
			}
		}

		return $columns;
	}

	/**
	 * Return the CSV template path (relative to plugin root) for a subcategory.
	 *
	 * @param string $subcategory_id
	 * @return string|null  Relative path string, or null if not found.
	 */
	public function get_csv_path( $subcategory_id ) {
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
		// Fallback: base IDs (no suffix) → try the _sale variant for display purposes.
		if ( ! $sub ) {
			$sub = $this->get_subcategory_by_id( $subcategory_id . '_sale' );
		}
		if ( $sub && ! empty( $sub['template']['required_fields'] ) ) {
			return (array) $sub['template']['required_fields'];
		}
		return array();
	}

	/**
	 * Return the field_map (JSON key overrides) for a subcategory.
	 *
	 * e.g. for houses_sale: [ 'floors' => 'house_floors' ]
	 *
	 * @param string $subcategory_id
	 * @return array<string,string>  Empty array if no overrides defined.
	 */
	public function get_field_map_overrides( $subcategory_id ) {
		$sub = $this->get_subcategory_by_id( $subcategory_id );
		// Fallback: base IDs (no suffix) → try the _sale variant.
		if ( ! $sub ) {
			$sub = $this->get_subcategory_by_id( $subcategory_id . '_sale' );
		}
		if ( $sub && ! empty( $sub['template']['field_map'] ) && is_array( $sub['template']['field_map'] ) ) {
			return $sub['template']['field_map'];
		}
		return array();
	}

	// =========================================================================
	// JSON Value Files
	// =========================================================================

	/**
	 * Return the JSON-file key for a given CSV column name.
	 *
	 * Handles the location_city → citys special case and any future
	 * column-to-JSON-key mismatches.
	 *
	 * @param string $column_name
	 * @return string  JSON file key (filename without .json).
	 */
	public function get_json_key_for_column( $column_name ) {
		return isset( self::$column_json_map[ $column_name ] )
			? self::$column_json_map[ $column_name ]
			: $column_name;
	}

	/**
	 * Get the effective JSON key for a field within a specific subcategory,
	 * taking field_map overrides into account.
	 *
	 * e.g. houses_sale / 'floors' → 'house_floors'
	 *
	 * @param string $subcategory_id
	 * @param string $field_key        CSV column name / required-field key.
	 * @return string  Effective JSON key to use for value lookups.
	 */
	public function get_effective_json_key( $subcategory_id, $field_key ) {
		$overrides = $this->get_field_map_overrides( $subcategory_id );
		if ( isset( $overrides[ $field_key ] ) ) {
			return $overrides[ $field_key ];
		}
		return $this->get_json_key_for_column( $field_key );
	}

	/**
	 * Check whether a JSON allowed-values file exists for a column name.
	 *
	 * Applies the column → JSON key mapping before the file check.
	 *
	 * @param string $column_name
	 * @return bool
	 */
	public function has_json_for_column( $column_name ) {
		$key = $this->get_json_key_for_column( $column_name );
		return file_exists( RE_EXPORTER_OLX_TEMPLATES . 'json/' . $key . '.json' );
	}

	/**
	 * Check whether a JSON allowed-values file exists for a raw field key.
	 *
	 * @param string $field_key  Exact JSON filename key (no .json extension).
	 * @return bool
	 */
	public function has_json_values( $field_key ) {
		return file_exists( RE_EXPORTER_OLX_TEMPLATES . 'json/' . $field_key . '.json' );
	}

	/**
	 * Return the allowed values from a JSON file.
	 *
	 * Parses the OLX nested structure:
	 *   data → myAds → categoryField → values → items[]
	 *   Each item: { label, value, sublabel, parentValue }
	 *
	 * Falls back to plain JSON arrays if the nested structure is absent.
	 *
	 * @param string $field_key  JSON filename without extension (e.g. 'heat', 'citys').
	 * @return array[]  Array of [ 'label' => string, 'value' => string ]
	 */
	public function get_json_values( $field_key ) {
		if ( isset( $this->json_cache[ $field_key ] ) ) {
			return $this->json_cache[ $field_key ];
		}

		$filepath = RE_EXPORTER_OLX_TEMPLATES . 'json/' . $field_key . '.json';

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

		// Attempt the OLX nested structure first.
		$items = isset( $data['data']['myAds']['categoryField']['values']['items'] )
			? $data['data']['myAds']['categoryField']['values']['items']
			: null;

		if ( is_array( $items ) ) {
			$result = array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$result[] = array(
					'label' => isset( $item['label'] ) ? (string) $item['label'] : '',
					'value' => isset( $item['value'] ) ? (string) $item['value'] : '',
				);
			}
			$this->json_cache[ $field_key ] = $result;
			return $result;
		}

		// Fallback: plain array of strings or objects.
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
	 * Convenience wrapper: get JSON values for a CSV column name,
	 * applying the column → JSON key mapping automatically.
	 *
	 * @param string $column_name
	 * @return array[]
	 */
	public function get_json_values_for_column( $column_name ) {
		return $this->get_json_values( $this->get_json_key_for_column( $column_name ) );
	}
}
