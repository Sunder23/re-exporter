<?php
/**
 * Resolves a source field key to its value for a given post.
 *
 * Handles virtual keys (__post_title__ etc.), ACF fields (with type-aware
 * processing), and raw post meta. Used by both exporter classes.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Field_Resolver
 */
class Field_Resolver {

	/**
	 * Resolve a field value from a post.
	 *
	 * @param \WP_Post $post
	 * @param string   $source_key  Virtual key (e.g. __post_title__) or meta key.
	 * @param string   $mode        'text' returns scalars; 'raw' allows arrays (for images).
	 * @return mixed
	 */
	public function resolve( \WP_Post $post, $source_key, $mode = 'text' ) {
		// ---- Taxonomy fields  (e.g. "taxonomy:property_category") ----------
		if ( 0 === strpos( $source_key, 'taxonomy:' ) ) {
			$taxonomy = substr( $source_key, 9 );
			$terms    = get_the_terms( $post->ID, $taxonomy );

			if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
				return ( 'text' === $mode ) ? '' : array();
			}

			$slugs = wp_list_pluck( $terms, 'slug' );

			return ( 'text' === $mode ) ? implode( ', ', $slugs ) : $slugs;
		}

		// ---- Virtual fields ------------------------------------------------
		switch ( $source_key ) {
			case '__post_id__':
				return $post->ID;

			case '__post_title__':
				return $post->post_title;

			case '__post_content__':
				return wp_strip_all_tags( $post->post_content );

			case '__post_url__':
				return get_permalink( $post->ID );

			case '__featured_image__':
				return $this->get_featured_image( $post->ID, $mode );

			case '__re_gallery__':
				return $this->get_re_gallery( $post->ID, $mode );
		}

		// ---- ACF field -----------------------------------------------------
		if ( function_exists( 'get_field' ) ) {
			// get_field() returns null when the key doesn't exist as an ACF field.
			$acf_value = get_field( $source_key, $post->ID );

			if ( null !== $acf_value && false !== $acf_value ) {
				return $this->process_acf_value( $source_key, $post->ID, $acf_value, $mode );
			}
		}

		// ---- Raw post meta -------------------------------------------------
		$value = get_post_meta( $post->ID, $source_key, true );

		return ( 'text' === $mode ) ? (string) $value : $value;
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Get the featured image URL.
	 * In 'raw' mode wraps the URL in an array (for JSON images arrays).
	 *
	 * @param int    $post_id
	 * @param string $mode
	 * @return string|string[]
	 */
	private function get_featured_image( $post_id, $mode ) {
		$url = get_the_post_thumbnail_url( $post_id, 'full' );
		$url = $url ? $url : '';

		if ( 'raw' === $mode ) {
			return $url ? array( $url ) : array();
		}

		return $url;
	}

	/**
	 * Get image URLs from the RE Exporter Gallery field (_re_gallery_images).
	 *
	 * In 'raw' mode returns an array of URLs.
	 * In 'text' mode returns pipe-separated URLs (for CSV).
	 *
	 * @param int    $post_id
	 * @param string $mode
	 * @return string|string[]
	 */
	private function get_re_gallery( $post_id, $mode ) {
		$ids = get_post_meta( $post_id, Gallery_Field::META_KEY, true );

		if ( ! is_array( $ids ) ) {
			return ( 'raw' === $mode ) ? array() : '';
		}

		$urls = array();
		foreach ( $ids as $id ) {
			$url = wp_get_attachment_url( (int) $id );
			if ( $url ) {
				$urls[] = $url;
			}
		}

		if ( 'raw' === $mode ) {
			return $urls;
		}

		return implode( '|', $urls );
	}

	/**
	 * Process an ACF value according to the field's declared type.
	 *
	 * @param string $field_key
	 * @param int    $post_id
	 * @param mixed  $value
	 * @param string $mode
	 * @return mixed
	 */
	private function process_acf_value( $field_key, $post_id, $value, $mode ) {
		if ( ! function_exists( 'get_field_object' ) ) {
			return ( 'text' === $mode ) ? (string) $value : $value;
		}

		$field = get_field_object( $field_key, $post_id );

		if ( ! $field || empty( $field['type'] ) ) {
			return ( 'text' === $mode ) ? (string) $value : $value;
		}

		switch ( $field['type'] ) {

			case 'image':
				$url = is_array( $value )
					? ( isset( $value['url'] ) ? $value['url'] : '' )
					: (string) wp_get_attachment_url( (int) $value );

				if ( 'raw' === $mode ) {
					return $url ? array( $url ) : array();
				}
				return $url;

			case 'file':
				$url = is_array( $value )
					? ( isset( $value['url'] ) ? $value['url'] : '' )
					: (string) wp_get_attachment_url( (int) $value );
				return $url;

			case 'gallery':
				$urls = array();
				if ( is_array( $value ) ) {
					foreach ( $value as $img ) {
						if ( is_array( $img ) ) {
							$urls[] = isset( $img['url'] ) ? $img['url'] : '';
						} else {
							$urls[] = (string) wp_get_attachment_url( (int) $img );
						}
					}
				}
				$urls = array_filter( $urls );

				return ( 'text' === $mode )
					? implode( '|', $urls )
					: array_values( $urls );

			case 'select':
			case 'radio':
				// ACF returns the label when return_format = 'label' (default).
				if ( is_array( $value ) ) {
					return implode( ', ', $value );
				}
				return (string) $value;

			case 'checkbox':
				if ( is_array( $value ) ) {
					return ( 'text' === $mode )
						? implode( ', ', $value )
						: $value;
				}
				return ( 'text' === $mode ) ? (string) $value : $value;

			case 'relationship':
			case 'post_object':
				$titles = array();
				if ( is_array( $value ) ) {
					foreach ( $value as $related ) {
						$rp = is_object( $related ) ? $related : get_post( $related );
						if ( $rp instanceof \WP_Post ) {
							$titles[] = $rp->post_title;
						}
					}
				} elseif ( $value instanceof \WP_Post ) {
					$titles[] = $value->post_title;
				}

				return ( 'text' === $mode )
					? implode( ', ', $titles )
					: $titles;

			default:
				return ( 'text' === $mode ) ? (string) $value : $value;
		}
	}
}
