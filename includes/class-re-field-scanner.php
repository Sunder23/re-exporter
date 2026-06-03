<?php
/**
 * Discovers post types, posts, field definitions, taxonomy terms, and
 * distinct meta values for the Settings and Export pages.
 *
 * Taxonomy fields are surfaced as source keys with the prefix "taxonomy:"
 * (e.g. "taxonomy:property_category") so callers can distinguish them from
 * plain meta / ACF fields.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Field_Scanner
 */
class Field_Scanner {

	/**
	 * Virtual source keys always available regardless of CPT.
	 *
	 * @var array<string,array>
	 */
	private static $virtual_fields = array(
		'__post_id__'       => array( 'label' => 'Post ID',                     'type' => 'virtual' ),
		'__post_title__'    => array( 'label' => 'Post Title',                  'type' => 'virtual' ),
		'__post_content__'  => array( 'label' => 'Post Content (stripped)',      'type' => 'virtual' ),
		'__post_url__'      => array( 'label' => 'Post URL',                    'type' => 'virtual' ),
		'__featured_image__'=> array( 'label' => 'Featured Image URL',          'type' => 'virtual' ),
		'__re_gallery__'    => array( 'label' => 'RE Gallery (re-exporter)',     'type' => 'gallery' ),
	);

	// =========================================================================
	// Post Types
	// =========================================================================

	/**
	 * Return all public non-builtin CPTs with their published post counts.
	 *
	 * @return array[]  Each entry: [ 'slug', 'label', 'count' ]
	 */
	public function get_post_types() {
		$types  = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
		$result = array();

		foreach ( $types as $type ) {
			$counts   = wp_count_posts( $type->name );
			$result[] = array(
				'slug'  => $type->name,
				'label' => $type->label,
				'count' => (int) ( isset( $counts->publish ) ? $counts->publish : 0 ),
			);
		}

		return $result;
	}

	// =========================================================================
	// Posts
	// =========================================================================

