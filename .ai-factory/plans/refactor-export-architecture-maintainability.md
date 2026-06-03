# Implementation Plan: Staged Refactor of Export Architecture for Maintainability

Branch: none
Created: 2026-06-04

## Settings
- Testing: no
- Logging: minimal
- Docs: no

## Goal
Refactor the export architecture of the WordPress plugin incrementally so that platform-specific behavior is isolated, the admin/export orchestration layer becomes thinner, and future marketplace extensions can be added without editing multiple unrelated classes.

## Constraints
- Preserve existing export behavior for OLX, ALO, Imoti, and Realistimo.
- Keep the plugin compatible with the current WordPress/PHP runtime assumptions.
- Do not mix behavioral feature work with architecture cleanup in the same changeset.
- Respect the current repository layout, where the actual plugin code lives under `re-exporter/`.

## Key Findings
- `re-exporter/includes/class-re-export-wizard.php` currently mixes AJAX boundary handling, review logic, platform routing, category resolution, export execution, and required-field validation.
- `re-exporter/includes/class-re-settings.php` acts as a large platform-specific configuration hub with multiple save flows and option schemas in one class.
- Exporters such as `re-exporter/includes/class-re-exporter-olx.php` and `re-exporter/includes/class-re-exporter-alo.php` duplicate the same high-level workflow while also depending on wizard internals and raw post-meta conventions.
- Platform behavior is scattered across admin pages, wizard logic, settings persistence, exporter logic, and metabox conventions, which makes extension expensive.

## Commit Plan
- **Commit 1** (after tasks 1-3): `refactor: extract export planning and platform contracts`
- **Commit 2** (after tasks 4-6): `refactor: move export orchestration behind services`
- **Commit 3** (after tasks 7-9): `refactor: split platform configuration and finalize compatibility`

## Tasks

### Phase 1: Establish Refactor Boundaries
- [x] Task 1: Inventory current platform responsibilities and define target contracts for export orchestration, platform modules, and shared infrastructure.
  Deliverable: a concrete internal contract list covering platform definition, export run inputs/outputs, category resolution, required-field validation, and file result descriptors.
  Files: `re-exporter/includes/class-re-export-wizard.php`, `re-exporter/includes/class-re-settings.php`, `re-exporter/includes/class-re-exporter-olx.php`, `re-exporter/includes/class-re-exporter-alo.php`, `re-exporter/includes/class-re-exporter-imoti.php`, `re-exporter/includes/class-re-exporter-realistimo.php`.
  Logging requirements: no new broad debug tracing; if temporary instrumentation is needed during extraction, log only WARN/ERROR at orchestration boundaries and remove any one-off exploratory logs before completion.

- [x] Task 2: Introduce shared export architecture primitives for platform-independent orchestration.
  Deliverable: new lightweight classes or interfaces for concepts such as platform definitions, export requests, export results, and platform handlers, without changing user-visible behavior yet.
  Files: create under `re-exporter/includes/` a small set of new shared classes for export contracts; update `re-exporter/re-exporter.php` to load them in the correct order.
  Logging requirements: add only minimal boundary logging for orchestration failures and invalid platform resolution, keeping log points at service entry/exit and fatal failure branches.

- [x] Task 3: Add a platform registry or equivalent composition layer that centralizes how platforms are discovered and wired.
  Deliverable: one place that maps `olx`, `alo`, `imoti`, and `realistimo` to their handlers and human labels so wizard/admin code no longer hardcodes platform branching everywhere.
  Files: create a registry/composition class under `re-exporter/includes/`; update `re-exporter/re-exporter.php` and any direct platform-selection logic in `re-exporter/includes/class-re-export-wizard.php`.
  Logging requirements: log only unknown platform lookup or handler resolution failures at ERROR level; avoid info-level noise for normal successful dispatch.

### Phase 2: Thin the Orchestration Layer
- [x] Task 4: Extract export run orchestration from `Export_Wizard` into a dedicated service.
  Deliverable: a service responsible for taking selected post IDs plus platform code and returning generated file descriptors, with `Export_Wizard` reduced to request validation and JSON response formatting.
  Files: `re-exporter/includes/class-re-export-wizard.php`; create a new orchestration service under `re-exporter/includes/`; update bootstrap wiring in `re-exporter/re-exporter.php`.
  Logging requirements: minimal logging at service entry, platform dispatch failure, filesystem/export failure, and final failure response path; no per-post success spam.

