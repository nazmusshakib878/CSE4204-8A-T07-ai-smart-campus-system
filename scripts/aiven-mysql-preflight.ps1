[CmdletBinding()]
param(
    [Parameter(Mandatory)] [string] $AivenHost,
    [Parameter(Mandatory)] [ValidateRange(1, 65535)] [int] $AivenPort,
    [Parameter(Mandatory)] [string] $AivenDatabase,
    [Parameter(Mandatory)] [string] $AivenUser,
    [Parameter(Mandatory)] [string] $CaCertificatePath,
    [Parameter(Mandatory)] [string] $MySqlClientPath,
    [string] $BackupDirectory = 'database/backups/aiven-pre-migration',
    [switch] $CreateBackup
)

$ErrorActionPreference = 'Stop'
$mysql = $MySqlClientPath
$mysqldump = Join-Path (Split-Path -Parent $mysql) 'mysqldump.exe'

if ($AivenHost -in @('localhost', '127.0.0.1', '::1') -or $AivenPort -eq 3307) {
    throw 'Refusing local MariaDB target. This script is for the remote Aiven database only.'
}
if (-not (Test-Path -LiteralPath $mysql)) {
    throw 'A MySQL 8+ mysql.exe client is required. The XAMPP MariaDB client is not compatible with Aiven caching_sha2_password accounts.'
}
$clientVersion = & $mysql --version
$versionMatch = [regex]::Match($clientVersion, '(?:Distrib|Ver)\s+(\d+)\.')
if ($LASTEXITCODE -ne 0 -or $clientVersion -match 'MariaDB' -or -not $versionMatch.Success -or [int] $versionMatch.Groups[1].Value -lt 8) {
    throw 'The supplied client is not Oracle MySQL 8+. Supply mysql.exe from an official MySQL 8.0+ package.'
}
if ($CreateBackup -and -not (Test-Path -LiteralPath $mysqldump)) {
    throw 'The matching MySQL 8+ mysqldump.exe is required only when -CreateBackup is used.'
}
if (-not (Test-Path -LiteralPath $CaCertificatePath)) {
    throw 'The Aiven CA certificate file was not found.'
}

try {
    # Let the Oracle client collect the secret directly. This avoids command-line,
    # PowerShell-quoting, and process-environment handling of special characters.
    $clientCommon = @('--protocol=TCP', "--host=$AivenHost", "--port=$AivenPort", "--user=$AivenUser", "--database=$AivenDatabase", "--ssl-mode=VERIFY_IDENTITY", "--ssl-ca=$CaCertificatePath", '--password', '--batch', '--skip-column-names')
    $dumpCommon = @('--protocol=TCP', "--host=$AivenHost", "--port=$AivenPort", "--user=$AivenUser", "--ssl-mode=VERIFY_IDENTITY", "--ssl-ca=$CaCertificatePath", '--password', '--single-transaction', '--routines', '--events', '--no-tablespaces')

    # Read-only target and TLS validation. No schema, data, or privilege changes are made.
    $probe = @(& $mysql @clientCommon --execute="SELECT 1 AS connected; SHOW STATUS LIKE 'Ssl_cipher'; SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")
    $probeText = $probe -join "`n"
    if ($LASTEXITCODE -ne 0 -or $probeText -notmatch '(?m)^1\s*$') { throw 'Remote database read-only probe failed.' }
    if ($probeText -notmatch '(?m)^Ssl_cipher\s+\S+') { throw 'TLS verification probe failed.' }
    $tableCount = @($probe | Where-Object { $_ -match '^\d+\s*$' } | Select-Object -Last 1)
    if ($tableCount.Count -ne 1) { throw 'Target inventory probe failed.' }

    Write-Output 'AIVEN_TARGET_PREFLIGHT=OK'
    Write-Output ("AIVEN_TARGET_TABLES={0}" -f $tableCount[0].Trim())

    if ($CreateBackup) {
        New-Item -ItemType Directory -Force -Path $BackupDirectory | Out-Null
        $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
        $backupPath = Join-Path $BackupDirectory ("{0}-pre-migration-{1}.sql" -f $AivenDatabase, $stamp)
        # Let mysqldump write its native byte stream directly, avoiding
        # PowerShell's text encoding and preserving a restorable SQL file.
        & $mysqldump @dumpCommon "--result-file=$backupPath" $AivenDatabase
        if ($LASTEXITCODE -ne 0 -or (Get-Item -LiteralPath $backupPath).Length -eq 0) { throw 'Backup creation failed.' }
        Write-Output 'AIVEN_TARGET_BACKUP=OK'
    }
} finally {
}
