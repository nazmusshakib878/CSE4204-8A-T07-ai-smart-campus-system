[CmdletBinding()]
param()

# One-time, operator-run bootstrap for a missing production administrator.
# It never prints passwords, connection secrets, identity values, or SQL.
# It refuses local targets and only connects to the approved Aiven endpoint.

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$AivenHost = 'mysql-200a9449-nazmusshakib335-1a15.h.aivencloud.com'
$AivenPort = 15419
$AivenDatabase = 'defaultdb'
$AivenUser = 'avnadmin'
$CaCertificatePath = 'D:\CV\ca.pem'
$MySqlClientPath = 'D:\CV\mysql-26.7.0-winx64\mysql-26.7.0-winx64\bin\mysql.exe'
$PhpPath = 'C:\xampp\php\php.exe'
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$ArtisanPath = Join-Path $ProjectRoot 'backend\artisan'
$LaravelHasherPath = Join-Path $PSScriptRoot 'laravel-hash-password.php'

function Assert-SafePrerequisites {
    if ($AivenHost -in @('localhost', '127.0.0.1', '::1') -or $AivenPort -eq 3307) {
        throw 'Refusing local MariaDB or port 3307.'
    }
    foreach ($path in @($CaCertificatePath, $MySqlClientPath, $PhpPath, $ArtisanPath, $LaravelHasherPath)) {
        if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
            throw "Required file is unavailable: $path"
        }
    }
    $version = & $MySqlClientPath --version
    if ($LASTEXITCODE -ne 0 -or $version -match 'MariaDB') {
        throw 'A compatible Oracle MySQL client is required.'
    }
}

function Invoke-AivenQuery([string] $Sql) {
    $common = @(
        '--protocol=TCP', "--host=$AivenHost", "--port=$AivenPort", "--user=$AivenUser", "--database=$AivenDatabase",
        '--ssl-mode=VERIFY_IDENTITY', "--ssl-ca=$CaCertificatePath", '--batch', '--skip-column-names', '--raw', '-p', "--execute=$Sql"
    )
    & $MySqlClientPath @common
    if ($LASTEXITCODE -ne 0) { throw 'TLS-verified Aiven MySQL command failed.' }
}

function Read-Required([string] $Prompt) {
    $value = (Read-Host $Prompt).Trim()
    if ([string]::IsNullOrWhiteSpace($value)) { throw "$Prompt is required." }
    return $value
}

function Convert-SecureStringToPlainText([Security.SecureString] $SecureValue) {
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecureValue)
    try { return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}

function Quote-Sql([string] $Value) {
    # Inputs are validated below; standard SQL quote escaping is retained for defense in depth.
    return "'" + $Value.Replace("'", "''") + "'"
}

Assert-SafePrerequisites

# This is deliberately an aggregate-only preflight: it reveals no account identity.
Write-Host 'Password prompt 1 of 2: TLS-verified, read-only Admin preflight.' -ForegroundColor Cyan
$preflightSql = @'
SELECT DATABASE(),
       SUM(role = 'admin') AS total_admins,
       SUM(role = 'admin' AND approval_status = 'approved') AS approved_admins,
       SUM(role = 'admin' AND approval_status <> 'approved') AS nonapproved_admins
FROM users;
'@
$preflight = @(Invoke-AivenQuery $preflightSql)
if ($preflight.Count -ne 1) { throw 'Unexpected preflight result; no change was made.' }
$parts = $preflight[0] -split "`t"
if ($parts.Count -ne 4 -or $parts[0] -ne $AivenDatabase) { throw 'Database identity verification failed; no change was made.' }
$totalAdmins = [int]$parts[1]
$approvedAdmins = [int]$parts[2]

if ($totalAdmins -gt 0) {
    Write-Output "ADMIN_BOOTSTRAP=SKIPPED_EXISTING_ADMIN"
    Write-Output "ADMIN_APPROVED_COUNT=$approvedAdmins"
    Write-Output 'No account was created or changed. Existing Admin login must be verified by its authorized owner.'
    exit 0
}

