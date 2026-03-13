# PROJECT_CONTEXT.md

## System Name

SOPWeb - Standard Operating Procedure Web Management System

## Purpose

SOPWeb manages procedural and operational documents for the organization.

Clarified business intent:

- SOPWeb is a document registry for Standard Operating Procedures already approved outside the system
- the primary requirement is systematic recording of SOP history, revision lineage, section lineage, and current active procedure identification
- the system should not be treated as an approval workflow engine

The repository currently serves two purposes at once:

- it continues to run the legacy SOP/document compatibility path through the `posts` module
- it incrementally builds out a Procedural Document Management System (PDMS) on top of that legacy data

The live application should be understood as a hybrid migration platform rather than a finished end state.

## Technology Stack

- PHP
- MySQL / MariaDB
- Apache in XAMPP during development
- HTML + Bootstrap
- minimal JavaScript
- PHPMailer

No framework is used.

## Actual Repository Layout

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

## Runtime Notes

- Request dispatch currently runs through `core/App.php`.
- `core/Router.php` exists but does not currently define the main runtime flow.
- Database queries use the project `Database` wrapper around PDO.

## Existing Core Modules

### Posts Module

The legacy `posts` module is still the main authoring path for SOP records.

Current features:

- create SOP
- edit SOP
- upload PDF
- list/search SOPs
- view SOP details
- legacy amendment/supersession links

Important current behavior:

- deletion of historical SOP records is blocked
- successful create/edit operations attempt to synchronize the post into PDMS tables
- direct legacy edit is being narrowed into an explicit compatibility-maintenance path for unmapped SOPs rather than a normal procedural lifecycle action
- direct legacy create is already redirected into PDMS-first flows whenever the PDMS foundation is present; the legacy create screen should be treated as fallback-only

### Document Relationships

Legacy relationship fields still exist in `posts`:

- `amended_post_id`
- `superseded_post_id`

These remain compatibility fields only. The PDMS layer adds scalable normalized relationships through `document_relationships`.

### PDMS Layer

The repository now contains an additive procedural layer with:

- procedure masters
- procedure versions
- normalized version relationships
- lifecycle transition logs
- section lineage foundations
- procedure dashboard and history screens
- legacy backfill utility
- relationship cleanup utility

Key implementation components:

- `ProcedureReadModel`
- `ProcedureSyncService`
- `Procedures` controller and views

Current implementation note:

- PDMS authoring and legacy bridge-safe compatibility behavior now share a centralized authoring-policy helper that supplies allowed option lists, normalization defaults, validation rules, UI helper metadata, and validation copy
- controller flows in both `Procedures` and `Posts` now use shared validation/result helpers so current PDMS authoring policy is no longer scattered across separate controller branches and view-specific arrays
- deprecated manual promote/transition controller stubs have been removed; `Procedures::issue()` remains as the active route compatibility alias for revision registration

### Users and Roles

Current implemented roles:

- `user`
- `admin`
- `super_admin`

Current access behavior:

- logged-in users can browse SOP and procedure pages
- admins and super admins can create/edit SOPs and run PDMS maintenance actions
- super admins manage users and activity logs

Future read/write separation, if needed, should be registry-oriented rather than approval-oriented.

### Activity Logs

The system still uses `activity_logs` and now also records PDMS sync and cleanup events when that table is available.

## Current Data Model Reality

### Legacy Core

The `posts` table remains the operational compatibility table for:

- title
- description
- reference number
- effectivity
- uploaded PDF
- legacy amendment/supersession linkage

### PDMS Foundation

The repository now includes additive PDMS tables:

- `procedures`
- `procedure_versions`
- `document_relationships`
- `workflow_actions`
- `procedure_sections`
- `section_change_log`

Current implementation note:

- Phase D hard cutover now treats `procedure_versions.registration_date`, `procedure_versions.registered_by`, and `workflow_actions.lifecycle_action_type` as the canonical registry fields
- approval-era compatibility columns have dedicated retirement migrations and are no longer part of the live application schema after cutover

