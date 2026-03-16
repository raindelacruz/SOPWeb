# Alignment Audit - March 13, 2026

> Historical note: this audit reflects the pre-retirement state from earlier on March 13, 2026. Legacy `posts` create/edit write paths were retired later the same day, so use `POST_CUTOVER_AUDIT_2026-03-13.md` plus the current project docs for the latest runtime position.

## Scope

This audit compares the updated repository markdown docs against the live implementation as inspected on March 13, 2026:

- `AGENTS.md`
- `PROJECT_CONTEXT.md`
- `SYSTEM_DESIGN.md`
- `DATABASE_REFERENCE.md`

Evidence was taken from current controllers, models, migrations, views, and the lightweight regression bundle under `tests/`.

## Executive Summary

The updated markdown set is substantially aligned with the current codebase.

The main architecture claims are accurate:

- SOPWeb is operating as a compatibility-first hybrid registry
- `posts` remains the live legacy compatibility table
- PDMS foundation tables, services, controller flows, and read surfaces are present
- registry-oriented lifecycle normalization is live
- historical edit locks and terminal-pointer clearing are enforced
- section-history foundations and relationship cleanup utilities exist

The remaining drift is not a broad documentation problem. It is concentrated in approval-era compatibility debt that the docs already describe as transitional:

- approval-era columns are still written as compatibility mirrors
- some fallback query logic still treats `APPROVED` as a controlling-state candidate
- several approval-era or workflow-era method names remain as wrappers or deprecated entry points
- the original foundation migration still creates approval-oriented defaults that later code normalizes away

## Verification Snapshot

Lightweight regression bundle run on March 13, 2026:

- `php tests/run_regressions.php`
- Result: all bundled PDMS regressions passed

Covered checks include:

- PDMS semantics
- legacy compatibility gate
- mapped legacy redirect behavior
- terminal pointer clearing
- authoring status validation
- historical workflow lane behavior
- procedure-master state handling
- sync relationship fallback behavior
- section-history surface exposure
- registry schema alias usage

## Aligned Areas

### 1. Hybrid runtime and routing reality

The docs correctly state that:

- the app is custom MVC
- request dispatch is still driven by `core/App.php`
- `core/Router.php` is present but not the active runtime router

No conflicting runtime-router cutover was found in the inspected controller flow.

### 2. Legacy `posts` remains the compatibility layer

The docs correctly describe `posts` as still live for compatibility:

- `Posts` controller still handles list, search, show, compatibility edit, and fallback create wiring
- legacy link fields `amended_post_id` and `superseded_post_id` are still used
- physical deletion remains blocked in the app flow

The docs are also accurate that legacy create/edit has been narrowed:

- legacy create redirects to PDMS-first flows when PDMS foundation is available
- mapped legacy edit redirects into PDMS views
- unmapped edit requires explicit `legacy_compatibility_intent`

### 3. PDMS bridge layer exists and is live

The documented PDMS foundation is present in migrations and code:

- `procedures`
- `procedure_versions`
- `document_relationships`
- `workflow_actions`
- `procedure_sections`
- `section_change_log`

The key live components named in the docs also exist:

- `ProcedureReadModel`
- `ProcedureSyncService`
- `ProcedureAuthoringService`
- `Procedures` controller
- procedures dashboard, detail, revision, supersede, rescind, and version views

### 4. Shared authoring policy is centralized

The docs correctly say that PDMS authoring rules and bridge-safe legacy rules now share a policy surface.

`app/helpers/pdms_authoring_options.php` currently centralizes:

- allowed change types
- allowed relationship types
- registry-state normalization
- current-eligibility logic
- target and affected-section requirements
- bridge-safe legacy subsets
- validation/error copy
- UI helper metadata

Both `Posts` and `Procedures` use that helper for validation and normalization, so the docs are aligned here.

### 5. Registry-oriented lifecycle model is live

The markdown claim that live code uses registry-oriented version states is accurate.

Canonical normalized states in code:

- `REGISTERED`
- `EFFECTIVE`
- `SUPERSEDED`
- `RESCINDED`
- `ARCHIVED`

The docs are also accurate that:

- only `EFFECTIVE` is current-eligible
- legacy approval states remain readable only as migration artifacts
- terminal procedures should not retain a controlling pointer

This behavior is reflected in:

- status normalization in `PdmsAuthoringOptions`
- current-version updates in `ProcedureAuthoringService`
- current-version reconciliation in `ProcedureSyncService`
- historical-anchor behavior in `ProcedureReadModel`

### 6. Procedure-master versus version-state split is real

The docs correctly distinguish:

- `procedures.status` as the master state
- `procedure_versions.status` as the registry lifecycle state

Observed live behavior matches the docs:

- active procedure masters use `ACTIVE`
- terminal procedures use `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`
- version rows carry the normalized lifecycle states

### 7. Relationship model and cleanup utilities are real

The docs correctly describe:

- normalized PDMS relationships in `document_relationships`
- bridge use of legacy post link fields
- cleanup support for sync-managed relationships

Observed behavior:

- relationship types include `AMENDS`, `REVISES`, `SUPERSEDES`, `RESCINDS`, `REFERENCES`, and `DERIVED_FROM`
- sync-managed rows use `management_source = 'LEGACY_SYNC'` when available
- older fallback still uses remarks-prefix behavior
- cleanup utility upgrades older rows toward explicit management metadata

### 8. Section-lineage foundations are present

The docs correctly describe additive section-history foundations.

