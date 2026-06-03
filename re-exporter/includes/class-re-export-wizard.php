<?php
/**
 * Export tab — renders the 4-step export wizard and handles all
 * AJAX actions for the Export tab.
 *
 * Steps:
 *   1. Select Records  (AJAX: re_exp_load_posts)
 *   2. Select Platform
 *   3. Review & Confirm (AJAX: re_exp_review)
 *   4. Generate & Download (AJAX: re_exp_run)
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Export_Wizard
 */
class Export_Wizard {

	/** @var Settings */
	private $settings;

	/** @var Platform_Registry */
	private $platform_registry;

	/** @var OLX_Category_Resolver */
	private $olx_category_resolver;

	/** @var Export_Run_Service */
	private $run_service;

	/** @var Export_Review_Service */
	private $review_service;

	/**
	 * @param Settings              $settings
	 * @param Platform_Registry     $platform_registry
	 * @param OLX_Category_Resolver $olx_category_resolver
	 * @param Export_Run_Service    $run_service
	 * @param Export_Review_Service $review_service
	 */
	public function __construct( Settings $settings, Platform_Registry $platform_registry, OLX_Category_Resolver $olx_category_resolver, Export_Run_Service $run_service, Export_Review_Service $review_service ) {
		$this->settings              = $settings;
		$this->platform_registry     = $platform_registry;
		$this->olx_category_resolver = $olx_category_resolver;
		$this->run_service           = $run_service;
		$this->review_service        = $review_service;

		add_action( 'wp_ajax_re_exp_load_posts', array( $this, 'ajax_load_posts' ) );
		add_action( 'wp_ajax_re_exp_review',     array( $this, 'ajax_review' ) );
		add_action( 'wp_ajax_re_exp_run',        array( $this, 'ajax_run' ) );
	}

	// =========================================================================
	// Render
	// =========================================================================

	/**
	 * Render the Export tab HTML.
	 */
	public function render() {
		$post_type = $this->settings->get_post_type();
		$cpt       = $post_type ? get_post_type_object( $post_type ) : null;
		include RE_EXPORTER_DIR . 'templates/admin/page-export.php';
	}

	/**
	 * Build the settings tab URL — mirrored from Admin_Page so that
	 * page-export.php (included with $this = Export_Wizard) can call it.
	 *
	 * @param array $extra  Extra query args.
	 * @return string
	 */
	public function settings_url( array $extra = array() ) {
		return add_query_arg(
			array_merge( array( 'page' => 're-exporter', 'tab' => 'settings' ), $extra ),
			admin_url( 'admin.php' )
		);
	}

	// =========================================================================
	// AJAX — Step 1: load posts table
	// =========================================================================

	/**
	 * Return the posts table HTML for Step 1.
	 */
	public function ajax_load_posts() {
		$this->verify_request();

		$post_type = $this->settings->get_post_type();
		if ( ! $post_type ) {
			wp_send_json_error( __( 'No post type configured. Please set one in Settings.', 're-exporter' ) );
		}

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $this->settings->get_only_published() ) {
			$args['post_status'] = 'publish';
		} else {
			$args['post_status'] = array( 'publish', 'draft', 'private' );
		}

