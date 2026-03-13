# Cleanup Phase Proposal - Approval-Era Cutover

## Purpose

This proposal outlines a harder future cutover for removing approval-era compatibility columns, fallbacks, and wrappers after the current bridge period is complete.

It assumes the current March 13, 2026 repository state:

- PDMS-first authoring is the normal admin path
- legacy `posts` still exists as a compatibility bridge
- registry-native aliases have been added
- approval-era compatibility fields and wrappers still remain in live code

## Goal

Shift SOPWeb from:

- registry-native behavior with approval-era compatibility writes and fallbacks

to:

- registry-native behavior with approval-era schema and wrapper debt removed

without breaking:

- historical lineage
- controlling-version semantics
- backfill/cleanup safety
- legacy record traceability during the final bridge window

## Proposed Exit Criteria Before Starting Hard Removal

Do not begin schema-dropping work until all of the following are true:

1. All active admin create/edit/revision/supersede/rescind flows route through `Procedures`
2. Legacy `Posts` create remains redirect-only and legacy edit is either removed or restricted to a fully documented migration-only maintenance action
3. All current databases have the registry alias migration applied
4. Backfill coverage shows no important unmapped legacy population still depending on approval-era normalization
5. Regression coverage exists for cutover-sensitive behavior

Required regression coverage before hard cutover:

- current-version selection with only registry states
- historical anchor behavior on terminal procedures
- supersede and rescind pointer clearing
- relationship cleanup preservation
- section-history normalization
- PDMS-first create and revision registration
- legacy compatibility redirect behavior until that surface is removed

## Recommended Cleanup Phases

### Phase A - Remove approval-era behavior dependencies while keeping schema

This is the safest first pass. Keep the old columns for now, but stop needing them in logic.

Scope:

- remove `APPROVED` from controlling-version fallback helpers
- update fallback selectors to treat only `EFFECTIVE` as controlling
- replace approval-centric helper names with registry-native equivalents
- stop using deprecated service wrappers from new code paths
- keep dual-write temporarily for compatibility

Primary code targets:

- `app/helpers/pdms_authoring_options.php`
- `app/models/ProcedureSyncService.php`
- `app/models/ProcedureVersion.php`
- `app/models/ProcedureAuthoringService.php`

Expected outcome:

- live behavior becomes registry-pure even before schema cleanup
- risk is reduced because historical databases can still retain old columns during the transition

### Phase B - Convert dual-write to registry-first write with read fallback

After Phase A is stable, stop writing approval-era mirror fields in normal flows.

Scope:

- write only `registration_date` and `registered_by`
- write only `lifecycle_action_type`
- keep read fallback from old columns for historical rows during the migration window
- add one-time data migration to backfill any missing alias data from old columns

Primary code targets:

- `app/models/ProcedureAuthoringService.php`
- `app/models/ProcedureSyncService.php`
- `app/models/WorkflowAction.php`
- `app/models/ProcedureReadModel.php`

Expected outcome:

- approval-era columns become cold historical baggage instead of active dependencies

### Phase C - Remove deprecated wrappers and workflow-era controller remnants

Once writes are registry-pure, simplify the service API and remove misleading compatibility names.

Remove or consolidate wrappers such as:

- `createProcedureWithInitialVersion()`
- `issueVersionForProcedure()`
- `promoteCurrentVersionToEffective()`
- `recordWorkflowAction()`

Review whether these methods should be:

- deleted outright
- replaced by direct canonical method calls
- retained only temporarily with deprecation comments if external code still references them

Also review controller remnants that now only flash deprecation messages:

- `Procedures::promote()`
- `Procedures::transition()`

Expected outcome:

- service and controller surfaces better match the registry model
- future contributors stop reintroducing workflow-era semantics accidentally

### Phase D - Hard schema cutover

Only after Phases A to C are stable should the schema itself be tightened.

Candidate schema removals:

- `procedure_versions.approval_date`
- `procedure_versions.approved_by`
- `workflow_actions.action_type`

Potential schema changes:

- set `procedure_versions.status` default to a registry-native value such as `REGISTERED`
- make `registration_date` the canonical non-compatibility field
- make `registered_by` the canonical actor field
- make `lifecycle_action_type` the canonical event label field

Important note:

Dropping `workflow_actions.action_type` should happen only after every query, insert path, and view renders from `lifecycle_action_type` directly.

### Phase E - Optional legacy bridge reduction

