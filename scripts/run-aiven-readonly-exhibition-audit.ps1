[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# Connection metadata is intentionally non-secret. Passwords are never stored,
# echoed, passed as arguments, or written to a file by this runner.
$AivenHost = 'mysql-200a9449-nazmusshakib335-1a15.h.aivencloud.com'
$AivenPort = 15419
$AivenDatabase = 'defaultdb'
$AivenUser = 'avnadmin'
$CaCertificatePath = 'D:\CV\ca.pem'
$MySqlBin = 'D:\CV\mysql-26.7.0-winx64\mysql-26.7.0-winx64\bin'
$MySqlClientPath = Join-Path $MySqlBin 'mysql.exe'
$MySqlDumpPath = Join-Path $MySqlBin 'mysqldump.exe'
$BackupDirectory = 'C:\tmp\ai-smart-campus-private-aiven-backups'
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$ReportPath = Join-Path $ProjectRoot 'EXHIBITION_AIVEN_AUDIT_REPORT.md'

function Assert-SafeTarget {
    if ($AivenHost -in @('localhost', '127.0.0.1', '::1') -or $AivenPort -eq 3307) {
        throw 'Refusing local MariaDB or port 3307. No connection was attempted.'
    }
    foreach ($path in @($CaCertificatePath, $MySqlClientPath, $MySqlDumpPath)) {
        if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
            throw "Required file is unavailable: $path"
        }
    }
    $version = & $MySqlClientPath --version
    if ($LASTEXITCODE -ne 0 -or $version -match 'MariaDB') {
        throw 'A compatible Oracle MySQL client is required; the supplied client was not accepted.'
    }
}

function Protect-BackupDirectory {
    New-Item -ItemType Directory -Force -Path $BackupDirectory | Out-Null
    & icacls $BackupDirectory /inheritance:r /grant:r "${env:USERNAME}:(OI)(CI)F" | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Could not apply private folder permissions to the backup directory.' }
}

function Get-AuditValue([string[]] $lines, [string] $key) {
    $prefix = "$key`t"
    $line = $lines | Where-Object { $_.StartsWith($prefix) } | Select-Object -First 1
    if ($null -eq $line) { return 'Unavailable' }
    return $line.Substring($prefix.Length).Trim()
}

Assert-SafeTarget
Protect-BackupDirectory

$common = @(
    '--protocol=TCP', "--host=$AivenHost", "--port=$AivenPort", "--user=$AivenUser", "--database=$AivenDatabase",
    '--ssl-mode=VERIFY_IDENTITY', "--ssl-ca=$CaCertificatePath", '--batch', '--skip-column-names', '--raw'
)

$auditSql = @'
SELECT 'DATABASE_NAME', DATABASE();
SHOW STATUS LIKE 'Ssl_cipher';
SELECT 'TABLE_COUNT', COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';
SELECT 'DATABASE_SIZE_BYTES', COALESCE(SUM(data_length + index_length), 0) FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT CONCAT('USER_', role, '_', approval_status), COUNT(*) FROM users GROUP BY role, approval_status ORDER BY role, approval_status;
SELECT 'ADMIN_ACCOUNTS', COUNT(*) FROM users WHERE role = 'admin';
SELECT 'PENDING_REGISTRATIONS', COUNT(*) FROM users WHERE role IN ('student','faculty') AND approval_status = 'pending';
SELECT 'DEMO_STUDENTS', COUNT(*) FROM users WHERE role = 'student' AND email REGEXP '^student[1-8][1-5]@gmail\\.com$';
SELECT 'DEMO_STUDENT_COHORTS', COUNT(*) FROM (SELECT s.current_semester FROM users u JOIN students s ON s.user_id = u.id WHERE u.role = 'student' AND u.email REGEXP '^student[1-8][1-5]@gmail\\.com$' GROUP BY s.current_semester HAVING COUNT(*) = 5) AS cohorts;
SELECT 'DEMO_FACULTY', COUNT(*) FROM users WHERE role = 'faculty' AND email REGEXP '^spring26\\.faculty\\.[0-9]{2}@example\\.edu$';
SELECT 'OTHER_ACCOUNTS', COUNT(*) FROM users WHERE NOT (role = 'student' AND email REGEXP '^student[1-8][1-5]@gmail\\.com$') AND NOT (role = 'faculty' AND email REGEXP '^spring26\\.faculty\\.[0-9]{2}@example\\.edu$');
SELECT 'DEMO_ENROLLMENTS', COUNT(*) FROM course_enrollments ce JOIN students s ON s.id = ce.student_id JOIN users u ON u.id = s.user_id WHERE u.email REGEXP '^student[1-8][1-5]@gmail\\.com$';
SELECT 'DEMO_ATTENDANCE', COUNT(*) FROM attendance_records ar JOIN students s ON s.id = ar.student_id JOIN users u ON u.id = s.user_id WHERE u.email REGEXP '^student[1-8][1-5]@gmail\\.com$';
SELECT 'DEMO_ACADEMIC_RECORDS', COUNT(*) FROM academic_records ar JOIN students s ON s.id = ar.student_id JOIN users u ON u.id = s.user_id WHERE u.email REGEXP '^student[1-8][1-5]@gmail\\.com$';
SELECT 'DEMO_PERFORMANCE_METRICS', COUNT(*) FROM performance_metrics pm JOIN students s ON s.id = pm.student_id JOIN users u ON u.id = s.user_id WHERE u.email REGEXP '^student[1-8][1-5]@gmail\\.com$';
SELECT 'OFFICIAL_SPRING_ROUTINE_ROWS', COUNT(*) FROM course_schedules WHERE semester = 'Spring' AND year = 2026 AND section REGEXP '^[1-8]A$' AND source_reference LIKE 'Spring_2026_A_Sections_Official_Routine.csv%';
SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected', 'Max_used_connections', 'Slow_queries', 'Questions', 'Uptime');
'@

