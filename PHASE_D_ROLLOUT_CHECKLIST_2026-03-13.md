# Phase D Rollout Checklist

## Purpose

This checklist is for safely applying the Phase D hard cutover in environments that still carry approval-era compatibility columns.

Primary migration:

- `database/migrations/2026_03_13_000005_drop_approval_compatibility_columns.sql`

Preflight query pack:

- `database/migrations/phase_d_cutover_preflight.sql`

## Preconditions

- The code deployed to the target environment already includes the Phase D application changes.
- The earlier PDMS migrations are already applied, including:
  - `2026_03_12_000001_add_pdms_foundation.sql`
  - `2026_03_13_000002_add_relationship_management_metadata.sql`
  - `2026_03_13_000003_add_section_history_foundation.sql`
  - `2026_03_13_000004_add_registry_schema_aliases.sql`
- The target database still has the old compatibility columns present, so `000005` has meaningful work to do.

## Recommended Rollout Order

1. Take a database backup or snapshot.
2. Put admin-facing mutation work on hold for the rollout window.
3. Run `database/migrations/phase_d_cutover_preflight.sql`.
4. Confirm these checks all pass:
   - `procedure_versions.registration_date` has no null rows.
   - `procedure_versions.registered_by` has no null rows.
   - `workflow_actions.lifecycle_action_type` has no null or empty rows.
   - version statuses are already in the registry-oriented set you expect to preserve.
5. Apply `database/migrations/2026_03_13_000005_drop_approval_compatibility_columns.sql`.
6. Run the lightweight regression bundle from the app workspace:
   - `php tests/run_regressions.php`
7. Smoke-test these screens:
   - current procedures dashboard
   - procedure detail page
   - procedure version page
   - mapped legacy SOP detail page
   - register revision flow
   - mark effective flow
8. If all checks pass, reopen admin mutation activity.

## Expected Post-Cutover Schema Shape

After `000005`, the live PDMS schema should use:

- `procedure_versions.registration_date`
- `procedure_versions.registered_by`
- `workflow_actions.lifecycle_action_type`

These approval-era compatibility columns should be gone:

- `procedure_versions.approval_date`
- `procedure_versions.approved_by`
- `workflow_actions.action_type`

## Rollback Reality

This cutover is schema-destructive by design. A safe rollback means restoring the database backup or snapshot taken before the migration, then redeploying the pre-Phase-D application code if needed.

Do not attempt an ad hoc rollback by re-adding columns without also restoring their historical data and corresponding application behavior.
