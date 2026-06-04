# Plan: Shared Field Defaults With Platform Overrides

Created: 2026-06-04
Mode: fast
Type: enhancement

## Settings

- Testing: no
- Logging: minimal
- Docs: no

## Roadmap Linkage

- Milestone: "none"
- Rationale: "Skipped by user"

## Goal

Add a shared configuration layer for common listing fields so values such as title, description, price, currency, city, images, and similar cross-platform data can be configured once and reused by marketplace-specific settings.

Keep marketplace-specific category routing, deal routing, required-field maps, and platform-only enum/value logic separate.

## Constraints

- Preserve the modular-monolith boundary: shared mapping logic belongs in shared settings/helpers, while platform-specific behavior stays in dedicated platform modules.
- Do not scatter new `if ( platform )` branches across unrelated shared classes.
- Treat bundled templates under `templates/` as read-only source assets.
- Keep admin request parsing thin and persistence centralized in `Settings`.

## Research Context

- Current architecture stores `field_map` and `value_map` separately for `olx`, `alo`, `imoti`, and `realistimo` in `re-exporter/includes/class-re-settings.php`.
- Admin settings data is assembled per platform in `re-exporter/includes/class-re-admin-page.php`.
- Exporters, review flows, wizard validation, and metabox rendering currently read platform maps directly, so a shared layer must expose effective resolved mappings instead of only adding another saved option.
- Existing per-category and per-template override behavior already exists for some platforms and must remain intact.

## Scope

In scope:
- Shared defaults model for common fields
- Platform-level override mechanism
- Admin UI for shared defaults
- Effective mapping/value resolution consumed by exporters and validation flows

Out of scope:
- Reworking category/subcategory routing
- Reworking deal type routing
- Changing bundled export schemas or template assets
- Full documentation pass

## Tasks

### Phase 1: Define Shared Mapping Model

- [x] Define the canonical shared field vocabulary and fallback rules in `re-exporter/includes/class-re-settings.php`.
Files: `re-exporter/includes/class-re-settings.php`, optionally a new shared settings/helper class under `re-exporter/includes/`
Deliverable: a normalized list of shared field keys and storage format for shared defaults plus platform overrides.
Behavior: shared fields cover only truly cross-platform concepts such as title, description, price, currency, city, images, and core contact data; category/deal/required maps remain platform-specific.
Logging: minimal; log only invalid or unsupported shared-field payloads through existing operational paths if needed, avoid debug noise.
Dependency notes: foundation for all later tasks.

- [x] Add persistence and read APIs for shared defaults and effective platform mappings.
Files: `re-exporter/includes/class-re-settings.php`, `re-exporter/includes/class-re-olx-settings.php`, `re-exporter/includes/class-re-alo-settings.php`, `re-exporter/includes/class-re-imoti-settings.php`, `re-exporter/includes/class-re-realistimo-settings.php`
Deliverable: getters/save methods that can return raw shared defaults, raw platform overrides, and effective merged mappings for each platform.
Behavior: explicit platform override wins; otherwise shared default applies; platform-only fields continue using existing platform maps.
Logging: minimal; guard invalid arrays and malformed keys without verbose instrumentation.
Dependency notes: depends on Task 1.

### Phase 2: Wire Admin Configuration

- [x] Introduce a shared settings section in the admin UI and keep platform forms focused on overrides.
Files: `re-exporter/includes/class-re-admin-page.php`, `re-exporter/templates/admin/page-settings.php`, related admin partials in `re-exporter/templates/admin/partials/`
Deliverable: admin UI where shared fields are configured once, while platform tabs show only platform-specific mappings and override controls for shared-capable fields.
Behavior: user can set a shared source once, then selectively override it in OLX/ALO/Imoti/Realistimo where necessary.
Logging: minimal; no new debug output in templates, only standard validation behavior on save.
Dependency notes: depends on Task 2.

- [x] Adjust form-save handling and settings-page data assembly to support the shared layer cleanly.
Files: `re-exporter/includes/class-re-admin-page.php`, `re-exporter/includes/class-re-global-settings.php` if needed
Deliverable: save flow and template data payloads include shared defaults and resolved per-platform state without duplicating save logic.
Behavior: shared settings save independently, platform settings preserve current behavior for non-shared sections.
Logging: minimal; preserve existing permission/nonce failure behavior.
Dependency notes: depends on Task 3.

### Phase 3: Switch Runtime Consumers To Effective Mappings

- [x] Refactor exporters and validation/review flows to read effective mappings instead of raw platform maps where shared fields are supported.
Files: `re-exporter/includes/class-re-exporter-olx.php`, `re-exporter/includes/class-re-exporter-alo.php`, `re-exporter/includes/class-re-exporter-imoti.php`, `re-exporter/includes/class-re-exporter-realistimo.php`, `re-exporter/includes/class-re-export-wizard.php`, `re-exporter/includes/class-re-export-review-service.php`
Deliverable: runtime flows resolve shared-capable fields from the merged effective mapping while preserving existing platform-specific logic and value transformations.
Behavior: existing exports continue to work, but shared defaults now fill common mappings when no platform override exists.
Logging: minimal; add only narrow warnings/errors for impossible mapping states that would otherwise silently break export generation.
Dependency notes: depends on Task 2 and should be coordinated with Task 6.

- [x] Update metabox and other platform-specific editing flows to respect the shared/effective mapping contract.
Files: `re-exporter/includes/class-re-metaboxes.php`, any helper classes that assume direct platform `field_map` reads
Deliverable: post-level override UI and runtime helpers continue to function when a field source comes from shared defaults instead of a platform-local map.
Behavior: per-post overrides still take precedence where they already exist; shared defaults only change the baseline source selection.
Logging: minimal; avoid new noisy metabox logging.
Dependency notes: depends on Task 5.

### Phase 4: Compatibility And Verification

- [x] Review backward compatibility for existing saved options and add migration/fallback handling if required.
Files: `re-exporter/includes/class-re-settings.php`, possibly bootstrap wiring in `re-exporter/re-exporter.php`
Deliverable: existing installations keep working without forcing users to re-enter all mappings on upgrade.
Behavior: old platform-only settings remain valid; shared defaults start empty or are inferred safely without destructive migration.
Logging: minimal; emit only actionable warnings for malformed legacy state.
Dependency notes: depends on Tasks 2, 5, and 6.

- [ ] Run smoke verification on admin save flows and at least one export path per platform without adding automated tests.
Files: no product-code target; verify through existing plugin admin/export flows and adjust touched files as needed
Deliverable: manual verification checklist completed for shared save flow, override precedence, and unchanged platform-specific category/deal behavior.
Behavior: confirm shared default -> platform override precedence works consistently in settings, review, and export output.
Logging: minimal; use existing operational output only.
Dependency notes: final validation after implementation tasks.

## Risks

- A shared layer can break existing exports if any runtime path still reads raw platform maps instead of effective merged mappings.
- UI can become confusing if shared defaults and platform overrides are displayed without clear precedence messaging.
- Backward compatibility is sensitive because multiple classes assume platform-local options are the source of truth.

## Commit Plan

1. `feat(settings): add shared field defaults model and effective mapping APIs`
Tasks: 1-2

2. `feat(admin): add shared mapping UI and save flow`
Tasks: 3-4

3. `refactor(export): resolve shared defaults through effective platform mappings`
Tasks: 5-6

4. `chore(compat): preserve legacy mappings and verify shared override behavior`
Tasks: 7-8

## Next Step

Run `$aif-implement` to execute the plan.
