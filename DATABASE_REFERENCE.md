# DATABASE_REFERENCE.md

## Purpose

This file documents the current database contract expected by the cleaned repository.

Important timing note:

- the repository is already aligned to the post-bridge schema
- the local database has already had the March 14, 2026 bridge-drop migration applied

Other environments that have not yet applied that migration may still contain older bridge artifacts that the code no longer uses.

## Platform

- MySQL / MariaDB
- InnoDB
- UTF-8 / utf8mb4 recommended
- XAMPP in development

## Active Operational Tables

### users

Live role values:

- `user`
- `admin`
- `super_admin`

### activity_logs

Used for:

- authentication and user events
- PDMS registration and maintenance events
- administrative audit context

### procedures

Implemented columns expected by current code:

- `id`
- `procedure_code`
- `title`
- `description`
- `category`
- `owner_office`
- `status`
- `current_version_id`
- `created_by`
- `created_at`
- `updated_at`

Important notes:

- `status` is the procedure-master state
- non-terminal procedures use `ACTIVE`
- terminal procedure masters use `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`
- `current_version_id` points only to an `EFFECTIVE` version

### procedure_versions

Implemented columns expected by current code:

- `id`
- `procedure_id`
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

- `registration_date` and `registered_by` are canonical registry fields
- approval-era fields such as `approval_date` and `approved_by` are not part of the current repository contract

### document_relationships

Implemented columns expected by current code:

- `id`
- `source_version_id`
- `target_version_id`
- `relationship_type`
- `affected_sections`
- `remarks`
- `management_source` when present
- `created_by`
- `created_at`

Relationship values used in code:

- `AMENDS`
- `REVISES`
- `SUPERSEDES`
- `RESCINDS`
- `REFERENCES`
- `DERIVED_FROM`

### workflow_actions

Implemented columns expected by current code:

- `id`
- `procedure_version_id`
- `lifecycle_action_type`
- `from_status`
- `to_status`
- `acted_by`
- `remarks`
- `acted_at`

Important notes:

- `lifecycle_action_type` is the canonical event label
- legacy `action_type` is not part of the current repository contract

### procedure_sections

Implemented columns expected by current code:

- `id`
- `procedure_id`
- `section_key`
- `section_title`
- `created_by`
- `created_at`
- `updated_at`

### section_change_log

Implemented columns expected by current code:

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

## Status Model

Version states:

```text
REGISTERED
EFFECTIVE
SUPERSEDED
RESCINDED
ARCHIVED
```

Current rules:

- only `EFFECTIVE` is controlling
- `procedures.status` is master-level state, not version lifecycle
- terminal procedures must not retain `current_version_id`

## Removed Bridge Contract

The cleaned repository no longer uses:

- `posts`
- `procedures.legacy_post_id`
- `procedure_versions.legacy_post_id`
- `posts.amended_post_id`
- `posts.superseded_post_id`

These have been removed from the local database by:

- `database/migrations/2026_03_14_000007_drop_legacy_posts_bridge.sql`

## Current Authoring Contract

Database-facing writes now come from PDMS-native flows only:

- procedure registration
- revision registration
- procedure edit of current metadata
- mark-effective transition
- supersession
- rescission
- historical-version archiving

No repository code should depend on legacy post sync, backfill, or mirror writes.

## Safety Rules

When changing database behavior:

- do not reintroduce physical deletion of official records
- preserve current-version integrity
- preserve normalized lineage integrity
- keep procedure-master and version lifecycle logic separate
- treat approval-era columns only as migration history
- do not add new dependencies on removed bridge fields

## Purpose of This File

This file is the current-state database reference for SOPWeb's PDMS-only repository contract with the local schema already aligned.
