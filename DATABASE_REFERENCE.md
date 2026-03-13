# DATABASE_REFERENCE.md

## Purpose

This file documents the current database reality of SOPWeb and the intended direction of its PDMS migration.

Business intent clarification:

- the system is a registry for SOPs already approved outside the application
- the database should prioritize current-version control, revision lineage, section lineage, and historical retention
- approval-specific fields and status semantics that still exist in code should be treated as compatibility artifacts unless explicitly repurposed

It should be used before editing:

- SQL migrations
- model queries
- sync logic
- lifecycle log logic
- lineage/history displays

## Platform

- MySQL / MariaDB
- InnoDB
- UTF-8 / utf8mb4 recommended
- XAMPP in development

## Current Operational Tables

### users

Current role values in live code:

- `user`
- `admin`
- `super_admin`

Future approval-workflow roles are not part of the clarified business intent.

### posts

This remains the legacy operational SOP table.

Key fields used in live code:

- `id`
- `title`
- `description`
- `reference_number`
- `date_of_effectivity`
- `upload_date`
- `file`
- `amended_post_id`
- `superseded_post_id`

Current behavior notes:

- `posts` is still the live create/edit/search source
- records are not physically deleted through the application flow
- legacy create/edit operations may synchronize the row into PDMS tables

### activity_logs

Still used for:

- authentication/user events
- legacy post events
- PDMS sync events
- cleanup/backfill events when available

## Implemented PDMS Tables

### procedures

Implemented columns:

- `id`
- `procedure_code`
- `title`
- `description`
- `category`
- `owner_office`
- `status`
- `current_version_id`
- `legacy_post_id`
- `created_by`
- `created_at`
- `updated_at`

Important notes:

- `legacy_post_id` is a migration bridge and is part of the live implementation
- `owner_office` is currently a string column, not `owner_office_id`
- `status` is currently used as a procedure-master state, not as the full version lifecycle field; active non-terminal procedures use `ACTIVE`, while terminal masters use `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`

### procedure_versions

Implemented columns:

- `id`
- `procedure_id`
- `legacy_post_id`
- `version_number`
- `document_number`
- `title`
- `summary_of_change`
- `change_type`
- `effective_date`
- `registration_date`
- `status`
- `file_path`
- `based_on_version_id`
- `created_by`
- `registered_by`
- `created_at`
- `updated_at`

Important notes:

- `legacy_post_id` allows a legacy SOP row to map to a PDMS version directly
- not every procedure master maps one-to-one with every historical legacy post
- `registration_date` and `registered_by` are now the canonical registry-native registration fields
- Phase D removes `approval_date` and `approved_by` from the live PDMS schema

### document_relationships

Implemented columns:

- `id`
- `source_version_id`
- `target_version_id`
- `relationship_type`
- `affected_sections`
- `remarks`
- `management_source` when the metadata migration has been applied
- `created_by`
- `created_at`

Relationship values used in code:

- `AMENDS`
- `REVISES`
- `SUPERSEDES`
- `RESCINDS`
- `REFERENCES`
- `DERIVED_FROM`

Current migration note:

- sync-managed relationship rows should use `management_source = 'LEGACY_SYNC'`
- older databases may still rely on the `[LEGACY_SYNC]` remarks prefix until the follow-up migration is applied
- sync fallback relationship typing should follow the selected `change_type` default when explicit relationship metadata is absent; for example, `REFERENCE` should fall back to `REFERENCES`, not `AMENDS`

### workflow_actions

Implemented columns:

- `id`
- `procedure_version_id`
- `lifecycle_action_type`
- `from_status`
- `to_status`
- `acted_by`
- `remarks`
- `acted_at`

Used for:

- sync creation/update/effective events
- current-version restoration events
- supersession events
- lifecycle tracing and audit context

Current design direction:

- this table should be understood as a lifecycle or registry action log
- `lifecycle_action_type` is now the canonical registry-native event label
- Phase D removes the legacy `action_type` column from the live PDMS schema
- approval-chain semantics should not drive future schema work

