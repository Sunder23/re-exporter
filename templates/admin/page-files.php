<?php
/**
 * Files tab — browse and delete saved export runs.
 *
 * Available via Admin_Page::render_page() scope:
 *   $this  Admin_Page
 *
 * @package RE_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$runs = $this->get_export_runs();

$platform_labels = array(
	'olx'   => 'OLX.bg',
	'alo'   => 'ALO.bg',
	'imoti' => 'imoti.net',
);

$total_files = 0;
$total_bytes = 0;
foreach ( $runs as $platform_runs ) {
	foreach ( $platform_runs as $run ) {
		$total_files += count( $run['files'] );
		$total_bytes += $run['total_size'];
	}
}

/**
 * Format bytes to human-readable string.
 *
 * @param int $bytes
 * @return string
 */
function re_format_bytes( $bytes ) {
	if ( $bytes >= 1048576 ) {
		return round( $bytes / 1048576, 2 ) . ' MB';
	}
	if ( $bytes >= 1024 ) {
		return round( $bytes / 1024, 1 ) . ' KB';
	}
	return $bytes . ' B';
}
?>

<style>
.re-files-wrap { max-width: 900px; margin-top: 20px; }
.re-files-platform { margin-bottom: 36px; }
.re-files-platform h2 { font-size: 1.2em; border-bottom: 2px solid #2271b1; padding-bottom: 6px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; }
.re-files-platform h2 span { font-size: 13px; font-weight: 400; color: #646970; }
.re-files-empty { color: #646970; font-style: italic; padding: 8px 0; }
.re-files-table { border-collapse: collapse; width: 100%; }
.re-files-table th { background: #f6f7f7; text-align: left; padding: 8px 12px; font-size: 13px; border-bottom: 1px solid #dcdcde; }
.re-files-table td { padding: 9px 12px; vertical-align: top; border-bottom: 1px solid #f0f0f1; font-size: 13px; }
.re-files-table tr:last-child td { border-bottom: none; }
.re-files-table tr:hover td { background: #f9f9f9; }
.re-files-list { margin: 0; padding: 0; list-style: none; }
.re-files-list li { margin-bottom: 3px; }
.re-files-list li a { text-decoration: none; color: #2271b1; }
.re-files-list li a:hover { text-decoration: underline; }
.re-files-size-badge { display: inline-block; color: #646970; font-size: 12px; margin-left: 4px; }
.re-files-delete-btn { background: none; border: 1px solid #d63638; color: #d63638; padding: 3px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; white-space: nowrap; }
.re-files-delete-btn:hover { background: #d63638; color: #fff; }
.re-files-delete-all { border: 1px solid #d63638 !important; color: #d63638 !important; font-size: 12px !important; padding: 4px 12px !important; height: auto !important; }
.re-files-delete-all:hover { background: #d63638 !important; color: #fff !important; }
.re-files-summary { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 10px 18px; margin-bottom: 24px; font-size: 13px; display: flex; gap: 24px; }
.re-files-summary strong { color: #1d2327; }
</style>

<div class="re-files-wrap">

	<!-- ── Summary bar ──────────────────────────────────────────────────── -->
	<div class="re-files-summary">
		<span><?php esc_html_e( 'Total files:', 're-exporter' ); ?> <strong><?php echo esc_html( $total_files ); ?></strong></span>
		<span><?php esc_html_e( 'Disk usage:', 're-exporter' ); ?> <strong><?php echo esc_html( re_format_bytes( $total_bytes ) ); ?></strong></span>
	</div>

	<?php foreach ( array( 'olx', 'alo') as $platform ) :
		$platform_runs = $runs[ $platform ];
		$label         = $platform_labels[ $platform ];
	?>
	<div class="re-files-platform">

		<h2>
			<?php echo esc_html( $label ); ?>
			<?php if ( $platform_runs ) : ?>
				<span>
					<?php
					$count = count( $platform_runs );
					/* translators: %d = number of runs */
					echo esc_html( sprintf( _n( '%d run', '%d runs', $count, 're-exporter' ), $count ) );
					?>
				</span>
			<?php endif; ?>
			<?php if ( $platform_runs ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=re-exporter' ) ); ?>"
					style="margin:0;"
					onsubmit="return confirm('<?php echo esc_js( sprintf(
						/* translators: %s = platform label */
						__( 'Delete all %s export files? This cannot be undone.', 're-exporter' ),
						$label
					) ); ?>')">
					<?php wp_nonce_field( 're_exporter_delete_all' ); ?>
					<input type="hidden" name="re_action" value="delete_all" />
					<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>" />
					<button type="submit" class="button re-files-delete-all">
						&#128465; <?php echo esc_html( sprintf(
							/* translators: %s = platform label */
							__( 'Delete all %s', 're-exporter' ),
							$label
						) ); ?>
					</button>
				</form>
			<?php endif; ?>
		</h2>

		<?php if ( ! $platform_runs ) : ?>
			<p class="re-files-empty"><?php esc_html_e( 'No export files found.', 're-exporter' ); ?></p>
		<?php else : ?>

		<table class="re-files-table widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Files', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Size', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Actions', 're-exporter' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $platform_runs as $run ) : ?>
				<tr>
					<td style="white-space:nowrap;"><?php echo esc_html( $run['date'] ); ?></td>
					<td>
						<ul class="re-files-list">
						<?php foreach ( $run['files'] as $file ) : ?>
							<li>
								<a href="<?php echo esc_url( $file['url'] ); ?>" download>
									<?php echo esc_html( $file['name'] ); ?>
								</a>
								<span class="re-files-size-badge">(<?php echo esc_html( re_format_bytes( $file['size'] ) ); ?>)</span>
							</li>
						<?php endforeach; ?>
						<?php if ( ! $run['files'] ) : ?>
							<li><span style="color:#646970;">—</span></li>
						<?php endif; ?>
						</ul>
					</td>
					<td style="white-space:nowrap;"><?php echo esc_html( re_format_bytes( $run['total_size'] ) ); ?></td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=re-exporter' ) ); ?>"
							onsubmit="return confirm('<?php echo esc_js( __( 'Delete this export run?', 're-exporter' ) ); ?>')">
							<?php wp_nonce_field( 're_exporter_delete_run' ); ?>
							<input type="hidden" name="re_action" value="delete_run" />
							<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>" />
							<input type="hidden" name="run" value="<?php echo esc_attr( $run['run'] ); ?>" />
							<button type="submit" class="re-files-delete-btn">
								&#128465; <?php esc_html_e( 'Delete', 're-exporter' ); ?>
							</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php endif; ?>
	</div>
	<?php endforeach; ?>

</div><!-- .re-files-wrap -->
