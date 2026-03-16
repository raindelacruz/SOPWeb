# AGENTS.md

## Project Overview

SOPWeb is a PHP and MySQL web application for procedural and operational documents.

Business intent clarification:

- the system is a registry for SOPs that are already approved outside the application
- the system's job is to store current procedures, record revisions and section-level lineage, and preserve historical traceability
- do not treat the repository as an approval-workflow system unless the product requirements explicitly change

Primary current-state reference:

- start with `CURRENT_ARCHITECTURE_STATE_2026-03-13.md` for the settled runtime position before relying on older dated audit notes

The repository now operates as a PDMS-first registry with a retained legacy archive mirror:

- PDMS tables are the operational source of truth for authoring, lifecycle, controlling-version behavior, and normalized lineage
- the legacy `posts` table remains for archive browsing, traceability, and compatibility access
- the application must preserve historical lineage while presenting current controlling procedures clearly

## Current Repository Structure

```text
SOPWeb/
├─ app/
│  ├─ controllers/
│  ├─ helpers/
│  ├─ models/
│  └─ views/
├─ config/
├─ core/
├─ database/
│  └─ migrations/
├─ libs/
│  └─ PHPMailer/
├─ public/
└─ uploads/
```

## Runtime Architecture

- The app uses lightweight custom MVC with plain PHP.
- Runtime request dispatch currently comes from `core/App.php`.
- `core/Router.php` exists but is not the primary runtime router and should be treated carefully until routing is explicitly consolidated.
- Database access uses the local `Database` wrapper around PDO.

## Current Functional Layers

### Legacy Layer

- `posts` remains the retained compatibility/archive table for search/list/show flows
- legacy relationship fields still exist:
  - `amended_post_id`
  - `superseded_post_id`
- admin-facing legacy create/edit is retired from the active runtime and should not be reintroduced casually

### PDMS Bridge Layer

The repository now contains an additive PDMS foundation:

- `procedures`
- `procedure_versions`
- `document_relationships`
- `workflow_actions`
- `procedure_sections`
- `section_change_log`

Current schema-alignment note:

- the repository now uses additive registry-native aliases where available, such as `registration_date`, `registered_by`, and `lifecycle_action_type`, while preserving approval-era columns for compatibility during migration

Supporting components include:

- `ProcedureReadModel`
- `ProcedureSyncService`
- `Procedures` controller
- procedures dashboard/history views
- legacy backfill utility
- relationship cleanup utility

Current implementation note:

- PDMS authoring policy is now centralized in a shared helper that defines allowed change types, relationship types, registry states, normalization behavior, UI helper metadata, and validation copy for active PDMS screens
- structured section-history tables now exist as additive foundations and normalized PDMS relationships are the authoritative lineage source for mapped records
- when changing authoring rules or compatibility behavior, update the shared policy surface and regression coverage together instead of patching isolated controller/view lists

This layer is now the operational source of truth, while legacy `posts` remains as an archival mirror linked through `legacy_post_id`.

## Core Business Rules

The codebase must preserve and enforce these rules:

1. Only one version may be the current controlling version of a procedure.
2. Historical procedural records must never be physically deleted.
3. Superseded and rescinded records must not be editable.
4. Amendment-style relationships should capture affected sections when available.
5. All relationships must remain traceable.
6. Current controlling procedures should be easier to access than historical versions.
7. The application should register already-approved SOPs rather than approve them internally.

## Current Registry / Lifecycle Model

Current live code now uses registry-oriented version states:

```text
REGISTERED
EFFECTIVE
SUPERSEDED
RESCINDED
ARCHIVED
```

Current implementation notes:

- these values are now the canonical states used by authoring, read models, and UI surfaces
- only `EFFECTIVE` is currently eligible to become the controlling version after normalization
- legacy approval-style values such as `DRAFT`, `FOR_REVIEW`, `FOR_APPROVAL`, and `APPROVED` are still readable as migration artifacts and are normalized into registry states where compatibility requires
- historical edit locks must be preserved whenever lifecycle rules require immutability

## Relationship Model

The target relationship set is:

- `AMENDS`
- `REVISES`
- `SUPERSEDES`
- `RESCINDS`
- `REFERENCES`
- `DERIVED_FROM`

Current implementation notes:

- the scalable relationship table exists
- the authoring UI can capture PDMS relationship intent
- legacy `amended_post_id` and `superseded_post_id` remain historical compatibility fields, not the authoritative lineage model for mapped records
- sync-managed relationships now use explicit management metadata when available, with legacy remarks-prefix fallback only for older databases

## Roles and Access

Current implemented roles are:

- `user`
- `admin`
- `super_admin`

Current access behavior:

- logged-in users can browse SOPs and procedures
- admins and super admins can create/edit SOPs and run PDMS maintenance actions
- super admins manage users and activity logs

Future role refinement, if needed, should stay registry-oriented, such as records administrators, office contributors, and executive viewers.

## Deletion and Retention

Legacy SOP deletion is intentionally blocked.

Do not reintroduce physical deletion for official procedural records unless the product requirements explicitly change and a replacement archival model is approved.

## Development Principles

### Analyze Before Editing

Before major work:

- inspect current code paths
- identify affected files
- explain the implementation approach

### Preserve Compatibility

- keep legacy `posts` behavior stable unless intentionally replaced
- prefer additive changes over destructive refactors
- do not remove bridge fields or compatibility paths casually
- do not reopen legacy admin mutation paths now that PDMS-first authoring is the settled runtime

### Keep Documentation in Sync

When implementation changes:

- update markdown files if the behavior, schema, routing, or lifecycle rules materially change
- note whether a change is current-state behavior or future-state design

### Protect Lineage

Any change touching these areas requires extra care:

- current version selection
- lifecycle transitions / registry-state changes
- relationship replacement/cleanup
- backfill/sync behavior
- access to historical records

## Purpose of This File

This file tells coding agents how to work safely in the SOPWeb repository as it transitions from a legacy document repository to a compatibility-first PDMS architecture.
