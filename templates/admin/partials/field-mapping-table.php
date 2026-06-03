<?php
/**
 * Partial: B1 OLX Field Mapping Table.
 *
 * Required variables (set by the including template):
 *   $d['all_csv_columns']  string[]  All unique OLX CSV columns.
 *   $d['source_fields']    array     From Field_Scanner::get_field_definitions().
 *   $d['olx_field_map']    array     Saved [ csv_column => source_key ].
 *   $this                  Admin_Page instance (for re_exporter_source_options()).
 *
 * @package RE_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Columns that are required in at least one OLX subcategory template.
// We collect them here for visual emphasis only (not hard validation).
$always_required = array( 'id', 'title', 'description', 'price_value', 'price_currency', 'location_city', 'images' );
?>
<table class="widefat re-field-map-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'OLX CSV column', 're-exporter' ); ?></th>
			<th><?php esc_html_e( 'WordPress source', 're-exporter' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $d['all_csv_columns'] as $col ) :
		$saved_src = isset( $d['olx_field_map'][ $col ] ) ? $d['olx_field_map'][ $col ] : '__skip__';
		$is_common = in_array( $col, $always_required, true );
		?>
		<tr class="<?php echo $is_common ? 're-required-row' : ''; ?>">
			<td>
				<code><?php echo esc_html( $col ); ?></code>
				<?php if ( $is_common ) : ?>
					<span class="re-badge re-badge-required"><?php esc_html_e( 'common', 're-exporter' ); ?></span>
				<?php endif; ?>
				<?php if ( $this->olx_template->has_json_for_column( $col ) ) : ?>
					<span class="re-badge" style="background:#e8f0fe;color:#1a73e8;">
						<?php esc_html_e( 'values', 're-exporter' ); ?>
					</span>
				<?php endif; ?>
			</td>
			<td>
				<select
					name="olx_field_map[<?php echo esc_attr( $col ); ?>]"
					class="re-source-select"
				>
					<option value="__skip__"<?php selected( $saved_src, '__skip__' ); ?>>
						<?php esc_html_e( '— Skip —', 're-exporter' ); ?>
					</option>
					<?php echo re_exporter_source_options( $d['source_fields'], $saved_src, 'olx' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
