# Project Description: Real Estate Exporter

## Overview
Real Estate Exporter is a WordPress plugin that exports property listings from a selected custom post type to multiple third-party real estate marketplaces. It provides an admin interface for configuring field mappings, category routing, value overrides, export filters, and generated output files.

The plugin currently supports OLX, ALO, Imoti, and Realistimo export flows. It stores plugin settings in WordPress options, persists export history in a custom database table, and writes generated export artifacts to the WordPress uploads directory.

## Core Features
- Configure a target custom post type and export filters from a WordPress admin screen.
- Map WordPress fields and taxonomy values to platform-specific export schemas.
- Generate OLX CSV and marketplace-specific JSON or feed outputs from reusable templates.
- Provide AJAX-powered admin workflows for record selection, review, generation, and file management.
- Maintain export logs and generated file directories per platform.

## Tech Stack
- **Programming language:** PHP 7.4+
- **Framework:** WordPress plugin architecture
- **Database:** WordPress MySQL via options API and a custom log table
- **ORM:** None
- **Frontend assets:** Vanilla JavaScript, jQuery on WordPress admin pages, CSS
- **Integrations:** OLX, ALO, Imoti, Realistimo

## Architecture Notes
The plugin is organized as a modular WordPress package with a single bootstrap file and feature-specific classes under `includes/`. Template files under `templates/` define export payloads and admin UI fragments, while `assets/` holds admin-side JavaScript and CSS.

The main design pressure is keeping marketplace-specific logic isolated so new exporters can be added without turning the bootstrap or settings layer into a monolith of conditional branches. Shared concerns such as settings, field resolution, template lookup, and logging should stay reusable and platform-agnostic.

## Non-Functional Requirements
- **Compatibility:** Must follow WordPress plugin lifecycle hooks and admin capability checks.
- **Data safety:** All settings writes and AJAX inputs should be sanitized and validated before use.
- **Extensibility:** New marketplaces should fit the existing exporter/template/settings conventions.
- **Observability:** Export runs should remain traceable through the log table and generated files UI.
- **Filesystem behavior:** Generated files should stay under the WordPress uploads area, while bundled templates remain read-only.

## Architecture
See `.ai-factory/ARCHITECTURE.md` for detailed architecture guidelines.
Pattern: Modular Monolith
