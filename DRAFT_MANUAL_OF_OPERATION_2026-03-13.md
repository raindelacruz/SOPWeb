# Manual of Operations and User Guide

System: SOPWeb Procedure and Document Management System (PDMS-first runtime)  
Basis of this manual: repository inspection completed on March 16, 2026  
Implementation basis: current code in `app/`, `core/`, `config/`, `database/`, `public/`, and current markdown references

## 1. Document Status

This manual reflects the current implementation found in the repository as inspected on March 16, 2026.

This manual does not describe planned features or older legacy behavior unless clearly marked for reference.

## 2. Short Gap Analysis

The older draft documentation and some historical markdown files do not fully match the current codebase.

### 2.1 Items found in older documentation that are no longer implemented

- Legacy `posts` browsing and maintenance screens are no longer present in the repository.
- Legacy SOP authoring through `/posts/create` and `/posts/edit` is not implemented.
- Legacy bridge/backfill operations are no longer active maintenance tools. The routes `/procedures/backfill` and `/procedures/cleanup` now redirect back to the procedures dashboard with a retirement message.
- The default post-login destination is no longer a legacy SOP page. Successful login now redirects to `/procedures`.
- Browser-based PDF upload is not the current behavior. The active screens use a server-side PDF path picker.

### 2.2 Features found in the code but missing or under-described in older documentation

- A PDMS-only dashboard with active procedures for all logged-in users.
- A separate historical procedures section visible on the dashboard for `admin` and `super_admin`.
- Search, responsibility center filter, effectivity date range filter, and card/list view switching on the procedures dashboard.
- Version-level detail pages with relationship history, lifecycle trail, and section lineage.
- Historical version archiving for eligible superseded or rescinded versions.
- Self-registration of users with inactive status by default, followed by activation by a super administrator.
- User profile management screen with office update and optional password change.

### 2.3 Documented but not currently implemented

- A meaningful operational home page. The `Home` controller exists, but the page content is placeholder only and is not the main working entry point.
- A visible user interface action to mark a `REGISTERED` version as `EFFECTIVE`. The service logic exists in code, but no current controller/view flow exposes it to users.
- A separate in-system download button for PDFs. The system opens PDFs inline; any saving or downloading depends on the browser's own PDF controls.

## 3. System Overview

SOPWeb is a web-based registry for procedures and related PDF documents.

Its present working model is PDMS-first. This means the application now manages procedures directly through the PDMS tables and screens. The system is used to register procedures that have already been approved outside the application.

The main purpose of the live system is to:

- keep a clear list of current controlling procedures
- preserve older versions for audit and reference
- record revisions, supersessions, rescissions, and references
- keep version-to-version and section-level traceability
- provide controlled access based on user role

## 4. Purpose of the System

Based on the current code, the system is intended to support the following practical office work:

- viewing current operative procedures
- locating historical versions when needed
- registering new procedure records
- registering revised procedure versions
- recording when one procedure supersedes another
- recording when a procedure has been rescinded
- managing user access
- keeping an activity log of important user and PDMS actions

Important note based on actual code logic:

- the system is a registry, not an internal approval workflow
- only `EFFECTIVE` versions are treated as controlling versions
- historical records are retained and are not deleted through the application

## 5. User Roles and Permissions

The following roles are implemented in the code.

### 5.1 User

Regular users can:

- log in
- view the procedures dashboard
- search and filter active procedures
- open procedure details
- open version details
- open procedure PDFs
- update their own office assignment and password from the profile screen

Regular users cannot:

- create procedures
- register revisions
- edit procedures
- supersede procedures
- rescind procedures
- archive historical versions
- manage users
- view the activity logs page

### 5.2 Admin

Admins can do everything a regular user can do, and can also:

- create procedures
- register revisions
- edit active procedures
- create superseding procedures
- rescind active procedures
- archive eligible historical versions
- use the PDF catalog picker for procedure authoring screens
- view the historical procedures section on the dashboard

Admins cannot:

- open the Manage Users page
- open the Activity Logs page
- activate or deactivate users
- change user roles

### 5.3 Super Admin

Super administrators can do everything an admin can do, and can also:

- open the Manage Users page
- filter and search user accounts
- activate users
- deactivate users
- change user roles between `user` and `admin`
- open the Activity Logs page

Important note based on actual code logic:

- the current user management screen does not provide a button to promote another user to `super_admin`

## 6. Login and Access Instructions

### 6.1 Opening the system

In the inspected development setup, the application is served from:

- `/SOPWeb/public/`

The active runtime router defaults to the `Procedures` controller. In normal use, the working area is the procedures dashboard.

### 6.2 Logging in

1. Open the login page.
2. Enter your registered email address.
3. Enter your password.
4. Select `Login`.

