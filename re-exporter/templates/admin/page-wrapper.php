<?php
/**
 * Top-level page wrapper — renders the tab bar and dispatches to the
 * correct tab template.
 *
 * Available variables (set by Admin_Page::render_page()):
 *   $active_tab   string  'settings' | 'export' | 'instructions' | 'files'
 *   $active_sub   string  'olx' | 'alo'
 *   $post_type    string  Currently configured CPT slug
 *   $cpt_list     array   From Field_Scanner::get_post_types()
 *   $saved        string  Flash-message key ('global'|'olx'|'alo'|'')
 *   $deleted      string  Flash-message key ('1'|'all'|'')
 *
 * @package RE_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap re-exporter-wrap">

	<h1><?php esc_html_e( 'Real Estate Exporter', 're-exporter' ); ?></h1>

	<!-- ── Saved notices ─────────────────────────────────────────────────── -->
	<?php if ( $saved ) : ?>
	<div class="notice notice-success is-dismissible">
		<p>
		<?php
		switch ( $saved ) {
			case 'global':
				esc_html_e( 'Global settings saved.', 're-exporter' );
				break;
			case 'olx':
				esc_html_e( 'OLX settings saved.', 're-exporter' );
				break;
			case 'alo':
				esc_html_e( 'ALO settings saved.', 're-exporter' );
				break;
			case 'imoti':
				esc_html_e( 'imoti.net settings saved.', 're-exporter' );
				break;
			case 'realistimo':
				esc_html_e( 'Realistimo settings saved.', 're-exporter' );
				break;
		}
		?>
		</p>
	</div>
	<?php endif; ?>

	<!-- ── Deleted notice ────────────────────────────────────────────────── -->
	<?php if ( $deleted ) : ?>
	<div class="notice notice-success is-dismissible">
		<p>
		<?php
		if ( 'all' === $deleted ) {
			esc_html_e( 'All export files deleted.', 're-exporter' );
		} else {
			esc_html_e( 'Export run deleted.', 're-exporter' );
		}
		?>
		</p>
	</div>
	<?php endif; ?>

	<div id="re-notice-area" style="display:none;" role="alert"></div>

	<!-- ── Main tab navigation ───────────────────────────────────────────── -->
	<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Main tabs', 're-exporter' ); ?>">
		<a
			href="<?php echo esc_url( $this->settings_url() ); ?>"
			class="nav-tab<?php echo 'settings' === $active_tab ? ' nav-tab-active' : ''; ?>"
		>
			&#9881; <?php esc_html_e( 'Settings', 're-exporter' ); ?>
		</a>
		<a
			href="<?php echo esc_url( $this->export_url() ); ?>"
			class="nav-tab<?php echo 'export' === $active_tab ? ' nav-tab-active' : ''; ?>"
		>
			&#9654; <?php esc_html_e( 'Export', 're-exporter' ); ?>
		</a>
		<a
			href="<?php echo esc_url( $this->instructions_url() ); ?>"
			class="nav-tab<?php echo 'instructions' === $active_tab ? ' nav-tab-active' : ''; ?>"
		>
			&#9432; <?php esc_html_e( 'Инструкция', 're-exporter' ); ?>
		</a>
		<a
			href="<?php echo esc_url( $this->files_url() ); ?>"
			class="nav-tab<?php echo 'files' === $active_tab ? ' nav-tab-active' : ''; ?>"
		>
			&#128193; <?php esc_html_e( 'Файлы', 're-exporter' ); ?>
		</a>
	</nav>

	<!-- ── Tab content ───────────────────────────────────────────────────── -->
	<?php if ( 'settings' === $active_tab ) : ?>
		<?php include RE_EXPORTER_DIR . 'templates/admin/page-settings.php'; ?>
	<?php elseif ( 'instructions' === $active_tab ) : ?>
		<?php include RE_EXPORTER_DIR . 'templates/admin/page-instructions.php'; ?>
	<?php elseif ( 'files' === $active_tab ) : ?>
		<?php include RE_EXPORTER_DIR . 'templates/admin/page-files.php'; ?>
	<?php else : ?>
		<?php $this->wizard->render(); ?>
	<?php endif; ?>

</div><!-- .wrap -->

<!-- Spinner (outside .wrap to cover full viewport) -->
<div id="re-spinner-overlay" role="status" aria-label="<?php esc_attr_e( 'Loading', 're-exporter' ); ?>">
	<div class="re-spinner-inner">
		<span class="re-spinner-icon" aria-hidden="true"></span>
		<div class="re-spinner-msg"><?php esc_html_e( 'Please wait…', 're-exporter' ); ?></div>
	</div>
</div>
