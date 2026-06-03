<?php
/**
 * Plugin Name:  Real Estate Exporter
 * Plugin URI:   https://freedomvarna.com
 * Description:  Export property listings to OLX.bg (CSV) and ALO.bg (JSON).
 * Version:      2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:       FreedomVarna
 * Text Domain:  re-exporter
 * Domain Path:  /languages
 * License:      GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RE_EXPORTER_VERSION',       '2.0.0' );
define( 'RE_EXPORTER_DIR',           plugin_dir_path( __FILE__ ) );
define( 'RE_EXPORTER_URL',           plugin_dir_url( __FILE__ ) );
define( 'RE_EXPORTER_BASENAME',      plugin_basename( __FILE__ ) );
// Absolute path to the client-provided OLX templates (READ ONLY).
define( 'RE_EXPORTER_OLX_TEMPLATES', RE_EXPORTER_DIR . 'templates/olx/' );
// Absolute path to the ALO templates (READ ONLY).
define( 'RE_EXPORTER_ALO_TEMPLATES', RE_EXPORTER_DIR . 'templates/alo_bg/' );
// Absolute path to the Realistimo templates (READ ONLY).
define( 'RE_EXPORTER_REALISTIMO_TEMPLATES', RE_EXPORTER_DIR . 'templates/realistimo/' );

// ── Core utilities (no inter-dependencies) ───────────────────────────────────
require_once RE_EXPORTER_DIR . 'includes/class-re-export-logger.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-imoti-lookups.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-olx-template.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-alo-template.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-field-scanner.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-field-resolver.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-settings.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-export-request.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-export-file-result.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-export-result.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-platform-definition.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-platform-handler-interface.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-platform-handler-olx.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-platform-handler-alo.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-platform-handler-imoti.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-platform-handler-realistimo.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-platform-registry.php';

// ── Feature classes (depend on core utilities) ────────────────────────────────
require_once RE_EXPORTER_DIR . 'includes/class-re-gallery-field.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-metaboxes.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-exporter-olx.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-exporter-alo.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-exporter-imoti.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-realistimo-geo.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-exporter-realistimo.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-export-wizard.php';
require_once RE_EXPORTER_DIR . 'includes/class-re-admin-page.php';

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 're_exporter_activate' );

/**
 * Create DB table and ensure the upload output directory exists.
 */
function re_exporter_activate() {
	RE_Exporter\Export_Logger::create_table();

	$upload = wp_upload_dir();
	$base   = trailingslashit( $upload['basedir'] ) . 're-exporter';

	foreach ( array( $base, $base . '/olx', $base . '/alo', $base . '/imoti', $base . '/realistimo' ) as $dir ) {
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
	}
}

// ── Deactivation ─────────────────────────────────────────────────────────────

register_deactivation_hook( __FILE__, '__return_false' );

// ── Bootstrap ─────────────────────────────────────────────────────────────────

/**
 * Instantiate all feature singletons after all plugins are loaded
 * so that ACF (and other dependencies) are available.
 */
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 're-exporter', false, dirname( RE_EXPORTER_BASENAME ) . '/languages' );

	$settings     = new RE_Exporter\Settings();
	$olx_template = new RE_Exporter\OLX_Template();
	$alo_template = new RE_Exporter\ALO_Template();
	$scanner      = new RE_Exporter\Field_Scanner();
	$platforms    = new RE_Exporter\Platform_Registry( array(
		new RE_Exporter\Platform_Handler_OLX( $settings, $olx_template ),
		new RE_Exporter\Platform_Handler_ALO( $settings, $alo_template ),
		new RE_Exporter\Platform_Handler_Imoti( $settings ),
		new RE_Exporter\Platform_Handler_Realistimo( $settings ),
	) );

	// Gallery field (media repeater, source key __re_gallery__).
	$gallery = new RE_Exporter\Gallery_Field( $settings );

	// Metaboxes run on the front- and back-end edit screens.
	new RE_Exporter\Metaboxes( $settings, $olx_template, $alo_template, $gallery );

	// Admin page (includes Export_Wizard AJAX hooks) — admin-only.
	if ( is_admin() ) {
		new RE_Exporter\Admin_Page( $settings, $olx_template, $alo_template, $scanner, $platforms );
	}
} );