		if ( $this->settings->get_use_per_post_flag() ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'relation' => 'OR',
					array(
						'key'     => '_re_export_enabled',
						'value'   => '1',
						'compare' => '=',
					),
					array(
						'key'     => '_re_export_enabled',
						'compare' => 'NOT EXISTS',
					),
				),
			);
		}

		$posts = get_posts( $args );

		$category_tax = $this->settings->get_olx_category_tax();
		$category_map = $this->settings->get_olx_category_map();

		ob_start();
		$this->render_posts_table( $posts, $category_tax, $category_map );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Render the posts selection table.
	 *
	 * @param \WP_Post[] $posts
	 * @param string     $category_tax  Taxonomy slug used for OLX category.
	 * @param array      $category_map  [ term_slug => subcategory_id ]
	 */
	private function render_posts_table( array $posts, $category_tax, array $category_map ) {
		if ( empty( $posts ) ) {
			echo '<p>' . esc_html__( 'No posts found matching the current filters.', 're-exporter' ) . '</p>';
			return;
		}
		?>
		<table class="re-posts-table widefat striped" id="re-posts-table">
			<thead>
				<tr>
					<th class="re-col-check">
						<input type="checkbox" id="re-check-all" checked="checked"
							title="<?php esc_attr_e( 'Toggle all', 're-exporter' ); ?>" />
					</th>
					<th><?php esc_html_e( 'Title', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'OLX Category', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Date', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Edit', 're-exporter' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $posts as $post ) :
				$olx_cat = $this->olx_category_resolver->get_display_category( $post );
				$missing = ! $olx_cat;
				?>
				<tr<?php echo $missing ? ' class="re-row-warning"' : ''; ?>>
					<td>
						<input type="checkbox" class="re-post-check" name="post_ids[]"
							value="<?php echo esc_attr( $post->ID ); ?>" checked="checked" />
					</td>
					<td>
						<?php echo esc_html( $post->post_title ? $post->post_title : __( '(no title)', 're-exporter' ) ); ?>
					</td>
					<td>
						<?php if ( $olx_cat ) : ?>
							<span class="re-cat-badge"><?php echo esc_html( $olx_cat ); ?></span>
						<?php else : ?>
							<span class="re-warn-inline">
								&#9888; <?php esc_html_e( 'No category', 're-exporter' ); ?>
							</span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( get_the_date( 'Y-m-d', $post ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"
							target="_blank" rel="noopener">
							<?php esc_html_e( 'Edit', 're-exporter' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Determine the display label of the OLX subcategory for a post.
	 *
	 * @param \WP_Post $post
	 * @param string   $category_tax
	 * @param array    $category_map
	 * @return string  Subcategory name, or '' if unresolved.
	 */
	private function resolve_display_category( \WP_Post $post, $category_tax, array $category_map ) {
		// Taxonomy-based.
		if ( $category_tax ) {
			$terms = get_the_terms( $post->ID, $category_tax );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( isset( $category_map[ $term->slug ] ) ) {
						$subcat = $this->olx_template->get_subcategory_by_id( $category_map[ $term->slug ] );
						return $subcat ? $subcat['name'] : $category_map[ $term->slug ];
					}
				}
			}
			return '';
		}

		// Per-post meta.
		$subcat_id = get_post_meta( $post->ID, '_re_exporter_olx_category', true );
		if ( $subcat_id ) {
			$subcat = $this->olx_template->get_subcategory_by_id( $subcat_id );
			return $subcat ? $subcat['name'] : $subcat_id;
		}

		return '';
	}

	// =========================================================================
	// AJAX — Step 3: Review
	// =========================================================================

	/**
	 * Return the review summary HTML for Step 2.
	 */
	public function ajax_review() {
		$this->verify_request();

		$platform = isset( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';

		if ( ! $this->platform_registry->has( $platform ) ) {
			wp_send_json_error( __( 'Invalid parameters.', 're-exporter' ) );
		}

		$post_ids = $this->get_export_post_ids();
		if ( empty( $post_ids ) ) {
			wp_send_json_error( __( 'No posts found matching the current export filters.', 're-exporter' ) );
		}

		$html = $this->review_service->render( $platform, $post_ids );

		wp_send_json_success( array( 'html' => $html, 'count' => count( $post_ids ) ) );
	}

	/**
	 * Render OLX review: group posts by subcategory, list files + counts + warnings.
	 *
	 * @param int[] $post_ids
	 */
	private function render_olx_review( array $post_ids ) {
		$category_tax = $this->settings->get_olx_category_tax();
		$category_map = $this->settings->get_olx_category_map();
		$field_map    = $this->settings->get_olx_field_map();
		$req_map      = $this->settings->get_olx_required_map();

		$groups   = array(); // [ subcategory_id => [ 'name', 'path', 'posts' => [] ] ]
		$warnings = array(); // [ post_title => [ missing_fields ] ]

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$subcat_id = $this->resolve_subcat_id( $post, $category_tax, $category_map );

			if ( ! $subcat_id ) {
				$warnings[] = array(
					'title'   => $post->post_title ?: "(#{$post_id})",
					'missing' => array( __( 'No OLX category assigned', 're-exporter' ) ),
				);
				continue;
			}

			// Check required fields.
			$missing = $this->get_missing_required_fields( $subcat_id, $field_map, $req_map );
			if ( ! empty( $missing ) ) {
				$warnings[] = array(
					'title'   => $post->post_title ?: "(#{$post_id})",
					'missing' => $missing,
				);
			}

			if ( ! isset( $groups[ $subcat_id ] ) ) {
				$subcat = $this->olx_template->get_subcategory_by_id( $subcat_id );
				$groups[ $subcat_id ] = array(
					'name'  => $subcat ? $subcat['name'] : $subcat_id,
					'path'  => $this->olx_template->get_csv_path( $subcat_id ),
					'count' => 0,
				);
			}

			$groups[ $subcat_id ]['count']++;
		}

		// Render.
		if ( ! empty( $groups ) ) :
			?>
			<table class="re-review-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Output file', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Category', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Posts', 're-exporter' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $groups as $subcat_id => $g ) : ?>
					<tr>
						<td><code><?php echo esc_html( $subcat_id . '.csv' ); ?></code></td>
						<td><?php echo esc_html( $g['name'] ); ?></td>
						<td><?php echo esc_html( $g['count'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		endif;

		if ( ! empty( $warnings ) ) {
			echo '<div class="re-review-warnings">';
			echo '<strong>&#9888; ' . esc_html__( 'Warnings', 're-exporter' ) . '</strong><ul>';
			foreach ( $warnings as $w ) {
				echo '<li><em>' . esc_html( $w['title'] ) . '</em>: '
					. esc_html( implode( ', ', $w['missing'] ) ) . '</li>';
			}
			echo '</ul></div>';
		}

		if ( empty( $groups ) && empty( $warnings ) ) {
			echo '<p>' . esc_html__( 'No exportable posts found.', 're-exporter' ) . '</p>';
		}
	}

	/**
	 * Render ALO review: posts grouped by subcategory, one JSON file per group.
	 *
	 * @param int[] $post_ids
	 */
	private function render_alo_review( array $post_ids ) {
		$category_tax = $this->settings->get_alo_category_tax();
		$category_map = $this->settings->get_alo_category_map();
		$field_map    = $this->settings->get_alo_field_map();
		$req_map      = $this->settings->get_alo_required_map();

		$groups   = array(); // [ subcat_id => [ 'name', 'count' ] ]
		$warnings = array(); // [ [ 'title', 'missing' => [] ] ]

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$subcat_id = $this->resolve_alo_subcat_id( $post, $category_tax, $category_map );

			if ( ! $subcat_id ) {
				$warnings[] = array(
					'title'   => $post->post_title ?: "(#{$post_id})",
					'missing' => array( __( 'No ALO category assigned', 're-exporter' ) ),
				);
				continue;
			}

			// Check required fields.
			$missing = $this->get_alo_missing_required_fields( $subcat_id, $field_map, $req_map );
			if ( ! empty( $missing ) ) {
				$warnings[] = array(
					'title'   => $post->post_title ?: "(#{$post_id})",
					'missing' => $missing,
				);
			}

			if ( ! isset( $groups[ $subcat_id ] ) ) {
				$subcat = $this->alo_template->get_subcategory_by_id( $subcat_id );
				$groups[ $subcat_id ] = array(
					'name'  => $subcat ? $subcat['name'] : $subcat_id,
					'count' => 0,
				);
			}

			$groups[ $subcat_id ]['count']++;
		}

		if ( ! empty( $groups ) ) :
			?>
			<table class="re-review-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Output file', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Category', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Posts', 're-exporter' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $groups as $subcat_id => $g ) : ?>
					<tr>
						<td><code><?php echo esc_html( $subcat_id . '.json' ); ?></code></td>
						<td><?php echo esc_html( $g['name'] ); ?></td>
						<td><?php echo esc_html( $g['count'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		endif;

		if ( ! empty( $warnings ) ) {
			echo '<div class="re-review-warnings">';
			echo '<strong>&#9888; ' . esc_html__( 'Warnings', 're-exporter' ) . '</strong><ul>';
			foreach ( $warnings as $w ) {
				echo '<li><em>' . esc_html( $w['title'] ) . '</em>: '
					. esc_html( implode( ', ', $w['missing'] ) ) . '</li>';
			}
			echo '</ul></div>';
		}

		if ( empty( $groups ) && empty( $warnings ) ) {
			echo '<p>' . esc_html__( 'No exportable posts found.', 're-exporter' ) . '</p>';
		}
	}

	/**
	 * Render imoti.net review: single feed.xml, list posts count + agency info.
	 *
	 * @param int[] $post_ids
	 */
	private function render_imoti_review( array $post_ids ) {
		$agency_id    = $this->settings->get_imoti_agency_id();
		$agency_title = $this->settings->get_imoti_agency_title();
		$field_map    = $this->settings->get_imoti_field_map();

		// Warn if critical fields are not mapped.
		$recommended = array( 'OfferType', 'EstateType', 'Price', 'Description', 'Images' );
		$missing      = array();
		foreach ( $recommended as $field ) {
			if ( empty( $field_map[ $field ] ) || '__skip__' === $field_map[ $field ] ) {
				$missing[] = $field;
			}
		}
		?>
		<table class="re-review-table widefat">
			<thead><tr>
				<th><?php esc_html_e( 'Output file', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'AgencyID', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'Posts', 're-exporter' ); ?></th>
			</tr></thead>
			<tbody>
				<tr>
					<td><code>feed.xml</code></td>
					<td><?php echo esc_html( $agency_id ?: '—' ); ?></td>
					<td><?php echo esc_html( count( $post_ids ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php if ( $agency_title ) : ?>
			<p style="margin-top:6px;color:#646970;">
				<?php esc_html_e( 'Agency:', 're-exporter' ); ?>
				<strong><?php echo esc_html( $agency_title ); ?></strong>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $missing ) ) : ?>
		<div class="re-review-warnings">
			<strong>&#9888; <?php esc_html_e( 'Warnings', 're-exporter' ); ?></strong>
			<ul>
				<li><?php
					echo esc_html( sprintf(
						/* translators: %s = comma-separated field list */
						__( 'Recommended fields not mapped: %s', 're-exporter' ),
						implode( ', ', $missing )
					) );
				?></li>
			</ul>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Realistimo review: single feed.xml, show post count + agency info.
	 *
	 * @param int[] $post_ids
	 */
	private function render_realistimo_review( array $post_ids ) {
		$agency    = $this->settings->get_realistimo_agency();
		$field_map = $this->settings->get_realistimo_field_map();

		// Warn if recommended fields are not mapped.
		$recommended = array( 'price', 'description', 'images', 'category', 'subcategory' );
		$missing     = array();
		foreach ( $recommended as $field ) {
			if ( empty( $field_map[ $field ] ) || '__skip__' === $field_map[ $field ] ) {
				$missing[] = $field;
			}
		}
		?>
		<table class="re-review-table widefat">
			<thead><tr>
				<th><?php esc_html_e( 'Output file', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'Agency UID', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'Posts', 're-exporter' ); ?></th>
			</tr></thead>
			<tbody>
				<tr>
					<td><code>feed.xml</code></td>
					<td><?php echo esc_html( ! empty( $agency['uid'] ) ? $agency['uid'] : '—' ); ?></td>
					<td><?php echo esc_html( count( $post_ids ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php if ( ! empty( $agency['name'] ) ) : ?>
			<p style="margin-top:6px;color:#646970;">
				<?php esc_html_e( 'Agency:', 're-exporter' ); ?>
				<strong><?php echo esc_html( $agency['name'] ); ?></strong>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $missing ) ) : ?>
		<div class="re-review-warnings">
			<strong>&#9888; <?php esc_html_e( 'Warnings', 're-exporter' ); ?></strong>
			<ul>
				<li><?php
					echo esc_html( sprintf(
						/* translators: %s = comma-separated field list */
						__( 'Recommended fields not mapped: %s', 're-exporter' ),
						implode( ', ', $missing )
					) );
				?></li>
			</ul>
		</div>
		<?php endif; ?>
		<?php
	}

	// =========================================================================
	// AJAX — Step 4: Run Export
	// =========================================================================

	/**
	 * Generate export files and return download links.
	 */
	public function ajax_run() {
		$this->verify_request();

		$platform = isset( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';

		if ( ! $this->platform_registry->has( $platform ) ) {
			wp_send_json_error( __( 'Invalid parameters.', 're-exporter' ) );
		}

		$post_ids = $this->get_export_post_ids();
		if ( empty( $post_ids ) ) {
			wp_send_json_error( __( 'No posts found matching the current export filters.', 're-exporter' ) );
		}

		$files = $this->run_service->run( $platform, $post_ids );
		if ( is_wp_error( $files ) ) {
			wp_send_json_error( $files->get_error_message() );
		}

		ob_start();
		$this->render_download_links( $files );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Render download buttons for the generated files.
	 *
	 * @param array[] $files  Each: [ 'filename', 'url', 'count' ]
	 */
	private function render_download_links( array $files ) {
		echo '<div class="notice notice-success"><p>';
		echo esc_html__( 'Export complete!', 're-exporter' );
		echo '</p></div>';

		echo '<ul class="re-download-list">';
		foreach ( $files as $file ) {
			echo '<li>';
			if ( ! empty( $file['link_only'] ) ) {
				// Permanent feed URL — just show the link, no download.
				printf(
					'<strong>%s</strong><br><code style="user-select:all;word-break:break-all;">%s</code> &nbsp; <span>%s</span>',
					esc_html( $file['filename'] ),
					esc_html( $file['url'] ),
					esc_html( sprintf(
						/* translators: %d = post count */
						_n( '%d post', '%d posts', $file['count'], 're-exporter' ),
						$file['count']
					) )
				);
			} else {
				printf(
					'<a href="%s" download="%s" class="button button-primary">&#11015; %s</a> &nbsp; <span>%s</span>',
					esc_url( $file['url'] ),
					esc_attr( $file['filename'] ),
					esc_html( $file['filename'] ),
					esc_html( sprintf(
						/* translators: %d = post count */
						_n( '%d post', '%d posts', $file['count'], 're-exporter' ),
						$file['count']
					) )
				);
				if ( ! empty( $file['category_name'] ) ) {
					echo ' &nbsp; <span class="re-category-label">' . esc_html( $file['category_name'] ) . '</span>';
				}
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Resolve the OLX subcategory ID for a post (runtime — for export).
	 *
	 * Priority:
	 *   1. Taxonomy-based mapping  (if category_tax is configured)
	 *   2. Per-post meta           (_re_exporter_olx_category)
	 *
	 * @param \WP_Post $post
	 * @param string   $category_tax
	 * @param array    $category_map  [ term_slug => subcategory_id ]
	 * @return string  Subcategory ID, or '' if unresolvable.
	 */
	public function resolve_subcat_id( \WP_Post $post, $category_tax, array $category_map ) {
		$rent_map = $this->settings->get_olx_category_rent_map();

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
	 * Select the correct OLX subcategory based on the deal-type field.
	 *
	 * When both sale and rent IDs are provided, the deal-type field (B5)
	 * determines which one to use for the given post.
	 * If only one is provided, that one is returned unconditionally.
	 *
	 * @param string   $sale_id  Sale/primary subcategory ID.
	 * @param string   $rent_id  Rent subcategory ID (may be empty).
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

	/**
	 * Return field keys that are required for a subcategory but not mapped.
	 *
	 * @param string $subcat_id
	 * @param array  $field_map   Global OLX field map.
	 * @param array  $req_map     Per-category supplemental map.
	 * @return string[]  Missing required field keys.
	 */
	private function get_missing_required_fields( $subcat_id, array $field_map, array $req_map ) {
		$required = $this->olx_template->get_required_fields( $subcat_id );
		$missing  = array();

		foreach ( $required as $field_key ) {
			$global_mapped = ! empty( $field_map[ $field_key ] ) && '__skip__' !== $field_map[ $field_key ];
			$cat_mapped    = ! empty( $req_map[ $subcat_id ][ $field_key ] ) && '__skip__' !== $req_map[ $subcat_id ][ $field_key ];

			if ( ! $global_mapped && ! $cat_mapped ) {
				$missing[] = $field_key;
			}
		}

		return $missing;
	}

	// =========================================================================
	// Post ID Resolution
	// =========================================================================

	/**
	 * Query all post IDs eligible for export based on current settings.
	 * Applies the same filters as the old ajax_load_posts step.
	 *
	 * @return int[]
	 */
	private function get_export_post_ids() {
		$post_type = $this->settings->get_post_type();
		if ( ! $post_type ) {
			return array();
		}

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		if ( $this->settings->get_only_published() ) {
			$args['post_status'] = 'publish';
		} else {
			$args['post_status'] = array( 'publish', 'draft', 'private' );
		}

		if ( $this->settings->get_use_per_post_flag() ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_re_export_enabled',
					'value'   => '1',
					'compare' => '=',
				),
			);
		}

		return get_posts( $args );
	}

	// =========================================================================
	// ALO Helpers
	// =========================================================================

	/**
	 * Resolve the ALO subcategory ID for a post (runtime — for export and review).
	 *
	 * Priority:
	 *   1. Taxonomy-based mapping  (if alo_category_tax is configured)
	 *   2. Per-post meta           (_re_exporter_alo_category)
	 *
	 * When a deal-type field is configured AND a rent-alternative map is set,
	 * the deal value determines whether to return the primary (sale) or rent subcat_id.
	 *
	 * @param \WP_Post $post
	 * @param string   $category_tax
	 * @param array    $category_map  [ term_slug => subcat_id (primary/sale) ]
	 * @return string  Subcategory ID, or '' if unresolvable.
	 */
	public function resolve_alo_subcat_id( \WP_Post $post, $category_tax, array $category_map ) {
		$subcat_id = '';

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

		return $this->apply_alo_deal_type( $subcat_id, $post, $category_tax, $category_map );
	}

	/**
	 * If a rent-alternative map is configured and the deal field indicates rent,
	 * swap the sale subcat_id for the rent subcat_id.
	 *
	 * @param string   $subcat_id     Resolved primary (sale) subcat_id.
	 * @param \WP_Post $post
	 * @param string   $category_tax
	 * @param array    $category_map  Used to look up which term produced this subcat_id.
	 * @return string
	 */
	private function apply_alo_deal_type( $subcat_id, \WP_Post $post, $category_tax, array $category_map ) {
		$deal_field = $this->settings->get_alo_deal_type_field();

		if ( ! $deal_field ) {
			return $subcat_id;
		}

		$rent_map = $this->settings->get_alo_category_rent_map();

		// Determine the term slug that produced this subcat_id.
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

		// No rent alternative configured for this term → return as-is.
		if ( ! $term_slug || empty( $rent_map[ $term_slug ] ) ) {
			return $subcat_id;
		}

		$rent_subcat_id = (string) $rent_map[ $term_slug ];

		// Resolve direction.
		$direction = 'sales';
		if ( '__always_rent__' === $deal_field ) {
			$direction = 'rent';
		} elseif ( '__always_sales__' !== $deal_field ) {
			$wp_val    = get_post_meta( $post->ID, $deal_field, true );
			$deal_map  = $this->settings->get_alo_deal_type_map();
			$direction = isset( $deal_map[ $wp_val ] ) ? $deal_map[ $wp_val ] : 'sales';
		}

		return 'rent' === $direction ? $rent_subcat_id : $subcat_id;
	}

	/**
	 * Return required ALO field keys that are not mapped for a subcategory.
	 *
	 * 'out_id' and 'subcat_id' are always auto-populated — skipped.
	 *
	 * @param string $subcat_id
	 * @param array  $field_map  Global ALO field map.
	 * @param array  $req_map    Per-category supplemental map.
	 * @return string[]
	 */
	private function get_alo_missing_required_fields( $subcat_id, array $field_map, array $req_map ) {
		$required = $this->alo_template->get_required_fields( $subcat_id );
		$missing  = array();

		foreach ( $required as $field_key ) {
			if ( in_array( $field_key, array( 'out_id', 'subcat_id' ), true ) ) {
				continue; // Auto-populated — never need explicit mapping.
			}

			$global_mapped = ! empty( $field_map[ $field_key ] ) && '__skip__' !== $field_map[ $field_key ];
			$cat_mapped    = ! empty( $req_map[ $subcat_id ][ $field_key ] ) && '__skip__' !== $req_map[ $subcat_id ][ $field_key ];

			if ( ! $global_mapped && ! $cat_mapped ) {
				$missing[] = $field_key;
			}
		}

		return $missing;
	}

	// =========================================================================
	// Security
	// =========================================================================

	/**
	 * Verify nonce and capability for every AJAX request.
	 */
	private function verify_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 're-exporter' ), 403 );
		}

		$nonce = isset( $_POST['nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 're_exporter_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 're-exporter' ), 403 );
		}
	}
}
