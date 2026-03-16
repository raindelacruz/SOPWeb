# PROJECT_CONTEXT.md

## System Name

SOPWeb - Standard Operating Procedure Web Management System

## Purpose

SOPWeb is a registry for procedural documents that are already approved outside the application.

Core business intent:

- record the current controlling procedure version clearly
- preserve procedure history and normalized lineage
- capture section-level lineage where available
- avoid treating the system as an internal approval engine

## Current Runtime Position

As of March 14, 2026, the repository is aligned to a PDMS-only runtime.

That means:

- PDMS procedure screens are the only supported lifecycle path
- normalized PDMS tables are the only active data contract
- the old legacy post model, legacy sync service, legacy post views, and legacy posts controller are removed from the codebase
- the local database has already had the March 14, 2026 schema-drop migration applied

## Technology Stack

- PHP
- MySQL / MariaDB
- Apache in XAMPP during development
- HTML + Bootstrap
- minimal JavaScript
- PHPMailer

No framework is used.

## Repository Layout

```text
SOPWeb/
|- app/
|  |- controllers/
|  |- helpers/
|  |- models/
|  `- views/
|- config/
|- core/
|- database/
|  `- migrations/
|- libs/
|  `- PHPMailer/
|- public/
`- uploads/
```

## Runtime Notes

- request dispatch runs through `core/App.php`
- `core/Router.php` exists but is not the primary runtime router
- database access uses the project `Database` wrapper around PDO

## Active Functional Layers

### PDMS Registry Layer

Live runtime tables and concepts:

- procedure masters in `procedures`
- procedure versions in `procedure_versions`
- normalized lineage in `document_relationships`
- lifecycle logs in `workflow_actions`
- section lineage in `procedure_sections` and `section_change_log`

Primary implementation components:

- `Procedure`
- `ProcedureVersion`
- `ProcedureAuthoringService`
- `ProcedureReadModel`
- `DocumentRelationship`
- `Procedures` controller and PDMS views

## Roles and Access

Current implemented roles:

- `user`
- `admin`
- `super_admin`

Current access behavior:

- logged-in users can browse procedures
- admins and super admins can register and maintain procedures
- super admins manage users and activity logs

## Lifecycle Model

Canonical version states:

- `REGISTERED`
- `EFFECTIVE`
- `SUPERSEDED`
- `RESCINDED`
- `ARCHIVED`

Current procedure-master model:

- `procedures.status` uses `ACTIVE` while a procedure is live
- terminal procedure masters use `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`
- only `EFFECTIVE` may hold the controlling-version pointer

## Current Hardening Rules

- historical procedures and terminal records must remain read-only
- terminal procedures must not retain `current_version_id`
- normalized PDMS relationships are authoritative
- historical versions remain accessible through PDMS history/detail screens
- lightweight regressions should keep the repository aligned to the post-bridge architecture

## Database Alignment Note

The local environment is already migrated to the PDMS-only schema.

Applied migration:

- `database/migrations/2026_03_14_000007_drop_legacy_posts_bridge.sql`

Any other environment that has not applied that migration may still contain old bridge columns and tables that the repository no longer uses.

## Purpose of This File

This file gives developers and coding agents the current repository contract: a PDMS-only SOP registry with the local schema already aligned.
