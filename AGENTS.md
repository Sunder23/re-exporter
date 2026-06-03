# AGENTS.md

> Keep this file aligned with the real project structure and AI context artifacts. Update it when the layout or workflow rules change materially.

## Project Overview
Real Estate Exporter is a WordPress plugin for exporting property listings from a configured custom post type to marketplace-specific feed formats such as OLX, ALO, Imoti, and Realistimo. The plugin combines an admin configuration UI, exporter classes, bundled templates, and export-run logging.

## Tech Stack
- **Programming language:** PHP 7.4+
- **Framework:** WordPress plugin architecture
- **Database:** WordPress MySQL via options API and a custom log table
- **ORM:** None

## Project Structure
```text
.
|- re-exporter.php                  # Plugin bootstrap, constants, dependency wiring, lifecycle hooks
|- uninstall.php                    # Cleanup for plugin deletion
|- assets/
|  |- css/                          # Admin styles
|  `- js/                           # Admin-side scripts and widgets
|- includes/                        # Core and feature PHP classes
|  |- class-re-settings.php         # Typed wrapper around WordPress option storage
|  |- class-re-admin-page.php       # Admin menu, form handling, asset loading
|  |- class-re-export-wizard.php    # AJAX-driven export wizard
|  |- class-re-exporter-*.php       # Marketplace-specific export generators
|  |- class-re-*-template.php       # Template readers and lookup helpers
|  `- class-re-export-logger.php    # Custom log table access
|- templates/
|  |- admin/                        # Admin page templates and partials
|  |- olx/                          # OLX CSV templates and lookup JSON
|  |- alo_bg/                       # ALO JSON templates and lookup JSON
|  `- realistimo/                   # Realistimo bundled data
|- languages/                       # Translation files
`- .ai-factory/                     # AI Factory context and project guidance
```

## Key Entry Points
| File | Purpose |
|------|---------|
| `re-exporter.php` | Registers the plugin, loads all classes, creates export storage on activation, and boots admin features on `plugins_loaded`. |
| `includes/class-re-admin-page.php` | Handles admin menus, form save routing, asset enqueueing, and tab rendering. |
| `includes/class-re-export-wizard.php` | Drives the multi-step export flow and AJAX endpoints for export review and generation. |
| `includes/class-re-settings.php` | Centralizes plugin option reads, sanitization, and writes. |
| `includes/class-re-export-logger.php` | Creates and queries the export log table. |
| `uninstall.php` | Removes plugin options, the log table, and generated export files on uninstall. |

## Documentation
| Document | Path | Description |
|----------|------|-------------|
| AI Factory description | `.ai-factory/DESCRIPTION.md` | High-level product, stack, and architectural context for AI-assisted work. |
| AI Factory architecture | `.ai-factory/ARCHITECTURE.md` | Practical architecture rules and module-boundary guidance for new changes. |
| Base rules | `.ai-factory/rules/base.md` | Detected codebase conventions and default implementation rules. |

## AI Context Files
| File | Purpose |
|------|---------|
| `AGENTS.md` | Quick structural map for agents and contributors working in this repository. |
| `.ai-factory/DESCRIPTION.md` | Project specification, supported platforms, and non-functional constraints. |
| `.ai-factory/ARCHITECTURE.md` | Architecture pattern, dependency rules, and extension guidance. |
| `.ai-factory/rules/base.md` | Baseline naming, module, error-handling, and logging conventions. |
| `.ai-factory/config.yaml` | AI Factory language, path, workflow, and git behavior configuration. |

## Agent Rules
- Decompose multi-step shell work into separate commands when each step matters independently.
  Incorrect combined command: `php -l re-exporter.php && php -l includes/class-re-settings.php`
  Correct decomposed command: first `php -l re-exporter.php`, then `php -l includes/class-re-settings.php`
- Keep marketplace-specific rules inside dedicated exporter, template, or settings code paths instead of scattering platform conditionals across unrelated classes.
- Treat bundled templates in `templates/` as read-only source assets unless the task is explicitly about changing export schemas.