### procedure_sections

Implemented columns:

- `id`
- `procedure_id`
- `section_key`
- `section_title`
- `created_by`
- `created_at`
- `updated_at`

Current implementation note:

- this is the additive foundation for structured section lineage
- current authoring still captures affected sections as free text first, then normalizes those labels into reusable procedure-section records when the foundation tables are available

### section_change_log

Implemented columns:

- `id`
- `procedure_section_id`
- `procedure_version_id`
- `document_relationship_id`
- `change_type`
- `entry_kind`
- `section_label`
- `change_summary`
- `created_by`
- `created_at`

Current implementation note:

- this currently records normalized affected-section lineage for amendment/revision-style relationships
- the existing `document_relationships.affected_sections` text field remains part of the live bridge for compatibility and UI continuity

## Current Status Model

Current live code now normalizes version status to:

```text
REGISTERED
EFFECTIVE
SUPERSEDED
RESCINDED
ARCHIVED
```

Clarified design direction:

- these registry/lifecycle values are now the preferred live model for authoring, sync, and read behavior
- legacy approval-style values such as `DRAFT`, `FOR_REVIEW`, `FOR_APPROVAL`, and `APPROVED` are still accepted or readable only as migration compatibility artifacts
- `procedures.status` remains the master-level state field and should continue to use `ACTIVE` for non-terminal procedures

## Current Controlling Version Logic

The controlling version pointer is:

```text
procedures.current_version_id
```

Current behavior:

- sync logic updates the pointer when an eligible new version becomes controlling
- if a current version is changed to a non-controlling status, the sync layer tries to restore the best eligible fallback or clear the pointer
- `EFFECTIVE` is now the sole controlling-status candidate used for fallback current-version recovery
- procedures in terminal states (`SUPERSEDED`, `RESCINDED`, `ARCHIVED`) should not retain a controlling-version pointer; read surfaces may instead use the latest historical version as an audit anchor

Target behavior:

- current-version selection should be determined by registry lifecycle and effectivity, not by an in-system approval stage

## Current Migration Strategy

The live migration path is:

1. keep `posts`
2. mirror legacy records into PDMS tables through sync
3. use backfill to synchronize older records in batches
4. use cleanup to mark older sync-managed relationships
5. continue migrating UI and business rules toward PDMS behavior

This is important because the repository still preserves legacy compatibility paths even though PDMS-first procedure registration is now the preferred admin flow.

## Current Authoring Policy Reality

Current implementation note:

- allowed PDMS authoring choices and bridge-safe legacy compatibility choices are now centralized in the application layer rather than duplicated across multiple controller/view validation lists
- this shared authoring-policy surface currently governs option lists, normalization defaults, validation predicates, UI helper metadata, and validation/error-message copy
- deprecated manual promote/transition controller surfaces have been removed from the active PDMS flow; revision registration continues through the canonical register-revision path with an `issue` route alias for compatibility
- when database-facing sync or lineage behavior is changed, review that shared authoring policy together with the affected model/service logic so UI, validation, and sync assumptions do not drift apart
- approval-oriented compatibility columns have now been removed from the live PDMS schema; future cleanup should focus on historical migration artifacts and old migration assumptions instead

## Implemented Utilities

### Backfill

Purpose:

- find legacy `posts` rows that do not yet map to `procedure_versions.legacy_post_id`
- synchronize them into the PDMS model

### Cleanup

Purpose:

- normalize older relationship rows so sync-managed links are identifiable
- reduce accidental replacement of curated PDMS relationship data during re-sync

## Not Yet Implemented

- richer section-lineage authoring and reporting

## Safety Rules

When changing database behavior:

- do not remove legacy compatibility fields casually
- do not reintroduce destructive deletion of official records
- be careful when changing `current_version_id` semantics
- protect relationship lineage during sync, backfill, and cleanup
- treat approval-era column names as historical migration context, not active-schema targets

## Purpose of This File

This file is the repository’s current-state database reference for the legacy-plus-PDMS bridge architecture now present in SOPWeb.
