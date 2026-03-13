# Phase D Local Commands

## Purpose

These are operator-ready local commands for applying the Phase D hard cutover in the XAMPP-based SOPWeb development environment.

Assumptions taken from the live repo as of March 13, 2026:

- MariaDB client binaries live under `C:\xampp\mysql\bin`
- the app database is `sopweb`
- the default local database user is `root`
- the current code already includes the Phase D application changes

## One-Step Helper

From the repo root:

```powershell
powershell -ExecutionPolicy Bypass -File .\database\migrations\apply_phase_d_local.ps1 -BackupDir .\database\backups
```

If the local database password is non-empty:

```powershell
powershell -ExecutionPolicy Bypass -File .\database\migrations\apply_phase_d_local.ps1 -DbPass "your-password" -BackupDir .\database\backups
```

## Manual Command Sequence

Preflight:

```powershell
Get-Content .\database\migrations\phase_d_cutover_preflight.sql | C:\xampp\mysql\bin\mysql.exe -h localhost -u root sopweb
```

Apply the migration:

```powershell
Get-Content .\database\migrations\2026_03_13_000005_drop_approval_compatibility_columns.sql | C:\xampp\mysql\bin\mysql.exe -h localhost -u root sopweb
```

Run regressions:

```powershell
php .\tests\run_regressions.php
```

## Optional Backup Command

```powershell
C:\xampp\mysql\bin\mysqldump.exe -h localhost -u root --routines --triggers --single-transaction sopweb > .\database\backups\sopweb_phase_d_manual.sql
```

If the local database password is non-empty, add `--password=your-password` to the MariaDB client commands above.
