<?php
/**
 * Settings sub-tab: Realistimo
 *
 * Required variables (from page-settings.php scope):
 *   $d               array   All settings data from Admin_Page::get_settings_data().
 *   $this            Admin_Page instance.
 *   $active_sub      string  Current sub-tab.
 *   $post_type       string  Configured CPT slug.
 *
 * @package RE_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$realistimo_agency             = $d['realistimo_agency'];
$realistimo_broker             = $d['realistimo_broker'];
$realistimo_field_map          = $d['realistimo_field_map'];
$realistimo_value_map          = $d['realistimo_value_map'];
$realistimo_location_label_map = $d['realistimo_location_label_map'];
$realistimo_category_tax       = $d['realistimo_category_tax'];
$realistimo_category_defaults  = $d['realistimo_category_defaults'];
$source_fields                 = $d['source_fields'];
$taxonomies                    = $d['taxonomies'];
$tax_terms                     = $d['tax_terms'];
?>

<form method="post" action="">
	<?php wp_nonce_field( 're_exporter_save_realistimo' ); ?>
	<input type="hidden" name="re_action" value="save_realistimo" />

	<!-- ===================================================================
	     E1 — Agency Settings
	     =================================================================== -->
	<div class="re-section">
		<h2><?php esc_html_e( 'E1 — Agency Settings', 're-exporter' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="realistimo_agency_uid">
						<?php esc_html_e( 'Agency UID', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="realistimo_agency_uid"
						name="realistimo_agency_uid"
						value="<?php echo esc_attr( $realistimo_agency['uid'] ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Inserted as <agency_uid> in the feed header (e.g. your agency phone number).', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="realistimo_agency_name">
						<?php esc_html_e( 'Agency Name', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="realistimo_agency_name"
						name="realistimo_agency_name"
						value="<?php echo esc_attr( $realistimo_agency['name'] ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Inserted as <agency_name> in the feed header.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="realistimo_agency_phone">
						<?php esc_html_e( 'Agency Phone', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="realistimo_agency_phone"
						name="realistimo_agency_phone"
						value="<?php echo esc_attr( $realistimo_agency['phone'] ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="realistimo_agency_email">
						<?php esc_html_e( 'Agency Email', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<input
						type="email"
						id="realistimo_agency_email"
						name="realistimo_agency_email"
						value="<?php echo esc_attr( $realistimo_agency['email'] ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="realistimo_agency_result_url">
						<?php esc_html_e( 'Result URL', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<input
						type="url"
						id="realistimo_agency_result_url"
						name="realistimo_agency_result_url"
						value="<?php echo esc_attr( $realistimo_agency['result_url'] ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'URL where Realistimo will send import result notifications.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<!-- ===================================================================
	     E2 — Broker Settings
	     =================================================================== -->
	<div class="re-section">
		<h2><?php esc_html_e( 'E2 — Broker (static, applied to every offer)', 're-exporter' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="realistimo_broker_id"><?php esc_html_e( 'Broker ID', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="text" id="realistimo_broker_id" name="realistimo_broker_id"
						value="<?php echo esc_attr( $realistimo_broker['id'] ); ?>" class="regular-text" />
					<p class="description">
						<?php esc_html_e( 'Used in <broker_id> inside each <offer> and in the <Brokers> header block.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="realistimo_broker_name"><?php esc_html_e( 'Broker Name', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="text" id="realistimo_broker_name" name="realistimo_broker_name"
						value="<?php echo esc_attr( $realistimo_broker['name'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="realistimo_broker_phone"><?php esc_html_e( 'Broker Phone', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="text" id="realistimo_broker_phone" name="realistimo_broker_phone"
						value="<?php echo esc_attr( $realistimo_broker['phone'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="realistimo_broker_email"><?php esc_html_e( 'Broker Email', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="email" id="realistimo_broker_email" name="realistimo_broker_email"
						value="<?php echo esc_attr( $realistimo_broker['email'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="realistimo_broker_photo"><?php esc_html_e( 'Broker Photo URL', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="url" id="realistimo_broker_photo" name="realistimo_broker_photo"
						value="<?php echo esc_attr( $realistimo_broker['photo'] ); ?>" class="regular-text" />
				</td>
			</tr>
		</table>
	</div>

	<!-- ===================================================================
	     E3 — Field Mapping
	     =================================================================== -->
	<div class="re-section">
		<h2><?php esc_html_e( 'E3 — Field Mapping', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Map each Realistimo XML field to a WordPress source. offer_uid, last_update and broker_id are auto-generated — no mapping needed.', 're-exporter' ); ?>
		</p>

		<table class="widefat re-field-map-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Realistimo XML field', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'WordPress source', 're-exporter' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			// Auto-generated fields (info-only rows).
			$auto_fields = array(
				'offer_uid'  => __( 'Auto-generated from post GUID', 're-exporter' ),
				'last_update' => __( 'Auto-generated from post modified date', 're-exporter' ),
				'broker_id'  => __( 'From E2 Broker Settings above', 're-exporter' ),
			);

			foreach ( $auto_fields as $auto_field => $auto_note ) :
			?>
			<tr>
				<td>
					<code><?php echo esc_html( $auto_field ); ?></code>
					<span class="re-badge" style="background:#f0f0f0;color:#646970;">
						<?php esc_html_e( 'auto', 're-exporter' ); ?>
					</span>
					<br /><span style="color:#646970;font-size:12px;"><?php echo esc_html( $auto_note ); ?></span>
				</td>
				<td>
					<span style="color:#646970;font-size:12px;font-style:italic;">
						<?php esc_html_e( '— auto-generated, no mapping required —', 're-exporter' ); ?>
					</span>
				</td>
			</tr>
			<?php endforeach; ?>

			<?php
			// Mappable fields.
			foreach ( \RE_Exporter\Exporter_Realistimo::$field_labels as $field => $label ) :
				// Skip auto-generated fields.
				if ( isset( $auto_fields[ $field ] ) ) {
					continue;
				}

				$saved_src  = isset( $realistimo_field_map[ $field ] ) ? $realistimo_field_map[ $field ] : '__skip__';
				$has_enum   = isset( \RE_Exporter\Exporter_Realistimo::$enum_fields[ $field ] );
				$is_multi   = in_array( $field, \RE_Exporter\Exporter_Realistimo::$multi_fields, true );
				$is_recommended = in_array( $field, array( 'price', 'description', 'images', 'category', 'subcategory' ), true );
			?>
			<tr class="<?php echo $is_recommended ? 're-required-row' : ''; ?>">
				<td>
					<code><?php echo esc_html( $field ); ?></code>
					<?php if ( $is_recommended ) : ?>
						<span class="re-badge re-badge-required"><?php esc_html_e( 'recommended', 're-exporter' ); ?></span>
					<?php endif; ?>
					<?php if ( $has_enum ) : ?>
						<span class="re-badge" style="background:#e8f0fe;color:#1a73e8;">
							<?php esc_html_e( 'values', 're-exporter' ); ?>
						</span>
					<?php endif; ?>
					<?php if ( $is_multi ) : ?>
						<span class="re-badge" style="background:#e6f4ea;color:#137333;">
							<?php esc_html_e( 'multi', 're-exporter' ); ?>
						</span>
					<?php endif; ?>
					<br /><span style="color:#646970;font-size:12px;"><?php echo esc_html( $label ); ?></span>
				</td>
				<td>
					<select name="realistimo_field_map[<?php echo esc_attr( $field ); ?>]" class="re-source-select">
						<option value="__skip__"<?php selected( $saved_src, '__skip__' ); ?>>
							<?php esc_html_e( '— Skip —', 're-exporter' ); ?>
						</option>
						<?php echo re_exporter_source_options( $source_fields, $saved_src, 'realistimo' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</select>
					<?php if ( $is_recommended && '__skip__' === $saved_src ) : ?>
						<span class="re-map-warning">
							&#9888; <?php esc_html_e( 'Recommended — not yet mapped.', 're-exporter' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- ===================================================================
	     E4 — Value Overrides
	     =================================================================== -->
	<?php
	// Determine whether any enum / location field is mapped to a non-token WP source.
	$has_value_overrides = false;
	$enum_field_keys     = array_keys( \RE_Exporter\Exporter_Realistimo::$enum_fields );
	foreach ( $enum_field_keys as $vf ) {
		$src = isset( $realistimo_field_map[ $vf ] ) ? $realistimo_field_map[ $vf ] : '__skip__';
		// Only show value-override section when mapped to a WP source (not a __realistimo_*__ token).
		if ( '__skip__' !== $src && '' !== $src && 0 !== strpos( $src, '__realistimo_' ) ) {
			$has_value_overrides = true;
			break;
		}
	}
	// Also check internal_id for location-AJAX widget.
	if ( ! $has_value_overrides ) {
		$int_src = isset( $realistimo_field_map['internal_id'] ) ? $realistimo_field_map['internal_id'] : '__skip__';
		if ( '__skip__' !== $int_src && '' !== $int_src && '__realistimo_location__' !== $int_src ) {
			$has_value_overrides = true;
		}
	}
	?>
	<?php if ( $has_value_overrides ) : ?>
	<div class="re-section">
		<h2><?php esc_html_e( 'E4 — Value Overrides', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Map WordPress field values to Realistimo allowed values for fields with a controlled vocabulary.', 're-exporter' ); ?>
		</p>

		<?php
		// internal_id location AJAX widget.
		$int_src = isset( $realistimo_field_map['internal_id'] ) ? $realistimo_field_map['internal_id'] : '__skip__';
		if ( '__skip__' !== $int_src && '' !== $int_src && '__realistimo_location__' !== $int_src ) :
			$int_source_values = $this->get_source_values_for( $int_src, $source_fields, $post_type );
			if ( ! empty( $int_source_values ) ) :
				$saved_int_vmap  = isset( $realistimo_value_map['internal_id'] ) ? (array) $realistimo_value_map['internal_id'] : array();
				$saved_int_labels = isset( $realistimo_location_label_map['internal_id'] ) ? (array) $realistimo_location_label_map['internal_id'] : array();
		?>
		<h3 style="margin-top:20px;">internal_id <small style="color:#646970;">(← <?php echo esc_html( $int_src ); ?>)</small></h3>
		<table class="widefat re-value-map-table" style="max-width:600px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'WordPress value', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Realistimo internal_id (geo)', 're-exporter' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $int_source_values as $sv ) :
				$saved_val   = isset( $saved_int_vmap[ $sv['value'] ] ) ? $saved_int_vmap[ $sv['value'] ] : '';
				$saved_label = isset( $saved_int_labels[ $sv['value'] ] ) ? $saved_int_labels[ $sv['value'] ] : '';
				$input_name  = 'realistimo_value_map[internal_id][' . esc_attr( $sv['value'] ) . ']';
			?>
				<tr>
					<td><?php echo esc_html( $sv['label'] ); ?></td>
					<td>
						<div class="re-city-widget"
							data-empty="<?php esc_attr_e( '— Keep original —', 're-exporter' ); ?>"
							data-realistimo-field="internal_id"
						>
							<input type="hidden"
								name="<?php echo esc_attr( $input_name ); ?>"
								class="re-city-val"
								value="<?php echo esc_attr( $saved_val ); ?>"
							/>
							<input type="hidden"
								name="realistimo_location_label_map[internal_id][<?php echo esc_attr( $sv['value'] ); ?>]"
								class="re-city-label-field"
								value="<?php echo esc_attr( $saved_label ); ?>"
							/>
							<div class="re-city-trigger">
								<span class="re-city-label<?php echo ( $saved_val && $saved_label ) ? '' : ' is-placeholder'; ?>">
									<?php echo ( $saved_val && $saved_label ) ? esc_html( $saved_label ) : esc_html__( '— Keep original —', 're-exporter' ); ?>
								</span>
								<span class="re-city-actions">
									<button type="button" class="re-city-clear-btn"<?php echo $saved_val ? ' style="display:inline-block;"' : ''; ?>>&#x2715;</button>
									<span class="re-city-caret"></span>
								</span>
							</div>
							<div class="re-city-panel" style="display:none;">
								<input type="text" class="re-city-search"
									placeholder="<?php esc_attr_e( 'Type to search location…', 're-exporter' ); ?>"
									autocomplete="off"
								/>
								<div class="re-city-results"></div>
							</div>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
			endif;
		endif;

		// Enum field overrides.
		foreach ( $enum_field_keys as $vf ) :
			$src = isset( $realistimo_field_map[ $vf ] ) ? $realistimo_field_map[ $vf ] : '__skip__';
			// Only when mapped to a WP source (not a __realistimo_*__ token).
			if ( '__skip__' === $src || '' === $src || 0 === strpos( $src, '__realistimo_' ) ) {
				continue;
			}
			$source_values = $this->get_source_values_for( $src, $source_fields, $post_type );
			if ( empty( $source_values ) ) {
				continue;
			}
			$saved_vmap  = isset( $realistimo_value_map[ $vf ] ) ? (array) $realistimo_value_map[ $vf ] : array();
			$enum_values = \RE_Exporter\Exporter_Realistimo::$enum_fields[ $vf ];
			$is_multi    = in_array( $vf, \RE_Exporter\Exporter_Realistimo::$multi_fields, true );
		?>
		<h3 style="margin-top:20px;"><?php echo esc_html( $vf ); ?> <small style="color:#646970;">(← <?php echo esc_html( $src ); ?>)</small></h3>
		<table class="widefat re-value-map-table" style="max-width:600px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'WordPress value', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Realistimo value', 're-exporter' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $source_values as $sv ) :
				$saved_val  = isset( $saved_vmap[ $sv['value'] ] ) ? $saved_vmap[ $sv['value'] ] : '';
				$input_name = 'realistimo_value_map[' . esc_attr( $vf ) . '][' . esc_attr( $sv['value'] ) . ']';
			?>
				<tr>
					<td><?php echo esc_html( $sv['label'] ); ?></td>
					<td>
					<?php if ( $is_multi ) :
						$selected_vals = array_filter( array_map( 'trim', explode( ',', $saved_val ) ) );
					?>
						<select name="<?php echo esc_attr( $input_name ); ?>[]" multiple style="min-height:60px;min-width:220px;">
							<?php foreach ( $enum_values as $enum_val => $enum_label ) : ?>
							<option value="<?php echo esc_attr( $enum_val ); ?>"<?php echo in_array( (string) $enum_val, $selected_vals, true ) ? ' selected' : ''; ?>>
								<?php echo esc_html( $enum_label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<select name="<?php echo esc_attr( $input_name ); ?>">
							<option value=""><?php esc_html_e( '— Keep original —', 're-exporter' ); ?></option>
							<?php foreach ( $enum_values as $enum_val => $enum_label ) : ?>
							<option value="<?php echo esc_attr( $enum_val ); ?>"<?php selected( $saved_val, $enum_val ); ?>>
								<?php echo esc_html( $enum_label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<!-- ===================================================================
	     E5 — Default subcategory per category
	     =================================================================== -->
	<div class="re-section">
		<h2><?php esc_html_e( 'E5 — Default subcategory per category', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'For each taxonomy term, set a default Realistimo subcategory. Used when a post has no per-post override (set on the post edit page → Realistimo tab).', 're-exporter' ); ?>
		</p>

		<table class="form-table" role="presentation" style="max-width:600px;">
			<tr>
				<th scope="row">
					<label for="realistimo_category_tax"><?php esc_html_e( 'Category taxonomy', 're-exporter' ); ?></label>
				</th>
				<td>
					<select id="realistimo_category_tax" name="realistimo_category_tax">
						<option value=""><?php esc_html_e( '— None —', 're-exporter' ); ?></option>
						<?php foreach ( $taxonomies as $tax ) : ?>
							<option value="<?php echo esc_attr( $tax->name ); ?>"
								<?php selected( $realistimo_category_tax, $tax->name ); ?>>
								<?php echo esc_html( $tax->label . ' (' . $tax->name . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Select a taxonomy to map its terms to a default subcategory.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php if ( $realistimo_category_tax && ! empty( $tax_terms[ $realistimo_category_tax ] ) ) :
			$terms_for_tax = $tax_terms[ $realistimo_category_tax ];
		?>
		<div style="margin-top:20px;">
		<?php foreach ( $terms_for_tax as $term ) :
			$term_slug = $term['slug'];
			$term_name = $term['name'];
			$saved_subcat = isset( $realistimo_category_defaults[ $term_slug ]['subcategory'] )
				? (string) $realistimo_category_defaults[ $term_slug ]['subcategory']
				: '';
		?>
		<div class="re-section" style="border:1px solid #dcdcde;border-radius:4px;padding:14px 18px;margin-bottom:14px;">
			<h3 style="margin-top:0;"><?php echo esc_html( $term_name ); ?> <small style="color:#646970;font-weight:400;">(<?php echo esc_html( $term_slug ); ?>)</small></h3>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row" style="width:200px;">
						<label><?php esc_html_e( 'Подкатегория', 're-exporter' ); ?></label>
					</th>
					<td>
						<select name="realistimo_category_defaults[<?php echo esc_attr( $term_slug ); ?>][subcategory]"
							style="max-width:300px;">
							<option value=""><?php esc_html_e( '— Не задана —', 're-exporter' ); ?></option>
							<?php foreach ( \RE_Exporter\Exporter_Realistimo::$enum_fields['subcategory'] as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $saved_subcat, $val ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		</div>
		<?php endforeach; ?>
		</div>
		<?php elseif ( ! $realistimo_category_tax ) : ?>
			<p class="description" style="margin-top:12px;">
				<?php esc_html_e( 'Select a taxonomy above and save to configure per-category defaults.', 're-exporter' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="re-action-bar">
		<?php submit_button( __( 'Save Realistimo Settings', 're-exporter' ), 'primary', 'submit', false ); ?>
	</div>

</form>