Write-Host 'Password prompt 1 of 2: read-only TLS audit connection.' -ForegroundColor Cyan
$auditOutput = @(& $MySqlClientPath @common '-p' "--execute=$auditSql")
if ($LASTEXITCODE -ne 0) { throw 'TLS-verified read-only audit connection failed. No backup was attempted.' }

$databaseName = Get-AuditValue $auditOutput 'DATABASE_NAME'
$sslCipher = Get-AuditValue $auditOutput 'Ssl_cipher'
if ($databaseName -ne $AivenDatabase -or $sslCipher -eq 'Unavailable' -or [string]::IsNullOrWhiteSpace($sslCipher)) {
    throw 'Target identity or TLS cipher verification failed. No backup was attempted.'
}

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupPath = Join-Path $BackupDirectory ("$AivenDatabase-exhibition-readonly-$stamp.sql")
$dumpArgs = @(
    '--protocol=TCP', "--host=$AivenHost", "--port=$AivenPort", "--user=$AivenUser",
    '--ssl-mode=VERIFY_IDENTITY', "--ssl-ca=$CaCertificatePath", '--single-transaction', '--quick', '--routines', '--events', '--no-tablespaces', '--set-gtid-purged=OFF', '-p', "--result-file=$backupPath", '--databases', $AivenDatabase
)

Write-Host 'Password prompt 2 of 2: separate native mysqldump process.' -ForegroundColor Cyan
& $MySqlDumpPath @dumpArgs
if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $backupPath) -or (Get-Item -LiteralPath $backupPath).Length -le 0) {
    if (Test-Path -LiteralPath $backupPath) { Remove-Item -LiteralPath $backupPath -Force }
    throw 'Logical backup failed verification and was removed.'
}

$expectedTables = [int](Get-AuditValue $auditOutput 'TABLE_COUNT')
$backupTableDefinitions = (Select-String -LiteralPath $backupPath -Pattern '^CREATE TABLE ' -AllMatches).Count
if ($expectedTables -le 0 -or $backupTableDefinitions -ne $expectedTables) {
    throw 'Backup table-definition verification failed; the backup is retained for manual inspection but must not be used until reviewed.'
}
$backupItem = Get-Item -LiteralPath $backupPath
$backupHash = (Get-FileHash -LiteralPath $backupPath -Algorithm SHA256).Hash

