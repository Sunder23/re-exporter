# Project Base Rules

> Auto-detected conventions from codebase analysis. Edit as needed.

## Naming Conventions

- Files: kebab-case with WordPress-style prefixes for root files and `class-re-*.php` for PHP classes
- Variables: snake_case
- Functions: snake_case
- Classes: PascalCase with underscore-separated words inside the `RE_Exporter` namespace
- Constants: UPPER_SNAKE_CASE
- Option keys and meta keys: lower_snake_case with `re_exporter_` or `_re_` prefixes

## Module Structure

- `re-exporter.php` is the bootstrap entry point and dependency wiring layer.
- `includes/` contains PHP classes grouped by responsibility: settings, admin UI, export flows, template helpers, logging, and field resolution.
- `templates/admin/` contains admin page templates and partials only; business logic stays in PHP classes.
- `templates/olx/`, `templates/alo_bg/`, and `templates/realistimo/` contain bundled export templates and lookup data.
- `assets/js/` and `assets/css/` contain admin-side behavior and styling.
- `languages/` contains translation assets.

## Error Handling

- Guard direct file access with `defined( 'ABSPATH' )` or `WP_UNINSTALL_PLUGIN`.
- Validate user capability and nonce state before processing admin writes or AJAX requests.
- Return early on invalid state and use WordPress response helpers such as `wp_send_json_error()`.
- Sanitize external and request-derived data before persisting or rendering it.

## Logging

- Persist export run metadata in the custom `re_exporter_logs` table through `Export_Logger`.
- Prefer operational logging through database records and generated files instead of ad hoc debug output.
- Avoid `echo`, `var_dump`, or direct debug prints in production admin and exporter flows.