Observed behavior:

- migrations create `procedure_sections` and `section_change_log`
- authoring and sync paths normalize free-text affected sections into section history when the foundation exists
- read surfaces expose section history at procedure and version levels

### 9. Historical protection rules are materially enforced

The docs state that:

- historical records are not deleted
- superseded/rescinded records are not editable
- historical procedures should stay visible

The current implementation matches that intent:

- delete flow in `Posts` remains blocked
- historical procedures are blocked from edit/revision/rescind flows
- mapped legacy SOPs can be locked based on normalized terminal state
- read surfaces still render history and historical anchors

## Partial Alignment / Notable Residual Drift

These items do not invalidate the updated docs, but they are important enough to record explicitly.

### 1. Approval-era compatibility columns are still part of live writes

The docs say approval-era columns remain as compatibility artifacts, and that is true. The important nuance is that they are not just readable artifacts yet; they are still actively written in the main services.

Still written today:

- `procedure_versions.approval_date`
- `procedure_versions.approved_by`
- `workflow_actions.action_type`

At the same time, registry-native aliases are also written when available:

- `procedure_versions.registration_date`
- `procedure_versions.registered_by`
- `workflow_actions.lifecycle_action_type`

Audit conclusion:

- documentation is directionally accurate
- implementation is still dual-write, not alias-preferred read/write only

### 2. Controlling-version fallback still includes `APPROVED`

The docs correctly frame `EFFECTIVE` as the only current-eligible normalized state.

However, compatibility fallback code still includes approval-era state support in candidate selection:

- `PdmsAuthoringOptions::storedControllingWorkflowStatuses()` returns `['APPROVED', 'EFFECTIVE']`
- `ProcedureSyncService::getBestControllingVersionForProcedure()` still orders `APPROVED` after `EFFECTIVE`
- `ProcedureVersion::getControllingCandidates()` also uses that same helper

This is understandable migration debt, but it means some deeper fallback queries still preserve approval-era semantics even though authoring and normalization now prefer registry states.

### 3. Foundation migration defaults are older than the current docs

The original PDMS foundation migration still creates approval-oriented defaults:

- `procedure_versions.status` default is `DRAFT`
- `procedure_versions.approval_date` and `approved_by` are part of the original shape
- `workflow_actions` originally only has `action_type`

The updated markdown docs already explain this as migration history plus alias layering, so the docs are not wrong. The important caveat is that a fresh database depends on later migrations and app-level normalization to match the documented registry model.

### 4. Some approval-era or workflow-era wrappers still remain in services

The docs say approval-style behavior should be treated as artifact rather than the preferred model. That is correct at the behavior level, but the service API still carries wrappers and deprecated naming that will matter during cutover.

Examples:

- `createProcedureWithInitialVersion()` wraps `registerProcedure()`
- `issueVersionForProcedure()` wraps `registerRevisionForProcedure()`
- `promoteCurrentVersionToEffective()` wraps `markCurrentVersionEffective()`
- `recordWorkflowAction()` wraps `recordLifecycleAction()`
- `transitionLatestWorkflowVersion()` still exists even though controller entry points now deprecate manual stage transitions

### 5. Some code still uses approval-centric naming in error and helper logic

A few helper names still reflect the older mental model:

- `workflowStatusGetsApprovalDate()`
- `changeTypePreservesPreviousCurrentAsApproved()`
- `replacementStatusForPreviousCurrent()` logic still inherits that naming history

Behavior is mostly registry-aligned; naming is not yet fully aligned.

## Documentation Gaps Worth Tightening Later

The markdown set is strong overall. These are optional refinements, not urgent corrections.

### 1. Clarify dual-write status for alias migration

The docs currently say approval-era columns are preserved for compatibility, but a future refresh should state more explicitly that:

- main services still dual-write approval-era and registry-era columns
- reads usually prefer registry-native aliases when present
- full removal is not yet safe

### 2. Call out fallback `APPROVED` handling as intentional migration debt

The docs may benefit from one sentence noting that fallback current-version recovery still tolerates `APPROVED` rows for older migrated data.

### 3. Distinguish deprecated service wrappers from active controller paths

The docs already describe deprecated controller behavior well. A future update could add that some deprecated wrapper methods still exist internally even though the primary UI no longer advertises those flows.

## Cutover Blockers Identified by This Audit

Before a hard removal of approval-era compatibility artifacts, the following dependencies must be addressed:

1. Service-layer dual writes to `approval_date` and `approved_by`
2. Service-layer dual writes to `workflow_actions.action_type`
3. Fallback current-version selection that still includes `APPROVED`
4. Old foundation-schema defaults that still seed approval-era state names
5. Residual wrapper methods and approval-era naming in authoring/sync services
6. Legacy bridge dependence on `posts.amended_post_id` and `posts.superseded_post_id`

## Overall Assessment

Status: aligned with documented current-state intent, with explicit transitional debt still present

Confidence: high

Reasoning:

- the docs describe the repository as a compatibility-first PDMS migration rather than a fully cut-over system
- the code matches that framing very closely
- the remaining mismatches are mostly known transitional dependencies, not contradictions to the updated architectural narrative

## Recommended Next Step

Use this audit as the baseline for a cleanup phase that removes approval-era compatibility in stages:

- first stop relying on approval-era fallbacks in code
- then stop dual-writing approval-era columns
- then remove deprecated wrappers and obsolete migrations/default assumptions
- only after that consider dropping approval-era schema columns in a deliberate hard cutover