$roleLines = $auditOutput | Where-Object { $_ -match '^USER_' } | ForEach-Object {
    $parts = $_ -split "`t", 2
    "| $($parts[0].Substring(5).Replace('_', ' / ')) | $($parts[1]) |"
}
$statusLines = @(
    "| TLS cipher negotiated | $sslCipher |",
    "| Target database | $databaseName |",
    "| Base tables | $(Get-AuditValue $auditOutput 'TABLE_COUNT') |",
    "| Approximate schema size (bytes) | $(Get-AuditValue $auditOutput 'DATABASE_SIZE_BYTES') |",
    "| Synthetic students | $(Get-AuditValue $auditOutput 'DEMO_STUDENTS') |",
    "| Complete five-student cohorts | $(Get-AuditValue $auditOutput 'DEMO_STUDENT_COHORTS') |",
    "| Synthetic faculty | $(Get-AuditValue $auditOutput 'DEMO_FACULTY') |",
    "| Other accounts | $(Get-AuditValue $auditOutput 'OTHER_ACCOUNTS') |",
    "| Pending registrations | $(Get-AuditValue $auditOutput 'PENDING_REGISTRATIONS') |",
    "| Admin accounts | $(Get-AuditValue $auditOutput 'ADMIN_ACCOUNTS') |",
    "| Demo enrollments | $(Get-AuditValue $auditOutput 'DEMO_ENROLLMENTS') |",
    "| Demo attendance records | $(Get-AuditValue $auditOutput 'DEMO_ATTENDANCE') |",
    "| Demo academic records | $(Get-AuditValue $auditOutput 'DEMO_ACADEMIC_RECORDS') |",
    "| Demo performance metrics | $(Get-AuditValue $auditOutput 'DEMO_PERFORMANCE_METRICS') |",
    "| Official Spring 2026 routine rows | $(Get-AuditValue $auditOutput 'OFFICIAL_SPRING_ROUTINE_ROWS') |"
)

$report = @(
    '# Exhibition Aiven Audit Report', '',
    '## Status', '',
    'Completed using a TLS-verified, read-only Aiven MySQL connection. No database writes, account changes, migrations, seeding, deployment, or local-MariaDB access were performed.', '',
    '## Backup verification', '',
    '| Check | Result |', '| --- | --- |',
    "| Private location | $BackupDirectory (outside repository) |",
    "| Backup filename | $($backupItem.Name) |",
    "| Backup size (bytes) | $($backupItem.Length) |",
    "| SHA-256 | $backupHash |",
    "| CREATE TABLE definitions / expected base tables | $backupTableDefinitions / $expectedTables |", '',
    '## Sanitized account and dependency counts', '',
    '| Category | Count / value |', '| --- | --- |'
) + $statusLines + @('', '### Users by role and approval status', '', '| Role / approval | Count |', '| --- | ---: |') + $roleLines + @(
    '', '## Database health observations', '',
    '| Status variable | Value |', '| --- | --- |',
    "| Threads connected | $(Get-AuditValue $auditOutput 'Threads_connected') |",
    "| Max used connections | $(Get-AuditValue $auditOutput 'Max_used_connections') |",
    "| Slow queries | $(Get-AuditValue $auditOutput 'Slow_queries') |",
    "| Questions | $(Get-AuditValue $auditOutput 'Questions') |",
    "| Uptime seconds | $(Get-AuditValue $auditOutput 'Uptime') |", '',
    'These are point-in-time read-only observations, not a load test.', '',
    '## Reversible cleanup recommendation', '',
    'The expected exhibition cohort is 40 synthetic students in eight five-student cohorts plus 8 synthetic faculty. If the observed counts are complete, none of those accounts is unnecessary for a full eight-semester demonstration. Keep admins and all non-pattern accounts unchanged. Any future reduction should first disable specifically approved synthetic accounts, preserve dependent academic rows, verify role/dashboard behavior, and retain this backup for rollback.', '',
    '## Remaining exhibition risks', '',
    '- Render cold starts, application memory, and QR/mobile path are not measured by this database audit.',
    '- A verified backup is a logical snapshot; restore testing requires a separate isolated environment and approval.',
    '- No real-user classification or cleanup has been performed.', '',
    '## Actions requiring approval', '',
    '- Any account disablement, data cleanup, migration, seed, restore drill, Render change, GitHub push, or deployment.',
    '- Any connection-setting change or performance load test beyond these read-only point-in-time queries.'
)

Set-Content -LiteralPath $ReportPath -Value ($report -join [Environment]::NewLine) -Encoding utf8
Write-Output 'AIVEN_READONLY_AUDIT=OK'
Write-Output "AIVEN_BACKUP_FILE=$($backupItem.Name)"
Write-Output "AIVEN_REPORT=$ReportPath"
