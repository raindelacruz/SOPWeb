# SYSTEM_DESIGN.md

## System Name

SOPWeb - Procedural Document Management System Design

## Design Objective

SOPWeb is evolving from a legacy SOP repository into a document registry for already-approved Standard Operating Procedures (SOPs).

The system must preserve:

- the currently active controlling procedure
- version history
- amendment, revision, supersession, and rescission chains
- section-level lineage where applicable
- executive-friendly access to active procedures

The system is not intended to approve procedures inside the application. Any SOP registered here is assumed to have been approved outside the system.

## Current Implementation Stage

The repository has completed an additive Phase 1-8 foundation and now operates as a hybrid system:

- legacy authoring still happens through `posts`
- PDMS records are synchronized into procedural tables
- read-oriented PDMS screens already exist
- migration/backfill/cleanup tools already exist

The next design concern is no longer whether PDMS tables should exist. They do. The focus is now on aligning the code and UI to the clarified registry intent: lineage recording, current-version control, edit restrictions, and read-friendly access.

## Core Domain Separation

### Procedure Master

Represents the enduring identity of a procedure.

Current implementation:

- stored in `procedures`
- tracks `current_version_id`
- uses procedure-level state such as `ACTIVE`, `SUPERSEDED`, `RESCINDED`, and `ARCHIVED`
- may also store a `legacy_post_id` bridge during migration

### Procedure Version

Represents a specific registered procedure version.

Current implementation:

- stored in `procedure_versions`
- may map back to a legacy post via `legacy_post_id`
- carries status, change type, effective date, and file path

## Lifecycle Goals

The target registry lifecycle is:

1. register original procedure record
2. register amendment with affected sections
3. register partial revision
4. register full revision
5. register superseding procedure
6. register rescission where needed
7. record references and derivations
8. preserve permanent history

## Implemented Data Model

### procedures

Current implemented purpose:

- groups versions by long-term procedure identity
- stores `current_version_id`
- supports executive/current-procedure views

### procedure_versions

Current implemented purpose:

- stores each synchronized or authored procedure version
- tracks change type, status, effectivity, and source file
- uses `registration_date` and `registered_by` as the canonical registration metadata fields after the Phase D hard cutover

### document_relationships

Current implemented purpose:

- stores normalized version-to-version relationships
- supports `AMENDS`, `REVISES`, `SUPERSEDES`, `RESCINDS`, `REFERENCES`, `DERIVED_FROM`

### workflow_actions

Current implemented purpose:

- stores version lifecycle and sync transition history
- uses `lifecycle_action_type` as the canonical registry-event label after the Phase D hard cutover

### procedure_sections

Current implemented purpose:

- stores reusable section identities per procedure as an additive foundation for structured amendment lineage

### section_change_log

Current implemented purpose:

- records section-level change entries tied to procedure versions and relationship context when affected sections are supplied

### Not Yet Implemented

- full cutover away from legacy compatibility create/edit as a routine admin path
- distinct executive-viewer role separation, if the product decides that role split is needed

## Registry State Model

Target version states should reflect registry lifecycle, not approval workflow:

```text
REGISTERED
EFFECTIVE
SUPERSEDED
RESCINDED
ARCHIVED
```

Current implementation note:

- the live code now normalizes version status into registry states in authoring, sync, and read surfaces
- legacy approval-style values remain readable as migration artifacts in data normalization, but they are no longer part of the active schema contract
- controlling-version fallback now treats `EFFECTIVE` as the sole controlling registry state for recovery logic
- `registration_date`, `registered_by`, and `lifecycle_action_type` are now required canonical registry fields in the live model
- `procedures.status` remains the master-level state field; active procedures use `ACTIVE`, while terminal procedure masters use `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`

Rules that the implementation must uphold:

1. Only one version may be current for a procedure.
2. Current-version selection must be driven by registry lifecycle, not in-system approval.
3. Superseded and rescinded records must not be editable.
4. Lifecycle changes must be logged.
5. Historical records must remain viewable.
6. Terminal procedures should not continue to present a controlling-version pointer; historical views may use a latest-version audit anchor instead.
7. A revision or superseding record should automatically preserve the previous controlling record as history.

## Relationship Logic

The target relationship model remains:

- `AMENDS`
- `REVISES`
- `SUPERSEDES`
- `RESCINDS`
- `REFERENCES`
- `DERIVED_FROM`

Current implementation note:

- legacy `amended_post_id` and `superseded_post_id` still drive much of the initial relationship generation
- newer sync behavior uses explicit relationship management metadata when available so curated PDMS links are less likely to be replaced during re-sync
- read surfaces should present normalized PDMS relationships as the primary lineage view and treat legacy relationship displays as compatibility context

## Current Authoring Policy Surface

Current implementation note:

- PDMS authoring choices and bridge-safe legacy compatibility choices are now centralized in a shared policy helper rather than duplicated across controllers, service normalization, and view option arrays
- the shared policy surface currently defines allowed change types, allowed relationship types, status normalization, target/affected-section requirements, UI helper metadata, and validation/error-message copy
- PDMS create and revision-registration controller validation now reuse shared authoring validation flow, while legacy compatibility create/edit validation now reuse shared bridge-safe validation flow
- deprecated promote/transition controller remnants have been removed; the remaining route-level compatibility alias is `Procedures::issue()` forwarding to `registerRevision()`
- future lifecycle-policy changes should update the shared policy surface and the regression checks together rather than patching individual controllers or views in isolation

## Current Views

Implemented read surfaces:

- SOP detail page with PDMS readiness/history context
- current procedures dashboard
- procedure history/detail page
- section-lineage panels on procedure and mapped SOP detail pages

These should be treated as the current executive-friendly access layer, even though the final executive dashboard vision is broader.

## Target UI / User Flow

The target UI should behave like a document registry:

1. Register new procedure
2. Register amendment or revision against the current active version
3. Capture affected sections when the change is section-specific
4. Register superseding or rescinding procedure when lifecycle requires it
5. Automatically preserve prior versions as history and clearly mark the current active one
6. Make current active procedures easier to access than historical versions

## Access Model

Current implemented roles:

- `user`
- `admin`
- `super_admin`

Target future roles, if the product needs finer read/write separation, should remain registry-oriented rather than approval-oriented.

Examples:

- records administrator
- office contributor
- executive viewer
- general user

## Migration Strategy

The active transition strategy is:

1. preserve legacy `posts`
2. synchronize create/edit activity into PDMS tables
3. backfill older posts in batches
4. normalize older sync-managed relationships
5. progressively move behavior and visibility toward PDMS views

Current implementation note:

- unmapped legacy SOP records may still use direct legacy edit for explicit compatibility maintenance, but mapped records and normal lifecycle actions should be routed through PDMS-first screens
- future cutover work should continue shrinking the legacy compatibility surface and keep amendment/revision registration as the primary lifecycle entry point

This is the current approved path unless a deliberate PDMS-first cutover is planned.

## Routing Reality

The live application currently uses convention-based dispatch through `core/App.php`.

Any future routing refactor should explicitly decide whether to:

- keep convention routing
- adopt the route file pattern
- remove unused router code

Do not assume `core/Router.php` is the active runtime router without verifying it.

## Current Design Priorities

Before adding new PDMS features, align these areas:

1. historical edit locks
2. registry-state simplification
3. current-version semantics
4. section-lineage clarity
5. documentation accuracy
6. routing clarity

## Purpose of This File

This file describes both the target direction and the current implemented migration architecture so future development does not treat the repository like either a pure legacy system or a fully completed PDMS.
