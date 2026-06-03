<?php
/**
 * Partial: B3 Category Mapping Table (taxonomy terms → OLX subcategories).
 *
 * Required variables (set by the including template):
 *   $terms        array[]  From Field_Scanner::get_taxonomy_terms():
 *                          [ 'term_id', 'name', 'slug' ]
 *   $saved_map    array    Saved olx_category_map: [ term_slug => subcategory_id ]
 *   $grouped      array[]  From OLX_Template::get_grouped_subcategories()
 *   $base_subcats array[]  From OLX_Template::get_base_subcategories() — base IDs for B5 routing
 *   $deal_field   string   Current B5 deal-type field ('' if not configured)
 *
 * @package RE_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $terms ) ) {
	echo '<p>' . esc_html__( 'No terms found in this taxonomy.', 're-exporter' ) . '</p>';
	return;
}

$base_subcats = isset( $base_subcats ) ? (array) $base_subcats : array();
$deal_field   = isset( $deal_field )   ? (string) $deal_field   : '';
?>
<table class="widefat re-category-map-table" style="margin-top:16px;">
	<thead>
		<tr>
			<th><?php esc_html_e( 'WordPress term', 're-exporter' ); ?></th>
			<th><?php esc_html_e( 'OLX subcategory', 're-exporter' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $terms as $term ) :
		$saved_id = isset( $saved_map[ $term['slug'] ] ) ? $saved_map[ $term['slug'] ] : '';
		?>
		<tr>
			<td>
				<?php echo esc_html( $term['name'] ); ?>
				<code style="font-size:11px;"><?php echo esc_html( $term['slug'] ); ?></code>
			</td>
			<td>
				<select name="olx_category_map[<?php echo esc_attr( $term['slug'] ); ?>]"
					class="re-category-map-table">
					<option value=""><?php esc_html_e( '— Not mapped —', 're-exporter' ); ?></option>
					<?php if ( ! empty( $base_subcats ) ) : ?>
						<optgroup label="<?php esc_attr_e( '— Auto Sale/Rent via B5 —', 're-exporter' ); ?>">
							<?php foreach ( $base_subcats as $base ) : ?>
								<option
									value="<?php echo esc_attr( $base['id'] ); ?>"
									<?php selected( $saved_id, $base['id'] ); ?>
								>
									<?php echo esc_html( $base['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
					<?php foreach ( $grouped as $group ) : ?>
						<optgroup label="<?php echo esc_attr( $group['name'] ); ?>">
							<?php foreach ( $group['subcategories'] as $sub ) : ?>
								<option
									value="<?php echo esc_attr( $sub['id'] ); ?>"
									<?php selected( $saved_id, $sub['id'] ); ?>
								>
									<?php echo esc_html( $sub['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
				<?php if ( $deal_field && $saved_id && substr( $saved_id, -5 ) !== '_sale' && substr( $saved_id, -5 ) !== '_rent' && 'new_construction' !== $saved_id ) : ?>
					<p class="description" style="color:#2271b1;">
						<?php
						printf(
							/* translators: %s = base subcategory ID */
							esc_html__( 'Routes to %s_sale or %s_rent based on B5 deal-type field.', 're-exporter' ),
							esc_html( $saved_id ),
							esc_html( $saved_id )
						);
						?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
