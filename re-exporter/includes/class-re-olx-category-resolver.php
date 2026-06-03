<?php
/**
 * Resolves OLX category and required-field state outside the wizard controller.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OLX_Category_Resolver
 */
class OLX_Category_Resolver {

	/** @var Settings */
	private $settings;

	/** @var OLX_Template */
	private $template;

	/**
	 * @param Settings     $settings
	 * @param OLX_Template $template
	 */
	public function __construct( Settings $settings, OLX_Template $template ) {
		$this->settings = $settings;
		$this->template = $template;
	}

	/**
	 * @param \WP_Post $post
	 * @return string
	 */
	public function get_display_category( \WP_Post $post ) {
		$subcat_id = $this->resolve_subcategory_id( $post );
		if ( ! $subcat_id ) {
			return '';
		}

		$subcat = $this->template->get_subcategory_by_id( $subcat_id );

		return $subcat ? $subcat['name'] : $subcat_id;
	}

	/**
	 * @param \WP_Post $post
	 * @return string
	 */
	public function resolve_subcategory_id( \WP_Post $post ) {
		$category_tax = $this->settings->get_olx_category_tax();
		$category_map = $this->settings->get_olx_category_map();
		$rent_map     = $this->settings->get_olx_category_rent_map();

		if ( $category_tax ) {
			$terms = get_the_terms( $post->ID, $category_tax );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$sale_id = ! empty( $category_map[ $term->slug ] ) ? $category_map[ $term->slug ] : '';
					$rent_id = ! empty( $rent_map[ $term->slug ] ) ? $rent_map[ $term->slug ] : '';
					if ( $sale_id || $rent_id ) {
						return $this->apply_deal_type( $sale_id, $rent_id, $post );
					}
				}
			}

			return '';
		}

		$meta_id = get_post_meta( $post->ID, '_re_exporter_olx_category', true );

		return $meta_id ? (string) $meta_id : '';
	}

	/**
	 * @param string $subcat_id
	 * @return string[]
	 */
	public function get_missing_required_fields( $subcat_id ) {
		$field_map = $this->settings->get_olx_field_map();
		$req_map   = $this->settings->get_olx_required_map();
		$required  = $this->template->get_required_fields( $subcat_id );
		$missing   = array();

		foreach ( $required as $field_key ) {
			$global_mapped = ! empty( $field_map[ $field_key ] ) && '__skip__' !== $field_map[ $field_key ];
			$cat_mapped    = ! empty( $req_map[ $subcat_id ][ $field_key ] ) && '__skip__' !== $req_map[ $subcat_id ][ $field_key ];

			if ( ! $global_mapped && ! $cat_mapped ) {
				$missing[] = $field_key;
			}
		}

		return $missing;
	}

	/**
	 * @param string   $sale_id
	 * @param string   $rent_id
	 * @param \WP_Post $post
	 * @return string
	 */
	private function apply_deal_type( $sale_id, $rent_id, \WP_Post $post ) {
		if ( ! $rent_id ) {
			return $sale_id;
		}
		if ( ! $sale_id ) {
			return $rent_id;
		}

		$deal_field = $this->settings->get_olx_deal_type_field();

		if ( '__always_rent__' === $deal_field ) {
			return $rent_id;
		}
		if ( '__always_sales__' === $deal_field || ! $deal_field ) {
			return $sale_id;
		}

		$wp_val    = get_post_meta( $post->ID, $deal_field, true );
		$deal_map  = $this->settings->get_olx_deal_type_map();
		$direction = isset( $deal_map[ $wp_val ] ) ? $deal_map[ $wp_val ] : 'sales';

		return 'rent' === $direction ? $rent_id : $sale_id;
	}
}