If the login is successful, the system redirects the user to the procedures dashboard.

Important note based on actual code logic:

- only accounts with status `active` can log in
- inactive accounts are rejected even if the email and password are correct

### 6.3 Registering a new user account

The system includes a self-registration page.

1. Open the registration page.
2. Enter the required details:
   - ID number
   - office
   - first name
   - last name
   - middle name
   - email address
   - password
3. Submit the form.

Actual system behavior:

- the account is saved with status `inactive`
- the user must still be activated by a super administrator before login is allowed
- the registration flow attempts to send email notifications

Important note based on actual code logic:

- if mail settings are incomplete, registration may stop with an error message after the user record has already been created

## 7. Main Navigation Overview

The current navigation bar changes based on the user role.

### 7.1 Visible to logged-in users

- `Procedures`
- `Manage Profile`
- `Logout`

### 7.2 Additional menu for admins and super admins

- `PDMS Create`

### 7.3 Additional menu for super admins only

- `Manage Users`
- `Activity Logs`

## 8. User Guide

This section is for regular end users and office staff who mainly browse and read records.

### 8.1 Open the procedures dashboard

After login, the system opens the Current Procedures Dashboard.

This page is the main working screen for regular users.

The dashboard shows:

- active procedure cards or list entries
- the current version status
- effectivity date
- responsibility center
- an `Open Procedure` action
- an `Open Current PDF` or `PDF` action when a file path is available

### 8.2 Search and filter procedures

The dashboard supports the following search and filter tools:

- keyword search
- responsibility center filter
- effective date from
- effective date to
- card view
- list view

Actual search fields used by code:

- procedure code
- title
- description
- document number

Actual responsibility center filter values currently shown by the authoring screens:

- Operations
- Finance
- Human Resource
- Administrative

### 8.3 Open a procedure record

1. Find the procedure in the dashboard.
2. Select `Open Procedure`.

The procedure detail page shows:

- procedure code
- title and description
- current or historical status
- current controlling version or historical anchor version
- effectivity date
- version history
- relationship map
- lifecycle trail
- section lineage

Important note based on actual code logic:

- if a procedure is already historical and no longer has a current pointer, the page uses the latest historical version as the reference version for display

### 8.4 Open a procedure PDF

Where a file is available, the dashboard and detail pages provide a button to open the PDF.

Actual behavior:

- the system streams the PDF inline in the browser
- the application itself does not provide a separate download button

### 8.5 Review a specific version

1. Open a procedure record.
2. In the Version History list, select the version number.

The version detail page shows:

- procedure master status
- version lifecycle status
- version number
- document number
- change type
- effectivity date
- summary of change
- version relationships
- lifecycle trail
- section lineage
- full procedure history

### 8.6 Manage your profile

1. Open `Manage Profile`.
2. Review the displayed account information.
3. Update the office field if needed.
4. Enter a new password only if you want to change it.
5. Save the update.

Important note based on actual code logic:

- ID number, email, first name, last name, and middle name are currently displayed as read-only in the screen
- the practical editable fields are office and optional password

## 9. Administrator Guide

This section is for `admin` and `super_admin`.

### 9.1 Create a new procedure

Menu path:

- `PDMS Create`

Purpose:

- create the first registered version of a procedure

Step-by-step:

1. Open `PDMS Create`.
2. Enter the procedure code.
3. Enter the document number.
4. Enter the title and description.
5. Select the responsibility center.
6. Complete the Initial Registry Record section:
   - change type
   - registry state
   - effectivity date
   - summary of change
7. Select `Locate File` and choose a PDF from the configured server folders.
8. If needed, add an optional relationship to another current procedure version.
9. Submit the form.

Important notes based on actual code logic:

- the system accepts only PDF file paths
- the file must already exist in a configured server folder and be readable
- the initial version number is created as `1.0`
- the document number must be unique within `procedure_versions`
- the procedure code must be unique within `procedures`
- only `REGISTERED` and `EFFECTIVE` are valid authoring states in current screens
- only `EFFECTIVE` becomes the controlling version immediately

### 9.2 Register a revision

Where to do it:

- open a procedure
- select `Register Revision`

Purpose:

- add a new version to an existing active procedure

Step-by-step:

1. Open the active procedure record.
2. Select `Register Revision`.
3. Enter the new document number.
4. Select the change type.
5. Select the registry state.
6. Enter the effectivity date.
7. choose the PDF path through `Locate File`.
8. Enter the summary of change.
9. Review or adjust the relationship mapping.
10. Submit the form.

Important notes based on actual code logic:

