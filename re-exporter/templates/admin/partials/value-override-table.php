<?php
/**
 * Partial: B2 Value Override Table for one column.
 *
 * Required variables (set by the including template):
 *   $col             string   CSV column name (or ALO field key).
 *   $json_values     array[]  OLX allowed values: [ 'label', 'value' ].
 *                             Empty for ALO (free-text target) or location_city (AJAX widget).
 *   $wp_values       array[]  WordPress source values: [ 'label', 'value' ].
 *   $saved_map       array    Saved [ wp_value => olx_value ].
 *   $saved_label_map array    Saved [ wp_value => display_label ] for city widgets.
 *   $platform        string   'olx' | 'alo'  (determines input name prefix).
 *
 * @package RE_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $wp_values ) ) {
	return; // Nothing to show if there are no sampled WP values.
}

$input_prefix       = ( 'alo' === $platform ) ? 'alo_value_map' : 'olx_value_map';
$saved_label_map    = isset( $saved_label_map )    ? (array) $saved_label_map    : array();
$json_optgroups     = isset( $json_optgroups )     ? (array) $json_optgroups     : array();
$alo_location_field = isset( $alo_location_field ) ? (string) $alo_location_field : '';
?>
<div class="re-collapse-block">
	<button type="button" class="re-collapse-toggle" aria-expanded="false">
		<?php echo esc_html( sprintf( __( 'Values for: %s', 're-exporter' ), $col ) ); ?>
		<span class="re-chevron">&#9660;</span>
	</button>
	<div class="re-collapse-body" style="display:none;">
	<table class="widefat re-value-map-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'WordPress value', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'Map to', 're-exporter' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $wp_values as $wv ) :
			$saved_target = isset( $saved_map[ $wv['value'] ] ) ? $saved_map[ $wv['value'] ] : '';
			?>
			<tr>
				<td>
					<?php echo esc_html( $wv['label'] !== $wv['value'] ? $wv['label'] . ' (' . $wv['value'] . ')' : $wv['value'] ); ?>
				</td>
				<td>
				<?php if ( '' !== $alo_location_field ) :
				$saved_label = isset( $saved_label_map[ $wv['value'] ] ) ? (string) $saved_label_map[ $wv['value'] ] : '';
			?>
				<div class="re-city-widget"
					data-empty="<?php esc_attr_e( '— Keep original —', 're-exporter' ); ?>"
					data-alo-field="<?php echo esc_attr( $alo_location_field ); ?>"
				>
					<input type="hidden"
						name="<?php echo esc_attr( $input_prefix ); ?>[<?php echo esc_attr( $col ); ?>][<?php echo esc_attr( $wv['value'] ); ?>]"
						class="re-city-val"
						value="<?php echo esc_attr( $saved_target ); ?>"
					/>
					<input type="hidden"
						name="alo_location_label_map[<?php echo esc_attr( $col ); ?>][<?php echo esc_attr( $wv['value'] ); ?>]"
						class="re-city-label-field"
						value="<?php echo esc_attr( $saved_label ); ?>"
					/>
					<div class="re-city-trigger">
						<span class="re-city-label<?php echo ( $saved_target && $saved_label ) ? '' : ' is-placeholder'; ?>">
							<?php echo ( $saved_target && $saved_label ) ? esc_html( $saved_label ) : esc_html__( '— Keep original —', 're-exporter' ); ?>
						</span>
						<span class="re-city-actions">
							<button type="button" class="re-city-clear-btn"<?php echo $saved_target ? ' style="display:inline-block;"' : ''; ?>>&#x2715;</button>
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
			<?php elseif ( 'location_city' === $col || 'location_district' === $col ) :
					$saved_label = isset( $saved_label_map[ $wv['value'] ] ) ? (string) $saved_label_map[ $wv['value'] ] : '';
				?>
					<div class="re-city-widget"
						data-empty="<?php esc_attr_e( '— Keep original —', 're-exporter' ); ?>"
						<?php if ( 'location_district' === $col ) : ?>data-store="district"<?php endif; ?>
					>
						<input type="hidden"
							name="<?php echo esc_attr( $input_prefix ); ?>[<?php echo esc_attr( $col ); ?>][<?php echo esc_attr( $wv['value'] ); ?>]"
							class="re-city-val"
							value="<?php echo esc_attr( $saved_target ); ?>"
						/>
						<input type="hidden"
							name="olx_city_label_map[<?php echo esc_attr( $col ); ?>][<?php echo esc_attr( $wv['value'] ); ?>]"
							class="re-city-label-field"
							value="<?php echo esc_attr( $saved_label ); ?>"
						/>
						<div class="re-city-trigger">
							<span class="re-city-label<?php echo ( $saved_target && $saved_label ) ? '' : ' is-placeholder'; ?>"
								<?php if ( $saved_target && ! $saved_label ) : ?>data-resolve="<?php echo esc_attr( $saved_target ); ?>"<?php endif; ?>
							>
								<?php echo ( $saved_target && $saved_label ) ? esc_html( $saved_label ) : esc_html__( '— Keep original —', 're-exporter' ); ?>
							</span>
							<span class="re-city-actions">
								<button type="button" class="re-city-clear-btn"<?php echo $saved_target ? ' style="display:inline-block;"' : ''; ?>>&#x2715;</button>
								<span class="re-city-caret"></span>
							</span>
						</div>
						<div class="re-city-panel" style="display:none;">
							<input type="text" class="re-city-search"
								placeholder="<?php esc_attr_e( 'Type to search city…', 're-exporter' ); ?>"
								autocomplete="off"
							/>
							<div class="re-city-results"></div>
						</div>
					</div>
				<?php elseif ( ! empty( $json_optgroups ) ) : ?>
					<select name="<?php echo esc_attr( $input_prefix ); ?>[<?php echo esc_attr( $col ); ?>][<?php echo esc_attr( $wv['value'] ); ?>]" style="min-width:300px;">
						<option value=""><?php esc_html_e( '— Keep original —', 're-exporter' ); ?></option>
						<?php foreach ( $json_optgroups as $grp ) : ?>
						<optgroup label="<?php echo esc_attr( $grp['group'] ); ?>">
							<?php foreach ( $grp['options'] as $jv ) : ?>
							<option value="<?php echo esc_attr( $jv['value'] ); ?>"<?php selected( $saved_target, $jv['value'] ); ?>>
								<?php echo esc_html( $jv['value'] ); ?>
							</option>
							<?php endforeach; ?>
						</optgroup>
						<?php endforeach; ?>
					</select>
			<?php elseif ( ! empty( $json_values ) ) : ?>
					<select name="<?php echo esc_attr( $input_prefix ); ?>[<?php echo esc_attr( $col ); ?>][<?php echo esc_attr( $wv['value'] ); ?>]">
						<option value=""><?php esc_html_e( '— Keep original —', 're-exporter' ); ?></option>
						<?php foreach ( $json_values as $jv ) : ?>
							<option
								value="<?php echo esc_attr( $jv['value'] ); ?>"
								<?php selected( $saved_target, $jv['value'] ); ?>
							>
								<?php echo esc_html( $jv['label'] !== $jv['value'] ? $jv['label'] . ' [' . $jv['value'] . ']' : $jv['value'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input
						type="text"
						name="<?php echo esc_attr( $input_prefix ); ?>[<?php echo esc_attr( $col ); ?>][<?php echo esc_attr( $wv['value'] ); ?>]"
						value="<?php echo esc_attr( $saved_target ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Leave blank to keep original', 're-exporter' ); ?>"
					/>
				<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</div>