Write-Host 'No Admin was found. Enter the authorized exhibition Admin identity locally; values are not logged.' -ForegroundColor Yellow
$name = Read-Required 'Full name'
$email = (Read-Required 'Email').ToLowerInvariant()
$phone = Read-Required 'Phone (10-15 digits, optional leading +)'
$adminId = (Read-Required 'Admin ID (ADM-123)').ToUpperInvariant()

if ($name.Length -lt 2 -or $name.Length -gt 255 -or $name -match "[\r\n]") { throw 'Name format is invalid.' }
if ($email -notmatch '^[^\s@]+@[^\s@]+\.[^\s@]+$' -or $email.Length -gt 255) { throw 'Email format is invalid.' }
if ($phone -notmatch '^\+?[0-9]{10,15}$') { throw 'Phone format is invalid.' }
if ($adminId -notmatch '^ADM-\d{3}$') { throw 'Admin ID must use ADM-123 format.' }

$password = Read-Host 'New Admin password (minimum 8; upper/lowercase, digit, symbol)' -AsSecureString
$confirmation = Read-Host 'Confirm new Admin password' -AsSecureString
$plainPassword = Convert-SecureStringToPlainText $password
$plainConfirmation = Convert-SecureStringToPlainText $confirmation
try {
    if ($plainPassword -cne $plainConfirmation) { throw 'Password confirmation does not match.' }
    if ($plainPassword.Length -lt 8 -or $plainPassword -notmatch '[a-z]' -or $plainPassword -notmatch '[A-Z]' -or $plainPassword -notmatch '\d' -or $plainPassword -notmatch '[^A-Za-z0-9]') {
        throw 'Password does not meet the existing Laravel password policy.'
    }

    # This local helper boots Laravel and verifies Hash::make with Hash::check before it emits a hash.
    # It avoids `artisan tinker`, which can fail when PsySH history is not writable.
    $env:AI_SMART_CAMPUS_BOOTSTRAP_PASSWORD = $plainPassword
    $passwordHash = @(& $PhpPath $LaravelHasherPath 2>&1) | Where-Object { $_ -match '^\$(2[aby]|argon2)' } | Select-Object -Last 1
    if ([string]::IsNullOrWhiteSpace($passwordHash)) { throw 'Laravel password hashing and local verification failed; no production query was attempted.' }

    $nameSql = Quote-Sql $name
    $emailSql = Quote-Sql $email
    $phoneSql = Quote-Sql $phone
    $adminIdSql = Quote-Sql $adminId
    $hashSql = Quote-Sql $passwordHash

    # GET_LOCK keeps the check-and-insert atomic across concurrent bootstrap attempts.
    # The INSERT only occurs when no Admin exists and neither unique identity value is in use.
    $writeSql = @"
SELECT GET_LOCK('ai_smart_campus_admin_bootstrap', 10);
START TRANSACTION;
INSERT INTO users (name, email, phone, password, role, admin_id, approval_status, created_at, updated_at)
SELECT $nameSql, $emailSql, $phoneSql, $hashSql, 'admin', $adminIdSql, 'approved', UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin')
  AND NOT EXISTS (SELECT 1 FROM users WHERE email = $emailSql OR admin_id = $adminIdSql);
SELECT ROW_COUNT();
COMMIT;
SELECT RELEASE_LOCK('ai_smart_campus_admin_bootstrap');
SELECT SUM(role = 'admin'), SUM(role = 'admin' AND approval_status = 'approved')
FROM users;
"@
    Write-Host 'Password prompt 2 of 2: TLS-verified, transactional Admin bootstrap.' -ForegroundColor Cyan
    $result = @(Invoke-AivenQuery $writeSql)
    if ($result.Count -lt 4 -or $result[-1] -notmatch '^1\s+1$') {
        throw 'Admin creation verification failed or an identity conflict occurred. The transaction committed no new Admin.'
    }

    Write-Output 'ADMIN_BOOTSTRAP=CREATED'
    Write-Output 'ADMIN_STATUS=APPROVED'
    Write-Output 'ADMIN_AUTHORIZATION=READY'
    Write-Output 'No existing user, student, faculty, enrollment, attendance, result, or approval status was changed.'
}
finally {
    Remove-Item Env:\AI_SMART_CAMPUS_BOOTSTRAP_PASSWORD -ErrorAction SilentlyContinue
    $plainPassword = $null
    $plainConfirmation = $null
}
