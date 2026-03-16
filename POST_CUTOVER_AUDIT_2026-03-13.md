# Post-Cutover Audit - March 13, 2026

> Historical note: this audit captures the March 13, 2026 pre-cleanup state when the legacy bridge still remained in the repository. For the current PDMS-only runtime contract prepared on March 14, 2026, use `CURRENT_ARCHITECTURE_STATE_2026-03-13.md`, `PROJECT_CONTEXT.md`, `SYSTEM_DESIGN.md`, and `DATABASE_REFERENCE.md`.

## Scope

This audit records the live applied state after:

- Phase D schema cutover
- local application of the registry-alias prerequisite migration
- local data normalization of lingering approval-era values

Evidence was taken from:

- current application code and markdown docs
- local MariaDB schema and data in `sopweb`
- `php tests/run_regressions.php`

## Executive Summary

SOPWeb is now aligned around the registry model in code, schema, and local data.

The application no longer depends on approval-era compatibility columns in the live schema contract, and the local database no longer carries approval-era status values in `procedure_versions` or terminal master records with stale controlling pointers.

Legacy `posts` create/edit routes are also retired as active admin write surfaces; PDMS screens are now the supported lifecycle entry point while `posts` remains available for compatibility-oriented browsing and traceability.
The current architectural position is that PDMS tables are the operational source of truth, while `posts` remains a retained archival mirror linked through `legacy_post_id`.

Status:

- aligned after cutover

Confidence:

- high

## Applied Database Steps

The following migrations were applied locally during the final cutover pass:

- `database/migrations/2026_03_13_000004_add_registry_schema_aliases.sql`
- `database/migrations/2026_03_13_000005_drop_approval_compatibility_columns.sql`
- `database/migrations/2026_03_13_000006_normalize_registry_transition_data.sql`

Backup snapshots created during rollout:

- `database/backups/sopweb_phase_d_20260313_175926.sql`
- `database/backups/sopweb_phase_d_20260313_175942.sql`

## Code and Schema Alignment

### Canonical registry fields are now the live contract

The active application contract now uses:

- `procedure_versions.registration_date`
- `procedure_versions.registered_by`
- `workflow_actions.lifecycle_action_type`

The approval-era compatibility columns removed by cutover are:

- `procedure_versions.approval_date`
- `procedure_versions.approved_by`
- `workflow_actions.action_type`

### Live code is registry-native

The main PDMS write paths now use only the registry-native fields:

- `ProcedureAuthoringService`
- `ProcedureSyncService`
- `ProcedureVersion`
- `WorkflowAction`

Read surfaces also render registry-native workflow labels directly:

- `app/views/procedures/show.php`
- `app/views/procedures/version.php`
- `app/views/posts/show.php`

Those detail surfaces now also explain:

- when a record is `REGISTERED` but not yet the effective controlling version
- when a historical procedure page is using the latest version as an audit anchor
- when PDMS is authoritative and the legacy SOP page is acting only as a compatibility mirror

Legacy post write views are no longer part of the active runtime path.

### Controlling-version behavior remains registry-only

Current-version recovery and control logic now treat `EFFECTIVE` as the only controlling candidate.

This matches the current markdown docs and the intended registry business rules.

## Local Data Verification

The local `sopweb` database was checked after cutover and normalization.

Verified results:

- remaining `procedure_versions.status = 'APPROVED'`: `0`
- terminal procedures with non-null `current_version_id`: `0`

Observed workflow status pairs after normalization:

- `NULL -> REGISTERED`
- `REGISTERED -> EFFECTIVE`
- `EFFECTIVE -> REGISTERED`
- `EFFECTIVE -> SUPERSEDED`
- `EFFECTIVE -> EFFECTIVE`
- `SUPERSEDED -> EFFECTIVE`

The lingering historical `APPROVED` version rows previously found in procedure history were normalized successfully into registry-native historical states.

## Regression Verification

Verification run on March 13, 2026:

- `php tests/run_regressions.php`

Result:

- all lightweight PDMS regressions passed

This now includes coverage for:

- Phase D cutover behavior
- Phase D rollout assets
- local Phase D command assets
- post-cutover data normalization
- PDMS lifecycle service flows for registration, revision replacement, supersession, rescission, and historical-version archiving

## Documentation Alignment

The current live markdown set is aligned with the post-cutover state:

- `PROJECT_CONTEXT.md`
- `SYSTEM_DESIGN.md`
- `DATABASE_REFERENCE.md`

These docs now correctly describe:

- registry-native canonical schema fields
- retirement of approval-era compatibility columns from the live PDMS schema
- approval-style statuses as migration history rather than active-schema behavior

## Residual Notes

The repository still preserves migration history and compatibility context in older migration files and a retained legacy archive mirror around `posts`.

That remaining legacy context is expected and does not conflict with the current registry-native PDMS runtime.

## Overall Assessment

SOPWeb has completed the harder PDMS registry cutover successfully in the local environment.

The current code, schema, local data, and documentation now tell the same story:

- the app is a registry for already-approved SOPs
- registry-native lifecycle fields are canonical
- current controlling behavior is driven by `EFFECTIVE`
- historical lineage remains preserved without relying on approval-era schema artifacts
