# SYSTEM_DESIGN.md

## System Name

SOPWeb - Procedural Document Management System Design

## Design Objective

SOPWeb is a registry for already-approved SOPs.

The design must support:

- one clear current controlling version per active procedure
- permanent historical retention
- normalized amendment, revision, supersession, rescission, reference, and derivation lineage
- section-level lineage where captured
- clear separation between active and historical records

The system is not intended to run an internal approval workflow.

## Current Implementation Stage

The repository is now in the PDMS-only stage.

Completed cleanup state:

- PDMS tables and services are the only active procedure-management path
- retired legacy post browsing, model, controller, and sync code has been removed
- the remaining major operational step is applying the March 14, 2026 bridge-drop migration to the live database

## Core Domain Model

### Procedure Master

Represents the long-lived identity of a procedure.

Stored in:

- `procedures`

Responsibilities:

- hold procedure identity fields
- track `current_version_id`
- carry master-level status such as `ACTIVE`, `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`

### Procedure Version

Represents a registered version of a procedure.

Stored in:

- `procedure_versions`

Responsibilities:

- carry version number and document number
- record change type and lifecycle status
- hold effectivity and file-path metadata
- link to the version it is based on when applicable

## Lifecycle Model

Canonical version states:

```text
REGISTERED
EFFECTIVE
SUPERSEDED
RESCINDED
ARCHIVED
```

Rules:

1. Only one version may be current for a procedure.
2. Only `EFFECTIVE` may be controlling.
3. Historical and terminal records must not be editable.
4. Lifecycle changes must be logged.
5. Terminal procedure masters must not retain a controlling-version pointer.
6. Older versions remain available through PDMS history/detail views.

## Relationship Model

Authoritative relationship storage:

- `document_relationships`

Supported relationship types:

- `AMENDS`
- `REVISES`
- `SUPERSEDES`
- `RESCINDS`
- `REFERENCES`
- `DERIVED_FROM`

Affected-section lineage is recorded through:

- `procedure_sections`
- `section_change_log`

## Current Authoring Surface

Primary runtime components:

- `ProcedureAuthoringService`
- `ProcedureReadModel`
- `Procedures` controller
- PDMS procedure create, issue, show, supersede, rescind, edit, and version views

Shared authoring policy covers:

- change-type options
- relationship-type options
- lifecycle-state normalization
- target-version requirements
- affected-section requirements
- UI helper metadata
- validation and error-message copy

## Routing Reality

Current runtime dispatch uses `core/App.php` and defaults directly into the PDMS procedures surface.

## Migration Position

Repository state:

- already cleaned for the post-bridge architecture

Live-database state:

- pending application of `database/migrations/2026_03_14_000007_drop_legacy_posts_bridge.sql`

After that migration, the runtime and production schema will match fully.

## Design Priorities

1. keep current-version semantics strict
2. preserve historical lineage
3. keep procedure-master and version lifecycle states distinct
4. keep section-lineage reporting trustworthy
5. keep documentation and regressions aligned with live architecture

## Purpose of This File

This file describes the current implemented design target for SOPWeb: a PDMS-only registry with a minimal retired-route compatibility shell and no live legacy data bridge.
