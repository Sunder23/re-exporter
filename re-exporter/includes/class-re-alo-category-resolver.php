<?php
/**
 * Resolves ALO category and required-field state outside the wizard controller.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ALO_Category_Resolver
 */
class ALO_Category_Resolver {

	/** @var Settings */
	private $settings;

	/** @var ALO_Template */
	private $template;

	/**
	 * @param Settings     $settings
	 * @param ALO_Template $template
	 */
	public function __construct( Settings $settings, ALO_Template $template ) {
		$this->settings = $settings;
		$this->template = $template;
	}

	/**
	 * @param \WP_Post $post
	 * @return string
	 */
	public function resolve_subcategory_id( \WP_Post $post ) {
		$category_tax = $this->settings->get_alo_category_tax();
		$category_map = $this->settings->get_alo_category_map();
		$subcat_id    = '';

		if ( $category_tax ) {
			$terms = get_the_terms( $post->ID, $category_tax );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( ! empty( $category_map[ $term->slug ] ) ) {
						$subcat_id = (string) $category_map[ $term->slug ];
						break;
					}
				}
			}
		} else {
			$subcat_id = (string) get_post_meta( $post->ID, '_re_exporter_alo_category', true );
		}

		if ( ! $subcat_id ) {
			return '';
		}

		return $this->apply_deal_type( $subcat_id, $post, $category_tax, $category_map );
	}

	/**
	 * @param string $subcat_id
	 * @return string[]
	 */
	public function get_missing_required_fields( $subcat_id ) {
		$field_map = $this->settings->get_alo_field_map();
		$req_map   = $this->settings->get_alo_required_map();
		$required  = $this->template->get_required_fields( $subcat_id );
		$missing   = array();

		foreach ( $required as $field_key ) {
			if ( in_array( $field_key, array( 'out_id', 'subcat_id' ), true ) ) {
				continue;
			}

			$global_mapped = ! empty( $field_map[ $field_key ] ) && '__skip__' !== $field_map[ $field_key ];
			$cat_mapped    = ! empty( $req_map[ $subcat_id ][ $field_key ] ) && '__skip__' !== $req_map[ $subcat_id ][ $field_key ];

			if ( ! $global_mapped && ! $cat_mapped ) {
				$missing[] = $field_key;
			}
		}

		return $missing;
	}

	/**
	 * @param string               $subcat_id
	 * @param \WP_Post             $post
	 * @param string               $category_tax
	 * @param array<string,string> $category_map
	 * @return string
	 */
	private function apply_deal_type( $subcat_id, \WP_Post $post, $category_tax, array $category_map ) {
		$deal_field = $this->settings->get_alo_deal_type_field();
		if ( ! $deal_field ) {
			return $subcat_id;
		}

		$rent_map  = $this->settings->get_alo_category_rent_map();
		$term_slug = '';

		if ( $category_tax ) {
			$terms = get_the_terms( $post->ID, $category_tax );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( isset( $category_map[ $term->slug ] ) && (string) $category_map[ $term->slug ] === $subcat_id ) {
						$term_slug = $term->slug;
						break;
					}
				}
			}
		}

		if ( ! $term_slug || empty( $rent_map[ $term_slug ] ) ) {
			return $subcat_id;
		}

		$rent_subcat_id = (string) $rent_map[ $term_slug ];
		$direction      = 'sales';

		if ( '__always_rent__' === $deal_field ) {
			$direction = 'rent';
		} elseif ( '__always_sales__' !== $deal_field ) {
			$wp_val    = get_post_meta( $post->ID, $deal_field, true );
			$deal_map  = $this->settings->get_alo_deal_type_map();
			$direction = isset( $deal_map[ $wp_val ] ) ? $deal_map[ $wp_val ] : 'sales';
		}

		return 'rent' === $direction ? $rent_subcat_id : $subcat_id;
	}
}