This phase is separate from approval-era cleanup, but it is the natural next step once the harder cutover succeeds.

Possible targets:

- shrink or remove `posts.amended_post_id`
- shrink or remove `posts.superseded_post_id`
- reduce `Posts` mutation surface further
- move remaining SOP detail dependence toward PDMS-first read models

This phase should happen only if product direction authorizes a deeper bridge reduction.

## Detailed Work Items

### Workstream 1 - Registry-pure controlling version logic

Tasks:

- change `PdmsAuthoringOptions::storedControllingWorkflowStatuses()` to registry-native semantics
- remove `APPROVED` ordering branches from `ProcedureSyncService`
- align `ProcedureVersion::getControllingCandidates()` with registry-only fallback rules
- add regression coverage for historical migrated rows that were previously normalized from approval-era states

Risk:

- older data may silently lose a fallback controlling candidate if it was never normalized

Mitigation:

- run a pre-cutover data audit to identify any remaining `APPROVED` values in `procedure_versions.status`
- normalize them to `EFFECTIVE` before shipping Phase A

### Workstream 2 - Registry-native field writes

Tasks:

- rename internal payload construction from approval-centric naming toward registration-centric naming
- stop binding `approval_date` and `approved_by` in create/update paths once alias-read fallback is in place
- stop binding `workflow_actions.action_type` once all reads and views use `lifecycle_action_type`

Risk:

- older databases missing alias columns may break

Mitigation:

- make the hard cutover require verified application of the alias migration on all target databases

### Workstream 3 - Naming cleanup

Rename examples:

- `workflowStatusGetsApprovalDate()` -> registry-native equivalent such as `workflowStatusGetsRegistrationDateMirror()` during transition, then remove mirror logic entirely
- `changeTypePreservesPreviousCurrentAsApproved()` -> registry-native equivalent reflecting replacement semantics rather than approval semantics

Goal:

- make code meaning match business meaning

### Workstream 4 - Migration and rollback plan

Before dropping columns:

1. Run data audit queries for non-null usage of approval-era columns
2. Backfill registry alias columns for any remaining rows
3. Ship code that no longer writes or reads the old columns in normal flow
4. Verify regression bundle plus targeted database smoke tests
5. Drop columns in a dedicated migration

Rollback principle:

- schema-drop migration should be isolated from behavior cleanup commits
- this allows behavior rollback without immediately restoring dropped columns in the same step

## Suggested Implementation Order

Recommended order:

1. Add audit queries and regressions for remaining approval-era data
2. Remove `APPROVED` controlling fallback behavior
3. Convert writes to registry-first with read fallback
4. Clean up wrappers and deprecated controller endpoints
5. Update docs to declare approval-era fields read-only historical baggage
6. Run a release candidate with production-like migrated data
7. Drop approval-era columns in a dedicated schema migration

## Suggested Pre-Cutover Audit Queries

Run and capture counts for:

- `procedure_versions.status` values not in the registry set
- rows where `approval_date` is populated but `registration_date` is null
- rows where `approved_by` is populated but `registered_by` is null
- workflow rows where `lifecycle_action_type` is null
- any code references still writing dropped columns

Example audit focus:

- how many `APPROVED` version rows still exist
- whether any current procedure still depends on such a row for fallback current-version recovery

## Risks

### High risk

- dropping approval-era columns before data is fully normalized
- removing action-type compatibility before all reads use `lifecycle_action_type`
- removing fallback support while historical data still contains approval-era states

### Medium risk

- breaking older migration paths in local or stale environments
- leaving deprecated wrappers in place and causing future drift after partial cleanup

### Low risk

- documentation churn
- view-label cleanup after service/schema cutover

## Recommendation

Proceed with a staged cleanup, not a single-step cutover.

The best next implementation phase is:

- Phase A plus Phase B together

Reason:

- they eliminate the most important approval-era behavioral dependencies
- they preserve rollback safety because schema can remain intact temporarily
- they create a clean checkpoint before the irreversible column-drop step

## Definition of Done for the Hard Cutover

The approval-era cutover can be considered complete when:

1. No normal code path writes `approval_date`, `approved_by`, or `workflow_actions.action_type`
2. No controlling-version logic depends on `APPROVED`
3. No active controller flow exposes deprecated stage-transition semantics
4. All regressions pass with registry-only data assumptions
5. Documentation describes approval-era behavior only as archived migration history
6. Dedicated migrations remove the obsolete columns cleanly