- [x] Task 5: Extract review/preflight logic from `Export_Wizard` into a dedicated review service.
  Deliverable: category grouping, required-field checks, and review summary preparation move behind a service that can be reused by multiple platforms and kept out of the AJAX controller.
  Files: `re-exporter/includes/class-re-export-wizard.php`; create one or more review/preflight classes under `re-exporter/includes/`.
  Logging requirements: log only invalid input, review-generation failure, and impossible platform/configuration states; avoid logging each reviewed post under minimal mode.

- [x] Task 6: Remove exporter dependence on wizard internals by moving category resolution and similar shared logic into dedicated collaborators.
  Deliverable: exporters no longer call methods like `resolve_subcat_id()` on the wizard; instead they depend on explicit collaborators or platform services.
  Files: `re-exporter/includes/class-re-exporter-olx.php`, `re-exporter/includes/class-re-exporter-alo.php`, `re-exporter/includes/class-re-export-wizard.php`; create shared resolution helpers under `re-exporter/includes/`.
  Logging requirements: log resolution failures only when they prevent export execution or indicate configuration corruption; keep normal empty-category skips silent unless they surface to the user.

### Phase 3: Split Platform Configuration and Shared Rules
- [x] Task 7: Decompose `Settings` into clearer platform-scoped responsibilities while preserving the existing option storage format.
  Deliverable: the current monolithic settings class is broken up or wrapped so that global settings and each platform configuration schema have isolated read/write responsibility, without forcing a storage migration.
  Files: `re-exporter/includes/class-re-settings.php`; create platform-scoped settings helpers or repositories under `re-exporter/includes/`; update admin consumers incrementally.
  Logging requirements: log only invalid persisted configuration, save failures, or schema translation failures; do not emit logs for routine option reads/writes.

- [x] Task 8: Normalize exporter workflow so each platform module follows the same high-level contract even if output formats differ.
  Deliverable: each exporter implements a consistent lifecycle such as collect config -> select/group posts -> build payload -> write output -> return result descriptors, reducing duplicated orchestration code.
  Files: `re-exporter/includes/class-re-exporter-olx.php`, `re-exporter/includes/class-re-exporter-alo.php`, `re-exporter/includes/class-re-exporter-imoti.php`, `re-exporter/includes/class-re-exporter-realistimo.php`, plus any new shared base helpers or strategy classes.
  Logging requirements: keep only failure-path logging for payload build errors, template lookup failures, and write failures; do not add row-by-row or item-by-item logs under minimal mode.

- [x] Task 9: Review metabox/admin integration points and align them with the new architecture boundaries without rewriting UI behavior.
  Deliverable: identify and fix remaining places where platform-specific admin/meta conventions bypass the new module boundaries, especially where export logic assumptions leak into admin classes.
  Files: `re-exporter/includes/class-re-metaboxes.php`, `re-exporter/includes/class-re-admin-page.php`, relevant `re-exporter/templates/admin/` files, and any touched shared service classes.
  Logging requirements: log only unexpected AJAX/admin boundary failures, invalid nonce/capability states already surfaced as errors, and architecture contract violations discovered during integration.

### Phase 4: Compatibility Hardening and Cleanup
- [ ] Task 10: Run a compatibility pass to ensure bootstrap wiring, platform execution, and file outputs still match the current plugin behavior after refactoring.
  Deliverable: verify that all four platforms still route through the new services correctly, old helper paths are removed or deprecated safely, and no dead composition code remains.
  Files: `re-exporter/re-exporter.php`, all touched classes under `re-exporter/includes/`, and any bootstrap/template includes affected by the refactor.
  Logging requirements: retain only durable WARN/ERROR logs that help diagnose production failures in orchestration, configuration, and file generation; remove transitional logs used only during refactor.

## Execution Notes
- Implement in narrow, behavior-preserving slices rather than one large rewrite.
- Prefer adapter layers first, then move callers behind them, then collapse old code.
- If a step reveals hidden coupling inside `class-re-metaboxes.php`, split that into a follow-up implementation task rather than broadening an earlier phase ad hoc.