	/**
	 * Return all published posts of a CPT, newest first.
	 *
	 * @param string $post_type
	 * @return \WP_Post[]
	 */
	public function get_posts_for_type( $post_type ) {
		return get_posts( array(
			'post_type'      => sanitize_key( $post_type ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}

	/**
	 * Return a small sample of published post IDs for field discovery.
	 *
	 * @param string $post_type
	 * @param int    $limit
	 * @return int[]
	 */
	public function get_sample_post_ids( $post_type, $limit = 5 ) {
		$posts = get_posts( array(
			'post_type'      => sanitize_key( $post_type ),
			'post_status'    => 'publish',
			'posts_per_page' => absint( $limit ),
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		return array_map( 'intval', $posts );
	}

	// =========================================================================
	// Field Definitions
	// =========================================================================

	/**
	 * Discover all available source field definitions for a CPT.
	 *
	 * Sources (in priority order):
	 *   1. Virtual fields (always included)
	 *   2. ACF field objects (via get_field_objects(), provides type metadata)
	 *   3. Taxonomy fields   (prefixed with "taxonomy:")
	 *   4. Raw post meta     (exclude _-prefixed private/ACF reference keys)
	 *
	 * Returns: field_key => [ 'label', 'type', ...extra ]
	 *
	 * @param string $post_type
	 * @param int[]  $post_ids   IDs to sample; up to 5 are used for discovery.
	 *                           Pass an empty array to auto-sample.
	 * @return array<string,array>
	 */
	public function get_field_definitions( $post_type, array $post_ids = array() ) {
		$fields = self::$virtual_fields;

		if ( empty( $post_ids ) ) {
			$post_ids = $this->get_sample_post_ids( $post_type, 5 );
		}

		$sample = array_slice( $post_ids, 0, 5 );

		// ── 1. ACF fields ─────────────────────────────────────────────────────
		if ( function_exists( 'get_field_objects' ) && ! empty( $sample ) ) {
			foreach ( $sample as $post_id ) {
				$acf_fields = get_field_objects( $post_id );

				if ( ! is_array( $acf_fields ) ) {
					continue;
				}

				foreach ( $acf_fields as $key => $acf ) {
					if ( isset( $fields[ $key ] ) ) {
						continue;
					}
					$fields[ $key ] = array(
						'label'   => isset( $acf['label'] ) ? $acf['label'] : $key,
						'type'    => isset( $acf['type'] )  ? $acf['type']  : 'text',
						'choices' => isset( $acf['choices'] ) && is_array( $acf['choices'] )
							? $acf['choices']
							: array(),
					);
				}
			}
		}

		// ── 2. Taxonomy fields ────────────────────────────────────────────────
		foreach ( $this->get_taxonomies( $post_type ) as $tax ) {
			$key            = 'taxonomy:' . $tax->name;
			$fields[ $key ] = array(
				'label'    => $tax->label,
				'type'     => 'taxonomy',
				'taxonomy' => $tax->name,
			);
		}

		// ── 3. Raw post meta ──────────────────────────────────────────────────
		if ( ! empty( $sample ) ) {
			global $wpdb;

			$placeholders = implode( ', ', array_fill( 0, count( $sample ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$meta_keys = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT meta_key
					 FROM {$wpdb->postmeta}
					 WHERE post_id IN ({$placeholders})
					   AND meta_key NOT LIKE '\_%%'
					 ORDER BY meta_key",
					$sample
				)
			);
			// phpcs:enable

			foreach ( $meta_keys as $key ) {
				if ( isset( $fields[ $key ] ) ) {
					continue; // ACF already captured with richer type info.
				}
				$fields[ $key ] = array(
					'label'   => $key,
					'type'    => 'text',
					'choices' => array(),
				);
			}
		}

		return $fields;
	}

	// =========================================================================
	// Taxonomies
	// =========================================================================

	/**
	 * Return taxonomy objects registered for a CPT.
	 *
	 * @param string $post_type
	 * @return \WP_Taxonomy[]  Keyed by taxonomy slug.
	 */
	public function get_taxonomies( $post_type ) {
		$taxonomies = get_object_taxonomies( sanitize_key( $post_type ), 'objects' );

		// Exclude built-in non-relevant taxonomies (nav menus, link categories, etc.).
		$exclude = array( 'nav_menu', 'link_category', 'post_format' );

		return array_filter( $taxonomies, function ( $tax ) use ( $exclude ) {
			return ! in_array( $tax->name, $exclude, true );
		} );
	}

	/**
	 * Return all terms for a taxonomy.
	 *
	 * @param string $taxonomy_slug
	 * @return array[]  Each: [ 'term_id', 'name', 'slug' ]
	 */
	public function get_taxonomy_terms( $taxonomy_slug ) {
		$terms = get_terms( array(
			'taxonomy'   => sanitize_key( $taxonomy_slug ),
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$result = array();
		foreach ( $terms as $term ) {
			$result[] = array(
				'term_id' => $term->term_id,
				'name'    => $term->name,
				'slug'    => $term->slug,
			);
		}

		return $result;
	}

	// =========================================================================
	// Meta Value Sampling
	// =========================================================================

	/**
	 * Return distinct non-empty values stored for a meta key within a CPT.
	 *
	 * Used to populate the left-column (WP values) of the Value Override table.
	 *
	 * @param string $meta_key
	 * @param string $post_type
	 * @param int    $limit      Maximum distinct values to return.
	 * @return string[]
	 */
	public function get_distinct_meta_values( $meta_key, $post_type, $limit = 200 ) {
		global $wpdb;

		$limit = absint( $limit );
		if ( 0 === $limit ) {
			$limit = 200;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s
				   AND p.post_type = %s
				   AND p.post_status = 'publish'
				   AND pm.meta_value != ''
				 ORDER BY pm.meta_value
				 LIMIT %d",
				$meta_key,
				$post_type,
				$limit
			)
		);
		// phpcs:enable

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Return possible WP source values for a given source field definition.
	 *
	 * Handles ACF select/radio (from choices), taxonomy (from terms),
	 * and plain meta (from DB sampling).
	 *
	 * @param string $source_key        Field key as returned by get_field_definitions().
	 * @param array  $field_definition  The field's definition array.
	 * @param string $post_type         CPT slug — needed for meta DB queries.
	 * @return array[]  Each: [ 'label', 'value' ]
	 */
	public function get_source_values( $source_key, array $field_definition, $post_type ) {
		$type = isset( $field_definition['type'] ) ? $field_definition['type'] : 'text';

		// Virtual fields have no enumerable values.
		if ( 'virtual' === $type ) {
			return array();
		}

		// Taxonomy: return all terms (slug as value, name as label).
		if ( 'taxonomy' === $type ) {
			$tax_name = isset( $field_definition['taxonomy'] ) ? $field_definition['taxonomy'] : '';
			if ( ! $tax_name ) {
				return array();
			}
			$result = array();
			foreach ( $this->get_taxonomy_terms( $tax_name ) as $term ) {
				$result[] = array( 'label' => $term['name'], 'value' => $term['slug'] );
			}
			return $result;
		}

		// ACF select / radio — choices are known at field-definition time.
		if ( in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
			$choices = isset( $field_definition['choices'] ) ? $field_definition['choices'] : array();
			if ( ! empty( $choices ) ) {
				$result = array();
				foreach ( $choices as $val => $label ) {
					$result[] = array( 'label' => $label, 'value' => (string) $val );
				}
				return $result;
			}
			// Fall through to DB sampling if choices array is empty.
		}

		// Plain meta / ACF text/number/etc.: sample distinct values from DB.
		$raw = $this->get_distinct_meta_values( $source_key, $post_type );

		$result = array();
		foreach ( $raw as $val ) {
			$result[] = array( 'label' => $val, 'value' => $val );
		}

		return $result;
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Return the virtual field definitions for use in dropdowns.
	 *
	 * @return array<string,array>
	 */
	public static function get_virtual_fields() {
		return self::$virtual_fields;
	}
}