Important transition detail:

- `legacy_post_id` bridge fields are used so legacy records can be mapped into the PDMS model without removing `posts`

## Current Limitations

The implementation has progressed significantly, but these limits still exist:

- legacy compatibility paths still remain alongside PDMS-first procedure registration
- structured section-history tables now exist as additive schema foundations, but richer reporting and authoring support still need refinement
- approval-era compatibility fields have been retired from the live PDMS schema; remaining approval-style references should be treated as migration history only
- PDMS validation is still lighter than the final records-governance model
- richer multi-relationship authoring is only partially exposed in the UI

## Current Registry / Lifecycle Reality

Canonical version status values now used in live code:

- `REGISTERED`
- `EFFECTIVE`
- `SUPERSEDED`
- `RESCINDED`
- `ARCHIVED`

Current status-layer note:

- these values now apply to the normalized live behavior of `procedure_versions.status`
- procedure masters in `procedures.status` currently use `ACTIVE` while the procedure remains non-terminal, then switch to terminal master states such as `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`
- legacy approval-style values are still readable as migration artifacts and are normalized into registry states where compatibility paths encounter them
- controlling-version fallback is now registry-only; `EFFECTIVE` is the sole controlling-state candidate during current-version recovery

Clarified intent:

- create/edit in legacy `posts` can supply PDMS authoring metadata
- sync logic mirrors records into PDMS tables
- backfill can migrate older posts in batches
- cleanup can mark older sync-managed relationships for safer re-sync behavior
- future code changes should treat approval-like statuses only as migration artifacts, not as the real business lifecycle

## Current Hardening Snapshot

The current migration-hardening rules now reflected in code are:

- PDMS procedure screens are the normal admin lifecycle path for create, revise, supersede, rescind, edit, and lifecycle actions
- mapped legacy SOP records should redirect admins into PDMS-first maintenance flows rather than normal legacy mutation
- unmapped legacy SOP edit is an explicit compatibility-maintenance exception, not a default admin workflow
- legacy SOP create should be treated as fallback-only when the PDMS foundation is available
- legacy compatibility create/edit metadata is now limited to pre-terminal registry states; supersession, rescission, and other terminal outcomes should be executed from PDMS registry actions
- legacy compatibility change types are now limited to the bridge-safe subset the old fields can represent reliably; richer PDMS-native change semantics should be authored from PDMS-first screens
- legacy compatibility relationship intent is now limited to the bridge-safe subset the old fields can represent reliably; richer normalized PDMS relationships should be authored from PDMS-first screens
- shared authoring policy definitions now drive PDMS option lists, bridge-safe legacy option lists, normalization behavior, UI helper hints, validation messages, and controller validation flow from one current-state source
- normalized PDMS relationships are the primary lineage view; legacy relationship displays are compatibility and audit context
- terminal procedures should not retain a controlling-version pointer; historical procedure screens may use the latest version as an audit anchor instead
- procedure-master state and version lifecycle state are intentionally separate: `procedures.status` tracks whether the procedure is active or terminal, while `procedure_versions.status` carries the normalized registry lifecycle for each version
- lightweight executable regression checks now exist for historical-anchor semantics and the explicit legacy compatibility gate under `tests/`

To run the current lightweight regression bundle:

- `php tests/run_regressions.php`

## Current Executive Access Reality

Executives and general users do not yet have a distinct implemented role separation.

However, the application now provides executive-friendly read surfaces:

- current procedures dashboard
- procedure detail/history page
- PDMS readiness panel on SOP detail pages
- section-lineage panels on procedure and mapped SOP detail pages

## Key Operating Principle

Historical procedural records must remain viewable and traceable.

Official records should not be physically deleted.

## Purpose of This File

This file gives developers and coding agents an accurate snapshot of the repository as it exists now: a live legacy SOP application with an additive Phase 1-8 PDMS bridge already present.