- revisions are blocked for procedures already marked `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`
- if the new revision is `EFFECTIVE`, it becomes the new controlling version
- if the change type is `AMENDMENT`, the previous controlling version keeps status `EFFECTIVE`
- for other effective replacement revisions, the previous controlling version is marked `SUPERSEDED`
- amendment revisions require affected sections
- the system can automatically target the current version for amendment, partial revision, full revision, and reference registrations

### 9.3 Edit an active procedure

Where to do it:

- open a procedure
- select `Edit Procedure`

Purpose:

- update the procedure master record and the current controlling version details

Editable items in the current screen:

- title
- description
- responsibility center
- document number of the current version
- effectivity date of the current version
- summary of change of the current version
- current PDF file path

Important notes based on actual code logic:

- editing is blocked when the procedure status is `SUPERSEDED`, `RESCINDED`, or `ARCHIVED`
- the procedure code itself is not editable in the screen

### 9.4 Create a superseding procedure

Where to do it:

- open an eligible procedure
- select `Create Superseding Procedure`

Purpose:

- create a brand-new procedure that replaces another active procedure

Step-by-step:

1. Open an active procedure with an `EFFECTIVE` current version.
2. Select `Create Superseding Procedure`.
3. Enter the new procedure code and new document number.
4. Enter the new title and description.
5. Select the responsibility center.
6. Select `REGISTERED` or `EFFECTIVE`.
7. Enter the effectivity date.
8. Choose the PDF path.
9. Enter the supersession note.
10. Submit the form.

Actual results from code:

- a new procedure is created
- a `SUPERSEDES` relationship is created
- the old target version is marked `SUPERSEDED`
- the old procedure master is marked `SUPERSEDED`
- the old procedure loses its current version pointer

### 9.5 Rescind a procedure

Where to do it:

- open an eligible procedure
- select `Rescind Procedure`

Purpose:

- formally withdraw an active procedure

Step-by-step:

1. Open an active procedure with an `EFFECTIVE` current version.
2. Select `Rescind Procedure`.
3. Enter the rescission reason or note.
4. Confirm rescission.

Actual results from code:

- the current version is marked `RESCINDED`
- the procedure master is marked `RESCINDED`
- the current version pointer is cleared
- the procedure becomes historical and can no longer be edited or revised

### 9.6 Archive a historical version

Where to do it:

- open a version detail page
- select `Archive Historical Version` when the button is available

Eligibility in current code:

- the version is not the current controlling version
- the version status is already `SUPERSEDED` or `RESCINDED`

Actual result:

- the version status becomes `ARCHIVED`
- a lifecycle action is recorded

### 9.7 PDF file selection in admin screens

The current implementation does not use a browser upload field.

Actual behavior:

- the `Locate File` button opens a server-side PDF catalog
- the system lists readable PDF files from configured folders
- the chosen value saved in the database is the file path

Practical operating advice:

- make sure the approved PDF already exists in the server folders configured for the system before creating or updating a procedure

## 10. Super Administrator Guide

### 10.1 Manage users

Menu path:

- `Manage Users`

The Manage Users page allows the following:

- filter by office
- filter by status
- filter by role
- search by name, email, or ID number
- activate a user
- deactivate a user
- change a user between `user` and `admin`

Important notes based on actual code logic:

- only `super_admin` can open this page
- user role changes and status changes are logged in `activity_logs`

### 10.2 Review activity logs

Menu path:

- `Activity Logs`

The activity logs page allows a super administrator to:

- review recent actions
- search by keyword
- monitor user management actions
- monitor PDMS registration and maintenance actions that were logged by the application

The page currently displays:

- user name
- action
- description
- date and time

## 11. Record Management Workflow

This section explains how records move through the current system.

### 11.1 Procedure lifecycle summary

1. A procedure is created with its first version.
2. If that version is created as `EFFECTIVE`, it becomes the controlling version.
3. If it is created as `REGISTERED`, it is recorded but not controlling.
4. Revisions may be added while the procedure remains active.
5. A procedure may later be superseded by a new procedure or rescinded.
6. Older non-controlling versions may be archived if they are already superseded or rescinded.

### 11.2 Version states used by the current code

- `REGISTERED`
- `EFFECTIVE`
- `SUPERSEDED`
- `RESCINDED`
- `ARCHIVED`

### 11.3 Procedure master states used by the current code

- `ACTIVE`
- `SUPERSEDED`
- `RESCINDED`
- `ARCHIVED`

### 11.4 Controlling version rule

Important note based on actual code logic:

- only `EFFECTIVE` is eligible to become the current controlling version

### 11.5 Version numbering rule

Important note based on actual code logic:

- the first version is `1.0`
- `AMENDMENT` increases the minor version number, such as `1.0` to `1.1`
- other revision types increase the major version number, such as `1.1` to `2.0`

