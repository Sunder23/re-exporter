<?php
/**
 * Geo database helper for Realistimo XML export.
 *
 * Parses the bundled CSV once (static cache) and provides fast prefix search
 * over location labels plus direct lookup by internal_id.
 *
 * CSV columns (first row is header):
 *   id, internal_id, state_name, city_name, village_name, neighborhood_name, location_type
 *
 * location_type values: state | city | village | neighborhood
 * NULL values appear as the literal string "NULL".
 *
 * Label format:
 *   state:        state_name                          e.g. "Варна"
 *   city:         state_name → city_name              e.g. "Варна → Варна"
 *   village:      state_name → village_name           e.g. "Варна → Камчия"
 *   neighborhood: state_name → city_name → nbhd_name e.g. "Варна → Варна → Ален мак"
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Realistimo_Geo
 */
class Realistimo_Geo {

	/**
	 * Parsed rows cache.
	 * Each entry: [ 'internal_id' => string, 'label' => string ]
	 *
	 * @var array[]|null
	 */
	private static $rows = null;

	/**
	 * Absolute path to the CSV file.
	 *
	 * @return string
	 */
	private static function csv_path() {
		return RE_EXPORTER_REALISTIMO_TEMPLATES . 'realistimo_geo_db.csv';
	}

	/**
	 * Parse the CSV and populate the static cache (runs at most once per request).
	 *
	 * @return void
	 */
	private static function load() {
		if ( null !== self::$rows ) {
			return;
		}

		self::$rows = array();

		$path = self::csv_path();
		if ( ! file_exists( $path ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$fh = fopen( $path, 'r' );
		if ( ! $fh ) {
			return;
		}

		// Skip header row.
		fgetcsv( $fh );

		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			if ( count( $row ) < 7 ) {
				continue;
			}

			list( , $internal_id, $state_name, $city_name, $village_name, $neighborhood_name, $location_type ) = $row;

			// Normalise NULL strings.
			$state_name        = ( 'NULL' === $state_name )        ? '' : trim( $state_name );
			$city_name         = ( 'NULL' === $city_name )         ? '' : trim( $city_name );
			$village_name      = ( 'NULL' === $village_name )      ? '' : trim( $village_name );
			$neighborhood_name = ( 'NULL' === $neighborhood_name ) ? '' : trim( $neighborhood_name );
			$location_type     = trim( $location_type );
			$internal_id       = trim( $internal_id );

			// Build human-readable label according to location_type.
			switch ( $location_type ) {
				case 'state':
					$label = $state_name;
					break;

				case 'city':
					$label = $state_name . ' → ' . $city_name;
					break;

				case 'village':
					$label = $state_name . ' → ' . $village_name;
					break;

				case 'neighborhood':
					$label = $state_name . ' → ' . $city_name . ' → ' . $neighborhood_name;
					break;

				default:
					$label = $state_name;
					break;
			}

			if ( '' === $internal_id || '' === $label ) {
				continue;
			}

			self::$rows[] = array(
				'internal_id' => $internal_id,
				'label'       => $label,
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		fclose( $fh );
	}

	/**
	 * Search locations by query string (case-insensitive prefix / substring match).
	 *
	 * @param string $q      Search term.
	 * @param int    $limit  Maximum number of results to return.
	 * @return array[]  Each entry: [ 'label' => string, 'value' => string ]
	 */
	public static function search( $q, $limit = 15 ) {
		self::load();

		$q      = trim( $q );
		$result = array();

		foreach ( self::$rows as $row ) {
			if ( '' === $q || false !== mb_stripos( $row['label'], $q ) ) {
				$result[] = array(
					'label' => $row['label'],
					'value' => $row['internal_id'],
				);
				if ( count( $result ) >= $limit ) {
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Retrieve the display label for a given internal_id.
	 *
	 * @param string $internal_id
	 * @return string  Human-readable label, or the raw internal_id if not found.
	 */
	public static function get_label( $internal_id ) {
		self::load();

		$internal_id = (string) $internal_id;
		foreach ( self::$rows as $row ) {
			if ( $row['internal_id'] === $internal_id ) {
				return $row['label'];
			}
		}

		return $internal_id;
	}
}
