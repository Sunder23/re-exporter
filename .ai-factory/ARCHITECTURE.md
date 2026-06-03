# Architecture: Modular Monolith

## Overview
This project should follow a modular monolith architecture. It is a single WordPress plugin deployed as one unit, but it already contains several distinct subdomains: admin configuration, export orchestration, platform-specific exporters, template lookup, field resolution, and logging.

Modular monolith fits this codebase better than layered-only guidance because the main maintenance risk is not deployment scale, but uncontrolled coupling between marketplace-specific behavior and shared plugin infrastructure. Strong module boundaries inside one plugin keep day-to-day development simple while preserving room for new export targets.

## Decision Rationale
- **Project type:** WordPress plugin for real estate listing export
- **Tech stack:** PHP 7.4+, WordPress admin and options APIs, MySQL, JavaScript/CSS assets
- **Key factor:** Multiple marketplace integrations need to evolve independently while still sharing common plugin infrastructure

## Folder Structure
```text
re-exporter.php
uninstall.php
assets/
  css/
  js/
includes/
  class-re-settings.php
  class-re-admin-page.php
  class-re-export-wizard.php
  class-re-export-logger.php
  class-re-field-scanner.php
  class-re-field-resolver.php
  class-re-gallery-field.php
  class-re-metaboxes.php
  class-re-olx-template.php
  class-re-alo-template.php
  class-re-imoti-lookups.php
  class-re-realistimo-geo.php
  class-re-exporter-olx.php
  class-re-exporter-alo.php
  class-re-exporter-imoti.php
  class-re-exporter-realistimo.php
templates/
  admin/
    partials/
  olx/
  alo_bg/
  realistimo/
languages/
```

## Dependency Rules
Shared infrastructure stays inward-facing, while marketplace modules depend on shared services but not on each other.

- `re-exporter.php` may wire modules together, but should not absorb business logic.
- `Settings`, template helpers, field helpers, and logging form the shared infrastructure layer.
- Admin UI classes may depend on settings, scanners, templates, and export orchestration.
- Marketplace exporters may depend on shared helpers and their own template/lookup data.
- Template files may render data prepared by classes, but should not become the source of business decisions.

- ✅ `Admin_Page` -> `Settings`, `Field_Scanner`, `Export_Wizard`
- ✅ `Export_Wizard` -> `Settings`, marketplace template helpers, exporter classes
- ✅ `Exporter_*` -> `Settings`, `Field_Resolver`, platform-specific lookup/template helpers
- ✅ `Export_Logger` -> WordPress database APIs
- ❌ `Settings` -> `Admin_Page` or template files
- ❌ One marketplace exporter -> another marketplace exporter
- ❌ Template partials -> direct persistence or query logic

## Layer/Module Communication
- Bootstrap composition happens in `re-exporter.php` on `plugins_loaded`.
- Admin requests flow through `Admin_Page`, then into `Settings` or `Export_Wizard`.
- AJAX handlers validate the request boundary first, then call shared services and exporter logic.
- Exporters should receive normalized inputs from settings and field-resolution helpers rather than reading raw `$_POST` data.
- New marketplace support should be added as a self-contained module: settings keys, template helper or lookup helper, exporter, and admin/tab integration.

## Key Principles
1. Keep platform-specific rules isolated. OLX, ALO, Imoti, and Realistimo logic should live in dedicated modules, not in shared generic helpers unless the behavior is truly common.
2. Keep WordPress boundary code thin. Nonces, capabilities, hooks, and request parsing belong at the edge; mapping and export logic belong in classes.
3. Treat templates and lookup bundles as data assets. Classes decide behavior; template files and JSON/CSV bundles provide the shape of exported data.

## Code Examples

### Bootstrap Wires Shared Services Into Admin Features
```php
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 're-exporter', false, dirname( RE_EXPORTER_BASENAME ) . '/languages' );

	$settings     = new RE_Exporter\Settings();
	$olx_template = new RE_Exporter\OLX_Template();
	$alo_template = new RE_Exporter\ALO_Template();
	$scanner      = new RE_Exporter\Field_Scanner();

	new RE_Exporter\Admin_Page( $settings, $olx_template, $alo_template, $scanner );
} );
```

### Marketplace Modules Depend On Shared Infrastructure, Not On Each Other
```php
namespace RE_Exporter;

class Exporter_OLX {
	private $settings;
	private $resolver;
	private $template;

	public function __construct( Settings $settings, Field_Resolver $resolver, OLX_Template $template ) {
		$this->settings = $settings;
		$this->resolver = $resolver;
		$this->template = $template;
	}
}
```

## Anti-Patterns
- ❌ Adding marketplace-specific `if ( 'olx' === $platform )` branches across unrelated shared classes when the logic belongs in a dedicated exporter module
- ❌ Reading raw request globals deep inside exporter or template helper code instead of validating inputs at the admin or AJAX boundary
- ❌ Moving persistence, queries, or filesystem mutations into template files
- ❌ Duplicating settings storage logic outside `Settings`
