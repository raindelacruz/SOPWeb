# Current Architecture State - March 14, 2026

## Status

SOPWeb now operates as a PDMS-only registry runtime.

The repository and local database are aligned to the post-bridge design.

## Settled Operating Model

- `procedures`, `procedure_versions`, `document_relationships`, `workflow_actions`, `procedure_sections`, and `section_change_log` are the only live procedural data surfaces
- PDMS procedure screens are the only supported lifecycle entry point for registration, revision, supersession, rescission, edit, and historical review
- the legacy post model, legacy post views, legacy posts controller, and legacy synchronization service have been removed from the repository
- the local database has had the legacy bridge schema removed so runtime and schema are aligned in this environment

## Authoritative Data Surfaces

Operational truth:

- `procedures`
- `procedure_versions`
- `document_relationships`
- `workflow_actions`
- `procedure_sections`
- `section_change_log`

Removed bridge surfaces from the repository contract:

- `posts`
- `procedures.legacy_post_id`
- `procedure_versions.legacy_post_id`
- legacy `amended_post_id`
- legacy `superseded_post_id`

## Current Runtime Meaning

PDMS is responsible for:

- new procedure registration
- revision registration
- supersession
- rescission
- current-version control
- normalized lineage
- section-history recording
- historical version review
- storing PDF document locators in `procedure_versions.file_path`; current screens now populate that path through a server-side PDF locator picker, while legacy upload filenames remain readable through compatibility resolution

Dashboard access pattern:

- the primary dashboard emphasizes active controlling procedures for day-to-day use
- the primary dashboard supports keyword search plus responsibility-center and effectivity date-range filters across active and historical sections
- superseded, rescinded, and archived procedure masters remain retained and are exposed through a dedicated historical dashboard section for audit review

## Lifecycle Model

Canonical version states:

- `REGISTERED`
- `EFFECTIVE`
- `SUPERSEDED`
- `RESCINDED`
- `ARCHIVED`

Controlling-version rule:

- only `EFFECTIVE` is eligible to be the controlling version

Procedure-master rule:

- `procedures.status` tracks `ACTIVE` versus terminal procedure-master states

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

## Cleanup Snapshot

Repository cleanup completed on March 14, 2026:

- removed `app/models/Post.php`
- removed `app/models/ProcedureSyncService.php`
- removed legacy `app/views/posts/*` browse/detail surfaces
- retargeted lightweight regressions to assert the PDMS-only repository shape
- updated current-state documentation to describe the post-bridge runtime

Database alignment completed in this environment:

- `database/migrations/2026_03_14_000007_drop_legacy_posts_bridge.sql` has been applied locally

## Verification Snapshot

Verification command:

- `php tests/run_regressions.php`

## Recommended Reading Order

1. `CURRENT_ARCHITECTURE_STATE_2026-03-13.md`
2. `PROJECT_CONTEXT.md`
3. `SYSTEM_DESIGN.md`
4. `DATABASE_REFERENCE.md`

Use older dated audit files as historical rollout context, not as the current runtime contract.
