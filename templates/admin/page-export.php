<?php
/**
 * Export tab — 3-step wizard skeleton.
 *
 * All dynamic content (review summary, download links)
 * is loaded via AJAX by re-admin.js.
 *
 * Available variables:
 *   $post_type  string           Configured CPT slug (may be empty).
 *   $cpt        WP_Post_Type|null
 *
 * @package RE_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php if ( ! $post_type ) : ?>
<div class="notice notice-warning inline">
	<p>
		<?php esc_html_e( 'No post type is configured for export.', 're-exporter' ); ?>
		<a href="<?php echo esc_url( $this->settings_url() ); ?>">
			<?php esc_html_e( '⚙ Configure in Settings', 're-exporter' ); ?>
		</a>
	</p>
</div>
<?php return; endif; ?>

<!-- ── Progress bar ────────────────────────────────────────────────────── -->
<ul class="re-progress-bar" aria-label="<?php esc_attr_e( 'Export steps', 're-exporter' ); ?>">
	<?php
	$steps = array(
		1 => __( 'Platform', 're-exporter' ),
		2 => __( 'Review',   're-exporter' ),
		3 => __( 'Done',     're-exporter' ),
	);
	foreach ( $steps as $n => $label ) :
		?>
	<li class="re-progress-step" id="re-progress-<?php echo esc_attr( $n ); ?>">
		<span class="step-num" aria-hidden="true"><?php echo esc_html( $n ); ?></span>
		<?php echo esc_html( $label ); ?>
	</li>
	<?php endforeach; ?>
</ul>

<!-- ===================================================================
     Step 1 — Select Platform
     =================================================================== -->
<div class="re-exp-step" id="re-exp-step-1" aria-hidden="true">

	<h2>
		<?php esc_html_e( 'Step 1 — Select Platform', 're-exporter' ); ?>
		<span style="font-size:13px;font-weight:400;color:#666;margin-left:8px;">
			<?php echo esc_html( $cpt ? $cpt->label : $post_type ); ?>
			&mdash;
			<a href="<?php echo esc_url( $this->settings_url() ); ?>">
				<?php esc_html_e( '⚙ Change in Settings', 're-exporter' ); ?>
			</a>
		</span>
	</h2>

	<div class="re-platform-options" role="radiogroup"
		aria-label="<?php esc_attr_e( 'Export platform', 're-exporter' ); ?>">

		<div class="re-platform-card">
			<input type="radio" name="re_exp_platform" value="olx" id="exp-platform-olx" />
			<label for="exp-platform-olx">OLX.bg</label>
			<p><?php esc_html_e( 'CSV files per category — semicolon-delimited, UTF-8 with BOM. Images joined with pipe (|).', 're-exporter' ); ?></p>
		</div>

		<div class="re-platform-card">
			<input type="radio" name="re_exp_platform" value="alo" id="exp-platform-alo" />
			<label for="exp-platform-alo">ALO.bg</label>
			<p><?php esc_html_e( 'Single JSON file — UTF-8, pretty-printed. Nested map / contacts objects. Images as array.', 're-exporter' ); ?></p>
		</div>

		<div class="re-platform-card">
			<input type="radio" name="re_exp_platform" value="imoti" id="exp-platform-imoti" />
			<label for="exp-platform-imoti">imoti.net</label>
			<p><?php esc_html_e( 'Single XML feed (feed.xml) — UTF-8. One <Item> per property. Images, Extras, CDATA description.', 're-exporter' ); ?></p>
		</div>

		<div class="re-platform-card">
			<input type="radio" name="re_exp_platform" value="realistimo" id="exp-platform-realistimo" />
			<label for="exp-platform-realistimo">Realistimo</label>
			<p><?php esc_html_e( 'Single XML feed (feed.xml) — UTF-8. One <offer> per property. Geo location, enum fields, multi-value heating/parking/exterior.', 're-exporter' ); ?></p>
		</div>

	</div>

	<div class="re-action-bar">
		<button class="button button-primary" id="re-exp-step1-next">
			<?php esc_html_e( 'Next →', 're-exporter' ); ?>
		</button>
	</div>

</div><!-- /step-1 -->

<!-- ===================================================================
     Step 2 — Review & Confirm
     =================================================================== -->
<div class="re-exp-step" id="re-exp-step-2" aria-hidden="true">

	<h2><?php esc_html_e( 'Step 2 — Review & Confirm', 're-exporter' ); ?></h2>

	<dl class="re-review-summary">
		<dt><?php esc_html_e( 'Records', 're-exporter' ); ?></dt>
		<dd id="re-review-count">&mdash;</dd>
		<dt><?php esc_html_e( 'Platform', 're-exporter' ); ?></dt>
		<dd id="re-review-platform">&mdash;</dd>
	</dl>

	<div id="re-exp-review-wrap"></div>

	<div class="re-action-bar">
		<button class="button" id="re-exp-step2-back">
			&#8592; <?php esc_html_e( 'Back', 're-exporter' ); ?>
		</button>
		<button class="button button-primary" id="re-exp-btn-export" style="font-size:14px;height:auto;padding:7px 20px;">
			&#11015; <?php esc_html_e( 'Export', 're-exporter' ); ?>
		</button>
	</div>

</div><!-- /step-2 -->

<!-- ===================================================================
     Step 3 — Done
     =================================================================== -->
<div class="re-exp-step" id="re-exp-step-3" aria-hidden="true">

	<h2><?php esc_html_e( 'Step 3 — Download', 're-exporter' ); ?></h2>

	<div id="re-exp-result" aria-live="polite"></div>

	<div class="re-action-bar">
		<button class="button" id="re-exp-step3-back">
			&#8592; <?php esc_html_e( 'Back', 're-exporter' ); ?>
		</button>
		<button class="button button-primary" id="re-exp-btn-new" style="display:none;">
			&#128260; <?php esc_html_e( 'New Export', 're-exporter' ); ?>
		</button>
	</div>

</div><!-- /step-3 -->
