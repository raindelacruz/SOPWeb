param(
    [string]$MysqlBin = 'C:\xampp\mysql\bin',
    [string]$DbHost = 'localhost',
    [string]$DbName = 'sopweb',
    [string]$DbUser = 'root',
    [string]$DbPass = '',
    [string]$BackupDir = '',
    [switch]$SkipConfirmation
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$mysqlExe = Join-Path $MysqlBin 'mysql.exe'
$mysqldumpExe = Join-Path $MysqlBin 'mysqldump.exe'
$preflightSql = Join-Path $PSScriptRoot 'phase_d_cutover_preflight.sql'
$migrationSql = Join-Path $PSScriptRoot '2026_03_13_000005_drop_approval_compatibility_columns.sql'

if (-not (Test-Path $mysqlExe)) {
    throw "mysql.exe not found at $mysqlExe"
}

if (-not (Test-Path $preflightSql)) {
    throw "Preflight SQL not found at $preflightSql"
}

if (-not (Test-Path $migrationSql)) {
    throw "Migration SQL not found at $migrationSql"
}

function Invoke-MysqlFile {
    param(
        [string]$SqlFile
    )

    $arguments = @(
        '-h', $DbHost,
        '-u', $DbUser
    )

    if ($DbPass -ne '') {
        $arguments += "--password=$DbPass"
    }

    $arguments += $DbName

    Get-Content $SqlFile | & $mysqlExe @arguments

    if ($LASTEXITCODE -ne 0) {
        throw "mysql exited with code $LASTEXITCODE while processing $SqlFile"
    }
}

function Invoke-MysqldumpBackup {
    if ($BackupDir -eq '') {
        Write-Host 'Backup step skipped because -BackupDir was not provided.'
        return
    }

    if (-not (Test-Path $mysqldumpExe)) {
        throw "mysqldump.exe not found at $mysqldumpExe"
    }

    New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null
    $timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
    $backupPath = Join-Path $BackupDir "${DbName}_phase_d_${timestamp}.sql"

    $arguments = @(
        '-h', $DbHost,
        '-u', $DbUser
    )

    if ($DbPass -ne '') {
        $arguments += "--password=$DbPass"
    }

    $arguments += @(
        '--routines',
        '--triggers',
        '--single-transaction',
        $DbName
    )

    & $mysqldumpExe @arguments > $backupPath

    if ($LASTEXITCODE -ne 0) {
        throw "mysqldump exited with code $LASTEXITCODE while creating $backupPath"
    }

    Write-Host "Backup created: $backupPath"
}

Write-Host 'Phase D local rollout starting...'
Write-Host "Repo root: $repoRoot"
Write-Host "Database: $DbName on $DbHost"

Invoke-MysqldumpBackup

Write-Host ''
Write-Host 'Running preflight checks...'
Invoke-MysqlFile -SqlFile $preflightSql

if (-not $SkipConfirmation) {
    $confirmation = Read-Host 'Apply Phase D migration now? Type APPLY to continue'
    if ($confirmation -ne 'APPLY') {
        throw 'Migration cancelled.'
    }
}

Write-Host ''
Write-Host 'Applying Phase D migration...'
Invoke-MysqlFile -SqlFile $migrationSql

Write-Host ''
Write-Host 'Running lightweight regressions...'
Push-Location $repoRoot
try {
    & php 'tests/run_regressions.php'
    if ($LASTEXITCODE -ne 0) {
        throw "Regression bundle failed with exit code $LASTEXITCODE"
    }
} finally {
    Pop-Location
}

Write-Host ''
Write-Host 'Phase D local rollout completed successfully.'
