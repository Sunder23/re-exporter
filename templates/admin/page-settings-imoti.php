<?php
/**
 * Settings sub-tab: imoti.net
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

$imoti_field_map          = $d['imoti_field_map'];
$imoti_value_map          = $d['imoti_value_map'];
$imoti_broker             = $d['imoti_broker'];
$imoti_category_tax       = $d['imoti_category_tax'];
$imoti_category_defaults  = $d['imoti_category_defaults'];
$imoti_location_label_map = $d['imoti_location_label_map'];
$source_fields            = $d['source_fields'];
$taxonomies               = $d['taxonomies'];
$tax_terms                = $d['tax_terms'];
?>

<form method="post" action="">
	<?php wp_nonce_field( 're_exporter_save_imoti' ); ?>
	<input type="hidden" name="re_action" value="save_imoti" />

	<!-- ===================================================================
	     D1 — Agency Settings
	     =================================================================== -->
	<div class="re-section">
		<h2><?php esc_html_e( 'D1 — Agency Settings', 're-exporter' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="imoti_agency_id">
						<?php esc_html_e( 'Agency ID', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="imoti_agency_id"
						name="imoti_agency_id"
						value="<?php echo esc_attr( $d['imoti_agency_id'] ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Your imoti.net AgencyID — inserted as <AgencyID> in the feed header.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="imoti_agency_title">
						<?php esc_html_e( 'Agency Name', 're-exporter' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="imoti_agency_title"
						name="imoti_agency_title"
						value="<?php echo esc_attr( $d['imoti_agency_title'] ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Inserted as <title> in the feed header.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<!-- ===================================================================
	     D2 — Broker Settings
	     =================================================================== -->
	<div class="re-section">
		<h2><?php esc_html_e( 'D2 — Broker (static, applied to every item)', 're-exporter' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="imoti_broker_id"><?php esc_html_e( 'Broker ID', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="text" id="imoti_broker_id" name="imoti_broker_id"
						value="<?php echo esc_attr( $imoti_broker['id'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="imoti_broker_name"><?php esc_html_e( 'Broker Name', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="text" id="imoti_broker_name" name="imoti_broker_name"
						value="<?php echo esc_attr( $imoti_broker['name'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="imoti_broker_phone"><?php esc_html_e( 'Broker Phone', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="text" id="imoti_broker_phone" name="imoti_broker_phone"
						value="<?php echo esc_attr( $imoti_broker['phone'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="imoti_broker_email"><?php esc_html_e( 'Broker Email', 're-exporter' ); ?></label>
				</th>
				<td>
					<input type="email" id="imoti_broker_email" name="imoti_broker_email"
						value="<?php echo esc_attr( $imoti_broker['email'] ); ?>" class="regular-text" />
				</td>
			</tr>
		</table>
	</div>

	<!-- ===================================================================
	     D3 — Field Mapping
	     =================================================================== -->
	<div class="re-section">
		<h2><?php esc_html_e( 'D3 — Field Mapping', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Map each imoti.net XML field to a WordPress source. BrokerID/Name/Phone/Email come from D2 above.', 're-exporter' ); ?>
		</p>

		<table class="widefat re-field-map-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'imoti.net XML field', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'WordPress source', 're-exporter' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			$recommended_fields = array( 'OfferType', 'Price', 'Description', 'Images' );
			foreach ( \RE_Exporter\Exporter_Imoti::$field_labels as $field => $label ) :
				$saved_src  = isset( $imoti_field_map[ $field ] ) ? $imoti_field_map[ $field ] : '__skip__';
				$is_rec     = in_array( $field, $recommended_fields, true );
				$has_values = in_array( $field, \RE_Exporter\Exporter_Imoti::$mapped_fields, true ) || 'Extras' === $field;
			?>
			<tr class="<?php echo $is_rec ? 're-required-row' : ''; ?>">
				<td>
					<code><?php echo esc_html( $field ); ?></code>
					<?php if ( $is_rec ) : ?>
						<span class="re-badge re-badge-required"><?php esc_html_e( 'recommended', 're-exporter' ); ?></span>
					<?php endif; ?>
					<?php if ( $has_values ) : ?>
						<span class="re-badge" style="background:#e8f0fe;color:#1a73e8;">
							<?php esc_html_e( 'values', 're-exporter' ); ?>
						</span>
					<?php endif; ?>
					<br /><span style="color:#646970;font-size:12px;"><?php echo esc_html( $label ); ?></span>
				</td>
				<td>
					<select name="imoti_field_map[<?php echo esc_attr( $field ); ?>]" class="re-source-select">
						<option value="__skip__"<?php selected( $saved_src, '__skip__' ); ?>>
							<?php esc_html_e( '— Skip —', 're-exporter' ); ?>
						</option>
						<?php echo re_exporter_source_options( $source_fields, $saved_src, 'imoti' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</select>
					<?php if ( $is_rec && '__skip__' === $saved_src ) : ?>
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
	     D4 — Value Overrides (for numeric-code fields)
	     =================================================================== -->
	<?php
	$has_value_overrides = false;
	foreach ( \RE_Exporter\Exporter_Imoti::$mapped_fields as $vf ) {
		$src = isset( $imoti_field_map[ $vf ] ) ? $imoti_field_map[ $vf ] : '__skip__';
		if ( '__skip__' !== $src && '' !== $src ) {
			$has_value_overrides = true;
			break;
		}
	}
	// Also check Extras.
	$extras_src = isset( $imoti_field_map['Extras'] ) ? $imoti_field_map['Extras'] : '__skip__';
	if ( '__skip__' !== $extras_src && '' !== $extras_src ) {
		$has_value_overrides = true;
	}
	?>
	<?php if ( $has_value_overrides ) : ?>
	<div class="re-section">
		<h2><?php esc_html_e( 'D4 — Value Overrides', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Map WordPress field values to imoti.net numeric codes for fields with a controlled vocabulary.', 're-exporter' ); ?>
		</p>

		<?php
		$value_fields = array_merge( \RE_Exporter\Exporter_Imoti::$mapped_fields, array( 'Extras' ) );
		foreach ( $value_fields as $vf ) :
			$src = isset( $imoti_field_map[ $vf ] ) ? $imoti_field_map[ $vf ] : '__skip__';
			if ( '__skip__' === $src || '' === $src ) {
				continue;
			}
			$source_values = $this->get_source_values_for( $src, $source_fields, $post_type );
			if ( empty( $source_values ) ) {
				continue;
			}
			$saved_vmap = isset( $imoti_value_map[ $vf ] ) ? (array) $imoti_value_map[ $vf ] : array();
		?>
		<h3 style="margin-top:20px;"><?php echo esc_html( $vf ); ?> <small style="color:#646970;">(← <?php echo esc_html( $src ); ?>)</small></h3>
		<table class="widefat re-value-map-table" style="max-width:600px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'WordPress value', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'imoti.net code', 're-exporter' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			$vf_lookup        = \RE_Exporter\Imoti_Lookups::get( $vf );
			$vf_is_dropdown   = in_array( $vf, \RE_Exporter\Imoti_Lookups::dropdown_fields(), true );
			$vf_is_extras     = ( 'Extras' === $vf );
			$vf_is_location   = in_array( $vf, \RE_Exporter\Imoti_Lookups::text_fields(), true );
			$vf_label_map     = isset( $imoti_location_label_map[ $vf ] ) ? (array) $imoti_location_label_map[ $vf ] : array();
			foreach ( $source_values as $sv ) :
				$saved_val  = isset( $saved_vmap[ $sv['value'] ] ) ? $saved_vmap[ $sv['value'] ] : '';
				$input_name = 'imoti_value_map[' . esc_attr( $vf ) . '][' . esc_attr( $sv['value'] ) . ']';
			?>
				<tr>
					<td><?php echo esc_html( $sv['label'] ); ?></td>
					<td>
					<?php if ( $vf_is_extras ) :
						$selected_ids = array_filter( array_map( 'trim', explode( ',', $saved_val ) ) );
					?>
						<select name="<?php echo esc_attr( $input_name ); ?>[]" multiple style="min-height:80px;min-width:240px;">
							<?php foreach ( \RE_Exporter\Imoti_Lookups::$extras as $code => $label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>"<?php echo in_array( (string) $code, $selected_ids, true ) ? ' selected' : ''; ?>>
								<?php echo esc_html( $code . ' — ' . $label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					<?php elseif ( $vf_is_dropdown ) : ?>
						<select name="<?php echo esc_attr( $input_name ); ?>">
							<option value=""><?php esc_html_e( '— Keep original —', 're-exporter' ); ?></option>
							<?php foreach ( $vf_lookup as $code => $label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>"<?php selected( $saved_val, (string) $code ); ?>>
								<?php echo esc_html( $code . ' — ' . $label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					<?php elseif ( $vf_is_location ) :
						$saved_label = isset( $vf_label_map[ $sv['value'] ] ) ? (string) $vf_label_map[ $sv['value'] ] : '';
					?>
						<div class="re-city-widget"
							data-empty="<?php esc_attr_e( '— Keep original —', 're-exporter' ); ?>"
							data-imoti-field="<?php echo esc_attr( $vf ); ?>"
						>
							<input type="hidden"
								name="<?php echo esc_attr( $input_name ); ?>"
								class="re-city-val"
								value="<?php echo esc_attr( $saved_val ); ?>"
							/>
							<input type="hidden"
								name="imoti_location_label_map[<?php echo esc_attr( $vf ); ?>][<?php echo esc_attr( $sv['value'] ); ?>]"
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
									placeholder="<?php esc_attr_e( 'Type to search…', 're-exporter' ); ?>"
									autocomplete="off"
								/>
								<div class="re-city-results"></div>
							</div>
						</div>
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
	     D5 — Default values per category
	     =================================================================== -->
	<div class="re-section">
		<h2><?php esc_html_e( 'D5 — Default values per category', 're-exporter' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'For each taxonomy term, set default imoti.net codes. These are used when a post has no per-post override (set on the post edit page → imoti.net tab).', 're-exporter' ); ?>
		</p>

		<!-- Category taxonomy selector -->
		<table class="form-table" role="presentation" style="max-width:600px;">
			<tr>
				<th scope="row">
					<label for="imoti_category_tax"><?php esc_html_e( 'Category taxonomy', 're-exporter' ); ?></label>
				</th>
				<td>
					<select id="imoti_category_tax" name="imoti_category_tax">
						<option value=""><?php esc_html_e( '— None —', 're-exporter' ); ?></option>
						<?php foreach ( $taxonomies as $tax ) : ?>
							<option value="<?php echo esc_attr( $tax->name ); ?>"
								<?php selected( $imoti_category_tax, $tax->name ); ?>>
								<?php echo esc_html( $tax->label . ' (' . $tax->name . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Select a taxonomy to map its terms to default imoti.net codes.', 're-exporter' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php if ( $imoti_category_tax && ! empty( $tax_terms[ $imoti_category_tax ] ) ) :
			$terms_for_tax = $tax_terms[ $imoti_category_tax ];

		?>
		<div style="margin-top:20px;">
		<?php foreach ( $terms_for_tax as $term ) :
			$term_slug = $term['slug'];
			$term_name = $term['name'];
			$saved_defaults = isset( $imoti_category_defaults[ $term_slug ] )
				? (array) $imoti_category_defaults[ $term_slug ]
				: array();
			$saved_estate_type = isset( $saved_defaults['EstateType'] ) ? (string) $saved_defaults['EstateType'] : '';
		?>
		<div class="re-section" style="border:1px solid #dcdcde;border-radius:4px;padding:14px 18px;margin-bottom:14px;">
			<h3 style="margin-top:0;"><?php echo esc_html( $term_name ); ?> <small style="color:#646970;font-weight:400;">(<?php echo esc_html( $term_slug ); ?>)</small></h3>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row" style="width:200px;">
						<label><?php esc_html_e( 'Тип недвижимости', 're-exporter' ); ?></label>
					</th>
					<td>
						<select name="imoti_category_defaults[<?php echo esc_attr( $term_slug ); ?>][EstateType]"
							style="max-width:300px;">
							<option value=""><?php esc_html_e( '— Не задан —', 're-exporter' ); ?></option>
							<?php foreach ( \RE_Exporter\Imoti_Lookups::get( 'EstateType' ) as $id => $name ) : ?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $saved_estate_type, (string) $id ); ?>>
									<?php echo esc_html( $id . ' — ' . $name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		</div>
		<?php endforeach; ?>
		</div>
		<?php elseif ( ! $imoti_category_tax ) : ?>
			<p class="description" style="margin-top:12px;">
				<?php esc_html_e( 'Select a taxonomy above and save to configure per-category defaults.', 're-exporter' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="re-action-bar">
		<?php submit_button( __( 'Save imoti Settings', 're-exporter' ), 'primary', 'submit', false ); ?>
	</div>

</form>