### 11.6 Relationship types used by the current code

- `AMENDS`
- `REVISES`
- `SUPERSEDES`
- `RESCINDS`
- `REFERENCES`
- `DERIVED_FROM`

### 11.7 Section-level lineage

The system can record affected sections for amendment-style relationships.

Important note based on actual code logic:

- affected sections are required for amendments
- section history entries are created only when the section history tables exist and the relationship type supports affected sections

## 12. Search, Filter, View, and File Actions

### 12.1 Search and filter functions

Implemented search and filter functions include:

- keyword search on the procedures dashboard
- responsibility center filter
- effectivity date range filter
- user management filter by office, status, and role
- activity log keyword search

### 12.2 View actions

Implemented record viewing actions include:

- open procedure
- open version detail
- open current PDF
- open version PDF

### 12.3 Upload, view, and download actions

Actual implementation status:

- upload: not implemented as a browser file upload; admins select an existing server PDF path
- view: implemented through inline PDF opening
- download: not implemented as a separate application button

## 13. Error Handling and Common Issues

### 13.1 Login fails

Possible reasons:

- wrong email or password
- account is still `inactive`

Action:

- verify the account was activated by a super administrator

### 13.2 Registration succeeds but an email-related message appears

Possible reason:

- mail environment variables are incomplete or invalid

Action:

- check mail configuration in the server environment

Important note based on actual code logic:

- the account may already have been inserted even if the registration page ends with an email failure message

### 13.3 The dashboard says PDMS tables are not available

Possible reason:

- the PDMS database tables or migrations are missing

Action:

- apply the required database migrations before using the procedures module

### 13.4 A procedure cannot be edited

Possible reason:

- the procedure is already historical

Current blocking states:

- `SUPERSEDED`
- `RESCINDED`
- `ARCHIVED`

### 13.5 A revision cannot be registered

Possible reasons:

- the procedure is historical
- the document number already exists
- the required PDF path is missing or unreadable
- affected sections were not entered for an amendment
- the selected relationship data is incomplete

### 13.6 A PDF cannot be opened

Possible reasons:

- the stored file path no longer exists
- the file is not readable by the server
- the path is not a PDF

### 13.7 The archive button does not appear for a version

Possible reasons:

- the version is still the current controlling version
- the version status is not yet `SUPERSEDED` or `RESCINDED`
- the user is not an admin or super admin

## 14. Glossary of Important Terms

### 14.1 Procedure

The master record that represents one SOP or procedural document across its versions.

### 14.2 Version

A specific registered edition of a procedure.

### 14.3 Controlling version

The operative version currently pointed to by the procedure master. In current code, only an `EFFECTIVE` version can be controlling.

### 14.4 Registered

A version that has already been recorded in the registry but is not yet the controlling version.

### 14.5 Effective

A version that is active for use and may be the controlling version.

### 14.6 Superseded

A procedure or version that has been replaced by another record.

### 14.7 Rescinded

A procedure or version that has been formally withdrawn.

### 14.8 Archived

A historical version retained for record purposes after it is no longer controlling and has already reached a historical state.

### 14.9 Relationship

A traceable link between one version and another, such as amendment, revision, supersession, or reference.

### 14.10 Section lineage

The recorded history of affected sections when amendment-style changes are entered.

### 14.11 Responsibility center

The office grouping used in the current procedure authoring and dashboard filters.

## 15. Practical Operating Reminders

- Use the Procedures dashboard as the main working page.
- Use PDMS Create for new procedures.
- Register revisions from the procedure detail page, not by creating duplicate procedures.
- Use supersession only when a new procedure replaces another procedure.
- Use rescission only when a procedure is being formally withdrawn.
- Do not expect deleted records; the current application preserves history.
- Keep approved PDF files in the configured server folders before authoring records.

## 16. Source Basis for This Manual

This manual was prepared by checking the current implementation in:

- `core/App.php`
- `core/Middleware.php`
- `config/config.php`
- `app/controllers/Procedures.php`
- `app/controllers/Users.php`
- `app/controllers/ActivityLogs.php`
- `app/models/ProcedureAuthoringService.php`
- `app/models/ProcedureReadModel.php`
- `app/models/User.php`
- `app/views/procedures/*.php`
- `app/views/users/*.php`
- `app/views/activity_logs/index.php`
- `database/migrations/*.sql`
- `CURRENT_ARCHITECTURE_STATE_2026-03-13.md`
- `DATABASE_REFERENCE.md`

## 17. Final Note

This manual is intended to be understandable to office users, administrators, and records personnel while staying faithful to the current code.

If future development changes the login flow, procedure lifecycle actions, PDF handling, or user management rules, this manual should be updated together with the code.
