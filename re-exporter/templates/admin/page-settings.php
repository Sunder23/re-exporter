<?php
/**
 * Settings tab — renders Section A (Global) + Section B (OLX / ALO).
 *
 * All variables come from Admin_Page::render_page() scope:
 *   $this           Admin_Page instance
 *   $active_sub     'olx' | 'alo'
 *   $post_type      string
 *   $cpt_list       array
 *
 * @package RE_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Collect all Settings data in one call.
$d = $this->get_settings_data();
?>

<!-- ===================================================================
     SECTION A — Global Settings
     =================================================================== -->
<div class="re-section">
	<h2><?php esc_html_e( 'Global Settings', 're-exporter' ); ?></h2>

	<form method="post" action="">
		<?php wp_nonce_field( 're_exporter_save_global' ); ?>
		<input type="hidden" name="re_action" value="save_global" />

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="re_post_type"><?php esc_html_e( 'Post Type', 're-exporter' ); ?></label>
				</th>
				<td>
					<select id="re_post_type" name="re_post_type">
						<option value=""><?php esc_html_e( '— Select post type —', 're-exporter' ); ?></option>
						<?php foreach ( $cpt_list as $cpt ) : ?>
							<option
								value="<?php echo esc_attr( $cpt['slug'] ); ?>"
								<?php selected( $post_type, $cpt['slug'] ); ?>
							>
								<?php echo esc_html( $cpt['label'] ); ?>
								(<?php echo esc_html( $cpt['slug'] ); ?>,
								<?php echo esc_html( number_format_i18n( $cpt['count'] ) ); ?>
								<?php esc_html_e( 'published', 're-exporter' ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'The CPT that will be exported. Changing this reloads all field-mapping sections below.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="re_olx_country"><?php esc_html_e( 'Country', 're-exporter' ); ?></label>
				</th>
				<td>
					<select id="re_olx_country" name="re_olx_country">
						<option value="bg"<?php selected( $this->settings->get_olx_country(), 'bg' ); ?>>Bulgaria — olx.bg</option>
						<option value="pl"<?php selected( $this->settings->get_olx_country(), 'pl' ); ?>>Poland — olx.pl</option>
						<option value="ro"<?php selected( $this->settings->get_olx_country(), 'ro' ); ?>>Romania — olx.ro</option>
						<option value="pt"<?php selected( $this->settings->get_olx_country(), 'pt' ); ?>>Portugal — olx.pt</option>
						<option value="ua"<?php selected( $this->settings->get_olx_country(), 'ua' ); ?>>Ukraine — olx.ua</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'OLX country portal used for city autocomplete.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Filters', 're-exporter' ); ?></th>
				<td>
					<label>
						<input
							type="checkbox"
							name="re_only_published"
							value="1"
							<?php checked( $this->settings->get_only_published() ); ?>
						/>
						<?php esc_html_e( 'Export only published posts', 're-exporter' ); ?>
					</label>
					<br />
					<label>
						<input
							type="checkbox"
							name="re_use_per_post_flag"
							value="1"
							<?php checked( $this->settings->get_use_per_post_flag() ); ?>
						/>
						<?php esc_html_e( 'Respect per-post &quot;Export on board&quot; flag', 're-exporter' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Shared Field Defaults', 're-exporter' ); ?></th>
				<td>
					<p class="description" style="margin-top:0;">
						<?php esc_html_e( 'Configure common listing sources once. Supported platform fields inherit these defaults until a platform tab is saved with a different source or explicit skip.', 're-exporter' ); ?>
					</p>
					<table class="widefat re-field-map-table" style="max-width:960px;margin-top:12px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Shared field', 're-exporter' ); ?></th>
								<th><?php esc_html_e( 'WordPress source', 're-exporter' ); ?></th>
								<th><?php esc_html_e( 'Applies to', 're-exporter' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $d['shared_field_definitions'] as $shared_key => $shared_definition ) :
								$shared_source  = isset( $d['shared_field_map'][ $shared_key ] ) ? $d['shared_field_map'][ $shared_key ] : '__skip__';
								$shared_targets = array();
								if ( ! empty( $shared_definition['targets'] ) && is_array( $shared_definition['targets'] ) ) {
									foreach ( $shared_definition['targets'] as $shared_platform => $shared_platform_fields ) {
										$shared_targets[] = strtoupper( $shared_platform ) . ': ' . implode( ', ', array_map( 'strval', (array) $shared_platform_fields ) );
									}
								}
								?>
								<tr>
									<td>
										<strong><?php echo esc_html( $shared_definition['label'] ); ?></strong>
										<br /><code><?php echo esc_html( $shared_key ); ?></code>
									</td>
									<td>
										<select name="re_shared_field_map[<?php echo esc_attr( $shared_key ); ?>]" class="re-source-select">
											<option value="__skip__"<?php selected( $shared_source, '__skip__' ); ?>>
												<?php esc_html_e( '— Skip —', 're-exporter' ); ?>
											</option>
											<?php echo re_exporter_source_options( $d['source_fields'], $shared_source, 'shared' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										</select>
									</td>
									<td style="color:#646970;font-size:12px;">
										<?php echo esc_html( implode( ' | ', $shared_targets ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</td>
			</tr>
		</table>

		<div class="re-action-bar">
			<?php submit_button( __( 'Save Settings', 're-exporter' ), 'primary', 'submit', false ); ?>
		</div>
	</form>
</div>

<?php if ( ! $post_type ) : ?>
<div class="notice notice-warning inline">
	<p><?php esc_html_e( 'Please select and save a Post Type above to configure the platform settings below.', 're-exporter' ); ?></p>
</div>
<?php return; endif; ?>

<!-- ===================================================================
     SECTION B — Platform sub-tabs
     =================================================================== -->
<nav class="nav-tab-wrapper re-platform-tabs">
	<a
		href="<?php echo esc_url( $this->settings_url( array( 'sub' => 'olx' ) ) ); ?>"
		class="nav-tab<?php echo 'olx' === $active_sub ? ' nav-tab-active' : ''; ?>"
	>
		OLX.bg
	</a>
	<a
		href="<?php echo esc_url( $this->settings_url( array( 'sub' => 'alo' ) ) ); ?>"
		class="nav-tab<?php echo 'alo' === $active_sub ? ' nav-tab-active' : ''; ?>"
	>
		ALO.bg
	</a>
	<a
		href="<?php echo esc_url( $this->settings_url( array( 'sub' => 'imoti' ) ) ); ?>"
		class="nav-tab<?php echo 'imoti' === $active_sub ? ' nav-tab-active' : ''; ?>"
	>
		imoti.net
	</a>
	<a
		href="<?php echo esc_url( $this->settings_url( array( 'sub' => 'realistimo' ) ) ); ?>"
		class="nav-tab<?php echo 'realistimo' === $active_sub ? ' nav-tab-active' : ''; ?>"
	>
		Realistimo
	</a>
</nav>

<?php if ( 'realistimo' === $active_sub ) : ?>
	<?php include RE_EXPORTER_DIR . 'templates/admin/page-settings-realistimo.php'; ?>
<?php elseif ( 'imoti' === $active_sub ) : ?>
	<?php include RE_EXPORTER_DIR . 'templates/admin/page-settings-imoti.php'; ?>
<?php elseif ( 'olx' === $active_sub ) : ?>

<!-- ===================================================================
     OLX SUB-TAB
     =================================================================== -->
<form method="post" action="">
	<?php wp_nonce_field( 're_exporter_save_olx' ); ?>
	<input type="hidden" name="re_action" value="save_olx" />

	<!-- ── B1 Field Mapping ────────────────────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'B1 — Field Mapping', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Map each OLX CSV column to a WordPress source field. All columns from all OLX template files are listed here.', 're-exporter' ); ?>
		</p>
		<?php
		include RE_EXPORTER_DIR . 'templates/admin/partials/field-mapping-table.php';
		?>
	</div>

	<!-- ── B2 Value Overrides ──────────────────────────────────────────── -->
	<?php
	$has_overrides = false;
	foreach ( $d['all_csv_columns'] as $col ) {
		$source = isset( $d['olx_field_map'][ $col ] ) ? $d['olx_field_map'][ $col ] : '__skip__';
		if ( '__skip__' === $source || '' === $source ) {
			continue;
		}
		if ( $this->olx_template->has_json_for_column( $col ) || 'location_district' === $col ) {
			$has_overrides = true;
			break;
		}
	}
	?>
	<?php if ( $has_overrides ) : ?>
	<div class="re-section">
		<h2><?php esc_html_e( 'B2 — Value Overrides', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Map WordPress field values to OLX-accepted values for fields with a controlled vocabulary.', 're-exporter' ); ?>
		</p>
		<?php
		foreach ( $d['all_csv_columns'] as $col ) {
			$source = isset( $d['olx_field_map'][ $col ] ) ? $d['olx_field_map'][ $col ] : '__skip__';
			if ( '__skip__' === $source || '' === $source ) {
				continue;
			}
			if ( ! $this->olx_template->has_json_for_column( $col ) && 'location_district' !== $col ) {
				continue;
			}
			// location_city and location_district use the AJAX city search widget — no JSON needed.
			$json_values = ( 'location_city' === $col || 'location_district' === $col )
				? array()
				: $this->olx_template->get_json_values_for_column( $col );
			$wp_values   = $this->get_source_values_for( $source, $d['source_fields'], $post_type );
			$saved_map        = isset( $d['olx_value_map'][ $col ] ) ? (array) $d['olx_value_map'][ $col ] : array();
			$saved_label_map  = isset( $d['olx_city_label_map'][ $col ] ) ? (array) $d['olx_city_label_map'][ $col ] : array();
			$platform         = 'olx';
			include RE_EXPORTER_DIR . 'templates/admin/partials/value-override-table.php';
		}
		?>
	</div>
	<?php endif; ?>

	<!-- ── B3 Category Mapping ─────────────────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'B3 — Category Mapping', 're-exporter' ); ?></h2>

		<?php if ( ! empty( $d['taxonomies'] ) ) : ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="olx_category_tax">
							<?php esc_html_e( 'Category taxonomy', 're-exporter' ); ?>
						</label>
					</th>
					<td>
						<select name="olx_category_tax" id="olx_category_tax">
							<option value=""><?php esc_html_e( '— None (use per-post metabox) —', 're-exporter' ); ?></option>
							<?php foreach ( $d['taxonomies'] as $tax ) : ?>
								<option
									value="<?php echo esc_attr( $tax->name ); ?>"
									<?php selected( $d['olx_category_tax'], $tax->name ); ?>
								>
									<?php echo esc_html( $tax->label ); ?>
									(<?php echo esc_html( $tax->name ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Which taxonomy term determines the OLX category. The deal type (sale/rent) is routed via B5.', 're-exporter' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php if ( $d['olx_category_tax'] && isset( $d['tax_terms'][ $d['olx_category_tax'] ] ) ) :
				$_olx_terms = $d['tax_terms'][ $d['olx_category_tax'] ];
				?>
				<table class="widefat re-category-map-table" style="margin-top:16px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'WordPress term', 're-exporter' ); ?></th>
							<th><?php esc_html_e( 'OLX category (Sale / Primary)', 're-exporter' ); ?></th>
							<th><?php esc_html_e( 'OLX category (Rent — optional, for B5 routing)', 're-exporter' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $_olx_terms as $term ) :
						$saved_sale = isset( $d['olx_category_map'][ $term['slug'] ] ) ? $d['olx_category_map'][ $term['slug'] ] : '';
						$saved_rent = isset( $d['olx_category_rent_map'][ $term['slug'] ] ) ? $d['olx_category_rent_map'][ $term['slug'] ] : '';
						?>
						<tr>
							<td><?php echo esc_html( $term['name'] ); ?> <code><?php echo esc_html( $term['slug'] ); ?></code></td>
							<td>
								<select name="olx_category_map[<?php echo esc_attr( $term['slug'] ); ?>]" style="min-width:220px;">
									<option value=""><?php esc_html_e( '— Not mapped —', 're-exporter' ); ?></option>
									<?php foreach ( $d['grouped_subcats'] as $g ) : ?>
										<optgroup label="<?php echo esc_attr( $g['name'] ); ?>">
											<?php foreach ( $g['subcategories'] as $sub ) : ?>
												<option value="<?php echo esc_attr( $sub['id'] ); ?>"<?php selected( $saved_sale, $sub['id'] ); ?>>
													<?php echo esc_html( $sub['name'] ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select name="olx_category_rent_map[<?php echo esc_attr( $term['slug'] ); ?>]" style="min-width:220px;">
									<option value=""><?php esc_html_e( '— None —', 're-exporter' ); ?></option>
									<?php foreach ( $d['grouped_subcats'] as $g ) : ?>
										<optgroup label="<?php echo esc_attr( $g['name'] ); ?>">
											<?php foreach ( $g['subcategories'] as $sub ) : ?>
												<option value="<?php echo esc_attr( $sub['id'] ); ?>"<?php selected( $saved_rent, $sub['id'] ); ?>>
													<?php echo esc_html( $sub['name'] ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		<?php else : ?>
			<div class="notice notice-info inline">
				<p>
					<?php esc_html_e( 'No taxonomy found for this post type. OLX category is assigned per-post via the "RE Exporter — OLX Category" metabox on each post edit screen.', 're-exporter' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- ── B4 Required Fields per Category ────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'B4 — Required Fields per Category', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Fields already mapped in B1 are shown as ✅. Unmapped required fields need a source assigned here.', 're-exporter' ); ?>
		</p>

		<?php
		// Show only subcategories mapped in B3; if none mapped yet, show all.
		$_mapped_ids = array_values( array_filter( array_unique( array_merge(
			array_values( (array) $d['olx_category_map'] ),
			array_values( (array) $d['olx_category_rent_map'] )
		) ) ) );
		$_b4_subcats = ! empty( $_mapped_ids )
			? array_values( array_filter( $d['all_subcats'], function ( $sub ) use ( $_mapped_ids ) {
				return in_array( $sub['id'], $_mapped_ids, true );
			} ) )
			: $d['all_subcats'];
		?>
		<?php foreach ( $_b4_subcats as $subcat ) :
			$required = $this->olx_template->get_required_fields( $subcat['id'] );
			if ( empty( $required ) ) {
				continue;
			}
			$field_map_overrides = $this->olx_template->get_field_map_overrides( $subcat['id'] );
			?>
			<div class="re-collapse-block">
				<button type="button" class="re-collapse-toggle" aria-expanded="false">
					<?php echo esc_html( $subcat['name'] ); ?>
					<span class="re-chevron">&#9660;</span>
				</button>
				<div class="re-collapse-body" style="display:none;">
					<?php if ( ! empty( $field_map_overrides ) ) : ?>
						<p class="description" style="margin-top:0;">
							<?php
							$notices = array();
							foreach ( $field_map_overrides as $from => $to ) {
								$notices[] = sprintf(
									/* translators: 1: field key, 2: JSON file key */
									esc_html__( '"%1$s" uses %2$s.json for value overrides in this category.', 're-exporter' ),
									esc_html( $from ),
									esc_html( $to )
								);
							}
							echo implode( ' ', $notices ); // Already escaped above.
							?>
						</p>
					<?php endif; ?>

					<table class="widefat re-field-map-table" style="margin-top:8px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Required field', 're-exporter' ); ?></th>
								<th><?php esc_html_e( 'Source', 're-exporter' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $required as $field_key ) :
							$global_source = isset( $d['olx_field_map'][ $field_key ] ) ? $d['olx_field_map'][ $field_key ] : '__skip__';
							$is_mapped     = '__skip__' !== $global_source && '' !== $global_source;
							$cat_source    = isset( $d['olx_required_map'][ $subcat['id'] ][ $field_key ] )
								? $d['olx_required_map'][ $subcat['id'] ][ $field_key ]
								: '__skip__';
							?>
							<tr>
								<td><code><?php echo esc_html( $field_key ); ?></code></td>
								<td>
								<?php if ( $is_mapped ) : ?>
									<span class="re-mapped-notice">
										&#10003; <?php
										/* translators: %s = source field key */
										printf( esc_html__( 'Mapped in B1 to: %s', 're-exporter' ), esc_html( $global_source ) );
										?>
									</span>
								<?php else : ?>
									<select
										name="olx_required_map[<?php echo esc_attr( $subcat['id'] ); ?>][<?php echo esc_attr( $field_key ); ?>]"
										class="re-req-source-select"
									>
										<option value="__skip__"<?php selected( $cat_source, '__skip__' ); ?>>
											<?php esc_html_e( '— Skip —', 're-exporter' ); ?>
										</option>
										<?php echo re_exporter_source_options( $d['source_fields'], $cat_source ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</select>
									<?php if ( '__skip__' === $cat_source ) : ?>
										<span class="re-map-warning">
											&#9888; <?php esc_html_e( 'Required — not yet mapped', 're-exporter' ); ?>
										</span>
									<?php endif; ?>
								<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<!-- ── B5 Deal Type Routing ────────────────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'B5 — Deal Type Routing (Sales / Rent)', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Used when a term has both a Sale and Rent OLX category configured in B3. Select the WordPress field that determines the deal type.', 're-exporter' ); ?>
		</p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="olx_deal_type_field">
						<?php esc_html_e( 'Deal type field', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<select name="olx_deal_type_field" id="olx_deal_type_field">
						<option value=""><?php esc_html_e( '— Not configured —', 're-exporter' ); ?></option>
						<option value="__always_sales__"<?php selected( $d['olx_deal_field'], '__always_sales__' ); ?>>
							<?php esc_html_e( '— Always Sales —', 're-exporter' ); ?>
						</option>
						<option value="__always_rent__"<?php selected( $d['olx_deal_field'], '__always_rent__' ); ?>>
							<?php esc_html_e( '— Always Rent —', 're-exporter' ); ?>
						</option>
						<?php
						foreach ( $d['source_fields'] as $key => $sf ) {
							if ( 'virtual' === $sf['type'] ) {
								continue;
							}
							printf(
								'<option value="%s"%s>%s (%s)</option>',
								esc_attr( $key ),
								selected( $d['olx_deal_field'], $key, false ),
								esc_html( $sf['label'] ),
								esc_html( $key )
							);
						}
						?>
					</select>
				</td>
			</tr>
		</table>

		<?php
		// Show value→direction mapping if a real field is selected.
		$deal_field = $d['olx_deal_field'];
		if ( $deal_field && ! in_array( $deal_field, array( '__always_sales__', '__always_rent__' ), true ) ) :
			$deal_values = $this->get_source_values_for( $deal_field, $d['source_fields'], $post_type );
			if ( ! empty( $deal_values ) ) :
				?>
			<table class="widefat re-deal-map-table" style="margin-top:16px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'WordPress value', 're-exporter' ); ?></th>
						<th><?php esc_html_e( 'Direction', 're-exporter' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $deal_values as $dv ) :
					$saved_dir = isset( $d['olx_deal_map'][ $dv['value'] ] ) ? $d['olx_deal_map'][ $dv['value'] ] : 'sales';
					?>
					<tr>
						<td><?php echo esc_html( $dv['label'] ); ?></td>
						<td class="re-deal-radio">
							<label>
								<input type="radio"
									name="olx_deal_type_map[<?php echo esc_attr( $dv['value'] ); ?>]"
									value="sales"
									<?php checked( $saved_dir, 'sales' ); ?>
								/>
								<?php esc_html_e( 'Sales', 're-exporter' ); ?>
							</label>
							<label>
								<input type="radio"
									name="olx_deal_type_map[<?php echo esc_attr( $dv['value'] ); ?>]"
									value="rent"
									<?php checked( $saved_dir, 'rent' ); ?>
								/>
								<?php esc_html_e( 'Rent', 're-exporter' ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			endif;
		endif;
		?>
	</div>

	<div class="re-action-bar">
		<?php submit_button( __( 'Save OLX Settings', 're-exporter' ), 'primary', 'submit', false ); ?>
	</div>
</form>

<?php elseif ( 'alo' === $active_sub ) : ?>

<!-- ===================================================================
     ALO SUB-TAB
     =================================================================== -->
<form method="post" action="">
	<?php wp_nonce_field( 're_exporter_save_alo' ); ?>
	<input type="hidden" name="re_action" value="save_alo" />

	<!-- ── C1 Field Mapping ────────────────────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'C1 — Field Mapping', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Map each ALO.bg field to a WordPress source. Fields are derived from the JSON templates for all subcategories.', 're-exporter' ); ?>
		</p>
		<?php
		$_alo_field_map_keys = $this->alo_template->get_all_field_map_keys();
		if ( ! empty( $_alo_field_map_keys ) ) :
		?>
		<p class="description" style="margin-top:4px;color:#1d6f42;">
			&#9432; <?php
			/* translators: %s = comma-separated list of field keys */
			printf(
				esc_html__( 'The following fields have subcategory-specific allowed values and are configured per-post in the metabox: %s', 're-exporter' ),
				'<strong>' . esc_html( implode( ', ', $_alo_field_map_keys ) ) . '</strong>'
			);
			?>
		</p>
		<?php endif; ?>

		<table class="widefat re-field-map-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ALO field', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'WordPress source', 're-exporter' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			// Columns always required by at least one subcategory.
			$_alo_always_required = array( 'out_id', 'region_name', 'location_name', 'price', 'currency' );
			// Fields excluded from global mapping: subcat_id (auto-resolved) + field_map keys (per-post metabox)
			// + contacts.* (managed in C6 static contacts section).
			$_alo_excluded = array_merge( array( 'subcat_id' ), $_alo_field_map_keys );
			foreach ( $d['alo_all_fields'] as $_cf ) {
				if ( 0 === strpos( $_cf, 'contacts.' ) ) {
					$_alo_excluded[] = $_cf;
				}
			}
			foreach ( $d['alo_all_fields'] as $field ) :
				if ( in_array( $field, $_alo_excluded, true ) ) {
					continue;
				}
				$saved_src  = isset( $d['alo_field_map'][ $field ] ) ? $d['alo_field_map'][ $field ] : '__skip__';
				$is_common  = in_array( $field, $_alo_always_required, true );
				$has_json   = ! empty( $this->alo_template->get_json_keys_for_field( $field ) );
			?>
				<tr class="<?php echo $is_common ? 're-required-row' : ''; ?>">
					<td>
						<code><?php echo esc_html( $field ); ?></code>
						<?php if ( $is_common ) : ?>
							<span class="re-badge re-badge-required"><?php esc_html_e( 'common', 're-exporter' ); ?></span>
						<?php endif; ?>
						<?php if ( $has_json ) : ?>
							<span class="re-badge" style="background:#e8f0fe;color:#1a73e8;">
								<?php esc_html_e( 'values', 're-exporter' ); ?>
							</span>
						<?php endif; ?>
					</td>
					<td>
						<select name="alo_field_map[<?php echo esc_attr( str_replace( '.', '|', $field ) ); ?>]" class="re-source-select">
							<option value="__skip__"<?php selected( $saved_src, '__skip__' ); ?>>
								<?php esc_html_e( '— Skip —', 're-exporter' ); ?>
							</option>
							<?php echo re_exporter_source_options( $d['source_fields'], $saved_src, 'alo' ); // phpcs:ignore ?>
						</select>
						<?php if ( $is_common && '__skip__' === $saved_src ) : ?>
							<span class="re-map-warning">
								&#9888; <?php esc_html_e( 'Commonly required — not yet mapped.', 're-exporter' ); ?>
							</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- ── C2 Value Overrides ──────────────────────────────────────────── -->
	<?php
	$alo_has_overrides = false;
	foreach ( $d['alo_all_fields'] as $field ) {
		if ( in_array( $field, $_alo_excluded, true ) ) {
			continue;
		}
		$src = isset( $d['alo_field_map'][ $field ] ) ? $d['alo_field_map'][ $field ] : '__skip__';
		if ( '__skip__' === $src || '' === $src ) {
			continue;
		}
		if ( $this->alo_template->is_location_field( $field ) ) {
			$alo_has_overrides = true;
			break;
		}
		if ( ! empty( $this->alo_template->get_json_keys_for_field( $field ) ) ) {
			$alo_has_overrides = true;
			break;
		}
	}
	?>
	<?php if ( $alo_has_overrides ) : ?>
	<div class="re-section">
		<h2><?php esc_html_e( 'C2 — Value Overrides', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Map WordPress field values to ALO-accepted values for fields with a controlled vocabulary.', 're-exporter' ); ?>
		</p>
		<?php
		foreach ( $d['alo_all_fields'] as $field ) {
			if ( in_array( $field, $_alo_excluded, true ) ) {
				continue;
			}
			$source = isset( $d['alo_field_map'][ $field ] ) ? $d['alo_field_map'][ $field ] : '__skip__';
			if ( '__skip__' === $source || '' === $source ) {
				continue;
			}

			$is_location = $this->alo_template->is_location_field( $field );
			$json_keys   = $is_location ? array() : $this->alo_template->get_json_keys_for_field( $field );

			if ( ! $is_location && empty( $json_keys ) ) {
				continue;
			}

			$wp_values = $this->get_source_values_for( $source, $d['source_fields'], $post_type );
			if ( empty( $wp_values ) ) {
				continue;
			}

			if ( $is_location ) {
				// Location field with WP source: city widget per each WP value.
				$col                = $field;
				$alo_location_field = $field;
				$json_values        = array();
				$json_optgroups     = array();
				$saved_map          = isset( $d['alo_value_map'][ $col ] ) ? (array) $d['alo_value_map'][ $col ] : array();
				$saved_label_map    = isset( $d['alo_location_label_map'][ $col ] ) ? (array) $d['alo_location_label_map'][ $col ] : array();
				$platform           = 'alo';
				include RE_EXPORTER_DIR . 'templates/admin/partials/value-override-table.php';
				unset( $alo_location_field, $json_optgroups ); // Clear for next iteration.
			} else {
				foreach ( $json_keys as $jk ) {
					// Use the json_key as the column key for storage when there are multiples.
					$col             = count( $json_keys ) > 1 ? $jk : $field;
					$json_values     = $this->alo_template->get_json_values( $jk );
					$json_optgroups  = array();
					$saved_map       = isset( $d['alo_value_map'][ $col ] ) ? (array) $d['alo_value_map'][ $col ] : array();
					$saved_label_map = array();
					$platform        = 'alo';
					include RE_EXPORTER_DIR . 'templates/admin/partials/value-override-table.php';
				}
			}
		}
		?>
	</div>
	<?php endif; ?>

	<!-- ── C3 Category Mapping ─────────────────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'C3 — Category Mapping', 're-exporter' ); ?></h2>

		<?php if ( ! empty( $d['taxonomies'] ) ) : ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alo_category_tax">
							<?php esc_html_e( 'Category taxonomy', 're-exporter' ); ?>
						</label>
					</th>
					<td>
						<select name="alo_category_tax" id="alo_category_tax">
							<option value=""><?php esc_html_e( '— None (use per-post metabox) —', 're-exporter' ); ?></option>
							<?php foreach ( $d['taxonomies'] as $tax ) : ?>
								<option
									value="<?php echo esc_attr( $tax->name ); ?>"
									<?php selected( $d['alo_category_tax'], $tax->name ); ?>
								>
									<?php echo esc_html( $tax->label ); ?>
									(<?php echo esc_html( $tax->name ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Which taxonomy determines the ALO subcategory. The deal type (sale/rent) is configured in C5.', 're-exporter' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php if ( $d['alo_category_tax'] && isset( $d['tax_terms'][ $d['alo_category_tax'] ] ) ) :
				$_alo_terms = $d['tax_terms'][ $d['alo_category_tax'] ];
				?>
				<table class="widefat re-category-map-table" style="margin-top:16px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Term', 're-exporter' ); ?></th>
							<th><?php esc_html_e( 'ALO category (Sale / Primary)', 're-exporter' ); ?></th>
							<th><?php esc_html_e( 'ALO category (Rent — optional, for C5 routing)', 're-exporter' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $_alo_terms as $term ) :
						$saved_sale = isset( $d['alo_category_map'][ $term['slug'] ] ) ? $d['alo_category_map'][ $term['slug'] ] : '';
						$saved_rent = isset( $d['alo_category_rent_map'][ $term['slug'] ] ) ? $d['alo_category_rent_map'][ $term['slug'] ] : '';
						?>
						<tr>
							<td><?php echo esc_html( $term['name'] ); ?> <code><?php echo esc_html( $term['slug'] ); ?></code></td>
							<td>
								<select name="alo_category_map[<?php echo esc_attr( $term['slug'] ); ?>]" style="min-width:220px;">
									<option value=""><?php esc_html_e( '— Not mapped —', 're-exporter' ); ?></option>
									<?php foreach ( $d['alo_grouped_subcats'] as $g ) : ?>
										<optgroup label="<?php echo esc_attr( $g['name'] ); ?>">
											<?php foreach ( $g['subcategories'] as $sub ) : ?>
												<option value="<?php echo esc_attr( $sub['id'] ); ?>"<?php selected( $saved_sale, $sub['id'] ); ?>>
													<?php echo esc_html( $sub['name'] ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select name="alo_category_rent_map[<?php echo esc_attr( $term['slug'] ); ?>]" style="min-width:220px;">
									<option value=""><?php esc_html_e( '— None —', 're-exporter' ); ?></option>
									<?php foreach ( $d['alo_grouped_subcats'] as $g ) : ?>
										<optgroup label="<?php echo esc_attr( $g['name'] ); ?>">
											<?php foreach ( $g['subcategories'] as $sub ) : ?>
												<option value="<?php echo esc_attr( $sub['id'] ); ?>"<?php selected( $saved_rent, $sub['id'] ); ?>>
													<?php echo esc_html( $sub['name'] ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		<?php else : ?>
			<div class="notice notice-info inline">
				<p>
					<?php esc_html_e( 'No taxonomy found for this post type. ALO category is assigned per-post via the "RE Exporter — ALO Category" metabox.', 're-exporter' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- ── C4 Required Fields per Category ────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'C4 — Required Fields per Category', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Fields already mapped in C1 are shown as ✅. Unmapped required fields need a source assigned here.', 're-exporter' ); ?>
		</p>

		<?php
		$_alo_mapped_ids = array_values( array_filter( array_unique( array_merge(
			array_values( (array) $d['alo_category_map'] ),
			array_values( (array) $d['alo_category_rent_map'] )
		) ) ) );
		$_c4_subcats = ! empty( $_alo_mapped_ids )
			? array_values( array_filter( $d['alo_all_subcats'], function ( $sub ) use ( $_alo_mapped_ids ) {
				return in_array( (string) $sub['id'], $_alo_mapped_ids, true );
			} ) )
			: $d['alo_all_subcats'];
		?>
		<?php foreach ( $_c4_subcats as $subcat ) :
			$required = $this->alo_template->get_required_fields( $subcat['id'] );
			if ( empty( $required ) ) {
				continue;
			}
			$field_map_info = $this->alo_template->get_field_map( $subcat['id'] );
			?>
			<div class="re-collapse-block">
				<button type="button" class="re-collapse-toggle" aria-expanded="false">
					<?php echo esc_html( $subcat['name'] ); ?>
					<span class="re-chevron">&#9660;</span>
				</button>
				<div class="re-collapse-body" style="display:none;">
					<?php if ( ! empty( $field_map_info ) ) : ?>
						<p class="description" style="margin-top:0;color:#1d6f42;">
							&#9432; <?php
							printf(
								/* translators: %s = comma-separated field names */
								esc_html__( 'Category-specific fields (set per-post in metabox): %s', 're-exporter' ),
								'<strong>' . esc_html( implode( ', ', array_keys( $field_map_info ) ) ) . '</strong>'
							);
							?>
						</p>
					<?php endif; ?>

					<table class="widefat re-field-map-table" style="margin-top:8px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Required field', 're-exporter' ); ?></th>
								<th><?php esc_html_e( 'Source', 're-exporter' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $required as $field_key ) :
							if ( in_array( $field_key, $_alo_excluded, true ) ) {
								continue; // Auto-populated or per-post metabox — skip.
							}
							$global_source = isset( $d['alo_field_map'][ $field_key ] ) ? $d['alo_field_map'][ $field_key ] : '__skip__';
							$is_mapped     = '__skip__' !== $global_source && '' !== $global_source;
							$cat_source    = isset( $d['alo_required_map'][ $subcat['id'] ][ $field_key ] )
								? $d['alo_required_map'][ $subcat['id'] ][ $field_key ]
								: '__skip__';
							?>
							<tr>
								<td><code><?php echo esc_html( $field_key ); ?></code></td>
								<td>
								<?php if ( $is_mapped ) : ?>
									<span class="re-mapped-notice">
										&#10003; <?php printf( esc_html__( 'Mapped in C1 to: %s', 're-exporter' ), esc_html( $global_source ) ); ?>
									</span>
								<?php else : ?>
									<select
										name="alo_required_map[<?php echo esc_attr( $subcat['id'] ); ?>][<?php echo esc_attr( str_replace( '.', '|', $field_key ) ); ?>]"
										class="re-req-source-select"
									>
										<option value="__skip__"<?php selected( $cat_source, '__skip__' ); ?>>
											<?php esc_html_e( '— Skip —', 're-exporter' ); ?>
										</option>
										<?php echo re_exporter_source_options( $d['source_fields'], $cat_source, 'alo' ); // phpcs:ignore ?>
									</select>
									<?php if ( '__skip__' === $cat_source ) : ?>
										<span class="re-map-warning">
											&#9888; <?php esc_html_e( 'Required — not yet mapped', 're-exporter' ); ?>
										</span>
									<?php endif; ?>
								<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<!-- ── C5 Deal Type Routing ────────────────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'C5 — Deal Type Routing (Sales / Rent)', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Used when a term has both a Sale and Rent ALO category configured in C3. Select the WordPress field that determines the deal type.', 're-exporter' ); ?>
		</p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="alo_deal_type_field">
						<?php esc_html_e( 'Deal type field', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<select name="alo_deal_type_field" id="alo_deal_type_field">
						<option value=""><?php esc_html_e( '— Not configured —', 're-exporter' ); ?></option>
						<option value="__always_sales__"<?php selected( $d['alo_deal_field'], '__always_sales__' ); ?>>
							<?php esc_html_e( '— Always Sales —', 're-exporter' ); ?>
						</option>
						<option value="__always_rent__"<?php selected( $d['alo_deal_field'], '__always_rent__' ); ?>>
							<?php esc_html_e( '— Always Rent —', 're-exporter' ); ?>
						</option>
						<?php
						foreach ( $d['source_fields'] as $key => $sf ) {
							if ( 'virtual' === $sf['type'] ) {
								continue;
							}
							printf(
								'<option value="%s"%s>%s (%s)</option>',
								esc_attr( $key ),
								selected( $d['alo_deal_field'], $key, false ),
								esc_html( $sf['label'] ),
								esc_html( $key )
							);
						}
						?>
					</select>
				</td>
			</tr>
		</table>

		<?php
		$alo_deal_field = $d['alo_deal_field'];
		if ( $alo_deal_field && ! in_array( $alo_deal_field, array( '__always_sales__', '__always_rent__' ), true ) ) :
			$alo_deal_values = $this->get_source_values_for( $alo_deal_field, $d['source_fields'], $post_type );
			if ( ! empty( $alo_deal_values ) ) :
				?>
			<table class="widefat re-deal-map-table" style="margin-top:16px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'WordPress value', 're-exporter' ); ?></th>
						<th><?php esc_html_e( 'Direction', 're-exporter' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $alo_deal_values as $dv ) :
					$saved_dir = isset( $d['alo_deal_map'][ $dv['value'] ] ) ? $d['alo_deal_map'][ $dv['value'] ] : 'sales';
					?>
					<tr>
						<td><?php echo esc_html( $dv['label'] ); ?></td>
						<td class="re-deal-radio">
							<label>
								<input type="radio"
									name="alo_deal_type_map[<?php echo esc_attr( $dv['value'] ); ?>]"
									value="sales"
									<?php checked( $saved_dir, 'sales' ); ?>
								/>
								<?php esc_html_e( 'Sales', 're-exporter' ); ?>
							</label>
							<label>
								<input type="radio"
									name="alo_deal_type_map[<?php echo esc_attr( $dv['value'] ); ?>]"
									value="rent"
									<?php checked( $saved_dir, 'rent' ); ?>
								/>
								<?php esc_html_e( 'Rent', 're-exporter' ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			endif;
		endif;
		?>
	</div>

	<!-- ── C6 Static Contacts ─────────────────────────────────────────── -->
	<div class="re-section">
		<h2><?php esc_html_e( 'C6 — Contacts', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Static contact details injected into every ALO export. Phones and email are the same for all listings. The hide_* flags control visibility on the ALO portal.', 're-exporter' ); ?>
		</p>

		<?php
		$_c6 = $d['alo_contacts'];
		// Pad phones array to always show 5 rows.
		$_c6_phones = array_pad( (array) $_c6['phones'], 5, '' );
		?>
		<table class="form-table" role="presentation">
			<?php for ( $i = 0; $i < 5; $i++ ) : ?>
			<tr>
				<th scope="row">
					<label for="alo_contacts_phone_<?php echo esc_attr( $i ); ?>">
						<?php
						/* translators: %d = phone number index 1–5 */
						printf( esc_html__( 'Phone %d', 're-exporter' ), $i + 1 );
						?>
					</label>
				</th>
				<td>
					<input
						type="tel"
						id="alo_contacts_phone_<?php echo esc_attr( $i ); ?>"
						name="alo_contacts_phones[]"
						value="<?php echo esc_attr( $_c6_phones[ $i ] ); ?>"
						class="regular-text"
						placeholder="+359…"
					/>
				</td>
			</tr>
			<?php endfor; ?>
			<tr>
				<th scope="row">
					<label for="alo_contacts_email">
						<?php esc_html_e( 'Email', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<input
						type="email"
						id="alo_contacts_email"
						name="alo_contacts_email"
						value="<?php echo esc_attr( $_c6['email'] ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Privacy toggles', 're-exporter' ); ?></th>
				<td>
					<?php
					$_c6_toggles = array(
						'hide_name'     => __( 'Hide name', 're-exporter' ),
						'hide_location' => __( 'Hide location', 're-exporter' ),
						'hide_address'  => __( 'Hide address', 're-exporter' ),
						'hide_website'  => __( 'Hide website', 're-exporter' ),
					);
					foreach ( $_c6_toggles as $_toggle_key => $_toggle_label ) :
					?>
					<label style="display:inline-block;margin-right:20px;">
						<input
							type="checkbox"
							name="alo_contacts_<?php echo esc_attr( $_toggle_key ); ?>"
							value="1"
							<?php checked( 'TRUE', $_c6[ $_toggle_key ] ); ?>
						/>
						<?php echo esc_html( $_toggle_label ); ?>
					</label>
					<?php endforeach; ?>
					<p class="description" style="margin-top:6px;">
						<?php esc_html_e( 'Checked = send "TRUE" to ALO (hides that field on the listing page).', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<div class="re-action-bar">
		<?php submit_button( __( 'Save ALO Settings', 're-exporter' ), 'primary', 'submit', false ); ?>
	</div>
</form>

<?php endif; // end OLX/ALO sub-tab ?>

<?php
// ── Template helper functions ──────────────────────────────────────────────

function re_exporter_source_options( array $source_fields, $selected_key, $context = 'olx' ) {
	ob_start();
	?>
	<optgroup label="<?php esc_attr_e( 'Generated Fields', 're-exporter' ); ?>">
		<?php
		foreach ( RE_Exporter\Field_Scanner::get_virtual_fields() as $vk => $vf ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $vk ),
				selected( $selected_key, $vk, false ),
				esc_html( $vf['label'] )
			);
		}
		?>
	</optgroup>

	<?php
	$acf_meta = array();
	$tax_flds = array();

	foreach ( $source_fields as $key => $sf ) {
		if ( 'virtual' === $sf['type'] ) {
			continue;
		}
		if ( 'taxonomy' === $sf['type'] ) {
			$tax_flds[ $key ] = $sf;
		} else {
			$acf_meta[ $key ] = $sf;
		}
	}

	if ( ! empty( $acf_meta ) ) :
		?>
	<optgroup label="<?php esc_attr_e( 'Post Meta / ACF Fields', 're-exporter' ); ?>">
		<?php
		foreach ( $acf_meta as $key => $sf ) {
			printf(
				'<option value="%s"%s>%s (%s)</option>',
				esc_attr( $key ),
				selected( $selected_key, $key, false ),
				esc_html( $sf['label'] ),
				esc_html( $key )
			);
		}
		?>
	</optgroup>
	<?php endif; ?>

	<?php if ( ! empty( $tax_flds ) ) : ?>
	<optgroup label="<?php esc_attr_e( 'Taxonomy Terms', 're-exporter' ); ?>">
		<?php
		foreach ( $tax_flds as $key => $sf ) {
			printf(
				'<option value="%s"%s>%s (taxonomy)</option>',
				esc_attr( $key ),
				selected( $selected_key, $key, false ),
				esc_html( $sf['label'] )
			);
		}
		?>
	</optgroup>
	<?php endif; ?>

	<?php
	// OLX JSON-backed per-post fields (from templates/olx/json/).
	if ( in_array( $context, array( 'olx', 'all' ), true ) ) :
		$_olx_dir   = defined( 'RE_EXPORTER_OLX_TEMPLATES' ) ? RE_EXPORTER_OLX_TEMPLATES . 'json/' : '';
		$_olx_files = ( $_olx_dir && is_dir( $_olx_dir ) ) ? (array) glob( $_olx_dir . '*.json' ) : array();
		sort( $_olx_files );
		if ( ! empty( $_olx_files ) ) :
			?>
	<optgroup label="<?php esc_attr_e( '— OLX: per-post value (fill per post)', 're-exporter' ); ?>">
		<?php
		foreach ( $_olx_files as $_jf ) {
			$_jkey = basename( $_jf, '.json' );
			$_jval = '__json__' . $_jkey;
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $_jval ),
				selected( $selected_key, $_jval, false ),
				esc_html( $_jkey )
			);
		}
		?>
	</optgroup>
		<?php
		endif;
	endif;
	?>

	<?php
	// ALO JSON-backed per-post fields (from templates/alo_bg/json/).
	if ( in_array( $context, array( 'alo', 'all' ), true ) ) :
		?>
	<optgroup label="<?php esc_attr_e( '— ALO: Location widget (per post)', 're-exporter' ); ?>">
		<option value="__alo_region__"<?php selected( $selected_key, '__alo_region__' ); ?>>
			<?php esc_html_e( '— ALO: Region (location widget)', 're-exporter' ); ?>
		</option>
		<option value="__alo_location__"<?php selected( $selected_key, '__alo_location__' ); ?>>
			<?php esc_html_e( '— ALO: Location (location widget)', 're-exporter' ); ?>
		</option>
		<option value="__alo_section__"<?php selected( $selected_key, '__alo_section__' ); ?>>
			<?php esc_html_e( '— ALO: Section (location widget)', 're-exporter' ); ?>
		</option>
		<option value="__alo_section_string__"<?php selected( $selected_key, '__alo_section_string__' ); ?>>
			<?php esc_html_e( '— ALO: Section free text (when NULL)', 're-exporter' ); ?>
		</option>
	</optgroup>
	<?php

		$_alo_dir   = defined( 'RE_EXPORTER_ALO_TEMPLATES' ) ? RE_EXPORTER_ALO_TEMPLATES . 'json/' : '';
		$_alo_files = ( $_alo_dir && is_dir( $_alo_dir ) ) ? (array) glob( $_alo_dir . '*.json' ) : array();
		sort( $_alo_files );
		if ( ! empty( $_alo_files ) ) :
			?>
	<optgroup label="<?php esc_attr_e( '— ALO: per-post value (fill per post)', 're-exporter' ); ?>">
		<?php
		foreach ( $_alo_files as $_jf ) {
			$_jkey = basename( $_jf, '.json' );
			$_jval = '__json__' . $_jkey;
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $_jval ),
				selected( $selected_key, $_jval, false ),
				esc_html( $_jkey )
			);
		}
		?>
	</optgroup>
		<?php
		endif;
	endif;
	?>

	<optgroup label="<?php esc_attr_e( '— Manual input per post (text)', 're-exporter' ); ?>">
		<option value="__text__"<?php selected( $selected_key, '__text__' ); ?>>
			<?php esc_html_e( '— Manual text input (per post)', 're-exporter' ); ?>
		</option>
	</optgroup>

	<optgroup label="<?php esc_attr_e( '— Manual input per post (number)', 're-exporter' ); ?>">
		<option value="__number__"<?php selected( $selected_key, '__number__' ); ?>>
			<?php esc_html_e( '— Manual number input (per post)', 're-exporter' ); ?>
		</option>
	</optgroup>

	<optgroup label="<?php esc_attr_e( '— City / District (OLX API)', 're-exporter' ); ?>">
		<option value="__city__"<?php selected( $selected_key, '__city__' ); ?>>
			<?php esc_html_e( '— City ID (OLX API, per post)', 're-exporter' ); ?>
		</option>
		<option value="__district__"<?php selected( $selected_key, '__district__' ); ?>>
			<?php esc_html_e( '— District ID (auto from city widget)', 're-exporter' ); ?>
		</option>
	</optgroup>

	<?php if ( in_array( $context, array( 'imoti', 'all' ), true ) ) : ?>
	<optgroup label="<?php esc_attr_e( '— Per-post dropdown (imoti.net)', 're-exporter' ); ?>">
		<option value="__imoti_offertype__"<?php selected( $selected_key, '__imoti_offertype__' ); ?>>
			<?php esc_html_e( '— OfferType (imoti.net dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__imoti_buildtype__"<?php selected( $selected_key, '__imoti_buildtype__' ); ?>>
			<?php esc_html_e( '— BuildType (imoti.net dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__imoti_currency__"<?php selected( $selected_key, '__imoti_currency__' ); ?>>
			<?php esc_html_e( '— CurrencyID (imoti.net dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__imoti_extras__"<?php selected( $selected_key, '__imoti_extras__' ); ?>>
			<?php esc_html_e( '— Extras (imoti.net checkboxes, per post)', 're-exporter' ); ?>
		</option>
	</optgroup>
	<optgroup label="<?php esc_attr_e( '— Region / Subregion (imoti.net, per post)', 're-exporter' ); ?>">
		<option value="__imoti_region__"<?php selected( $selected_key, '__imoti_region__' ); ?>>
			<?php esc_html_e( '— EstateRegion (imoti.net widget, per post)', 're-exporter' ); ?>
		</option>
		<option value="__imoti_subregion__"<?php selected( $selected_key, '__imoti_subregion__' ); ?>>
			<?php esc_html_e( '— EstateSubRegion (imoti.net widget, per post)', 're-exporter' ); ?>
		</option>
	</optgroup>
	<?php endif; ?>

	<?php if ( in_array( $context, array( 'realistimo', 'all' ), true ) ) : ?>
	<optgroup label="<?php esc_attr_e( '— Per-post location (Realistimo geo, per post)', 're-exporter' ); ?>">
		<option value="__realistimo_location__"<?php selected( $selected_key, '__realistimo_location__' ); ?>>
			<?php esc_html_e( '— internal_id (Realistimo geo widget, per post)', 're-exporter' ); ?>
		</option>
	</optgroup>
	<optgroup label="<?php esc_attr_e( '— Per-post dropdown (Realistimo, per post)', 're-exporter' ); ?>">
		<option value="__realistimo_currency__"<?php selected( $selected_key, '__realistimo_currency__' ); ?>>
			<?php esc_html_e( '— currency (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_category__"<?php selected( $selected_key, '__realistimo_category__' ); ?>>
			<?php esc_html_e( '— category (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_subcategory__"<?php selected( $selected_key, '__realistimo_subcategory__' ); ?>>
			<?php esc_html_e( '— subcategory (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_construction_type__"<?php selected( $selected_key, '__realistimo_construction_type__' ); ?>>
			<?php esc_html_e( '— construction_type (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_building_condition__"<?php selected( $selected_key, '__realistimo_building_condition__' ); ?>>
			<?php esc_html_e( '— building_condition (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_furnished__"<?php selected( $selected_key, '__realistimo_furnished__' ); ?>>
			<?php esc_html_e( '— furnished (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_heating_ids__"<?php selected( $selected_key, '__realistimo_heating_ids__' ); ?>>
			<?php esc_html_e( '— heating_ids (Realistimo checkboxes, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_parking_ids__"<?php selected( $selected_key, '__realistimo_parking_ids__' ); ?>>
			<?php esc_html_e( '— parking_ids (Realistimo checkboxes, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_new_construction__"<?php selected( $selected_key, '__realistimo_new_construction__' ); ?>>
			<?php esc_html_e( '— new_construction (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_exterior_ids__"<?php selected( $selected_key, '__realistimo_exterior_ids__' ); ?>>
			<?php esc_html_e( '— exterior_ids (Realistimo checkboxes, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_land_category__"<?php selected( $selected_key, '__realistimo_land_category__' ); ?>>
			<?php esc_html_e( '— land_category (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_parcel_in_regulation__"<?php selected( $selected_key, '__realistimo_parcel_in_regulation__' ); ?>>
			<?php esc_html_e( '— parcel_in_regulation (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
		<option value="__realistimo_Exclusive__"<?php selected( $selected_key, '__realistimo_Exclusive__' ); ?>>
			<?php esc_html_e( '— Exclusive (Realistimo dropdown, per post)', 're-exporter' ); ?>
		</option>
	</optgroup>
	<?php endif; ?>

	<?php

	return ob_get_clean();
}
