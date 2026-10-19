[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

# This is intentionally read-only. It verifies the same Aiven target used by
# production without printing a password, DSN, user rows, or other private data.
$aivenHost = 'mysql-200a9449-nazmusshakib335-1a15.h.aivencloud.com'
$aivenPort = 15419
$aivenDatabase = 'defaultdb'
$aivenUser = 'avnadmin'
$caCertificatePath = 'D:\CV\ca.pem'
$projectRoot = Split-Path -Parent $PSScriptRoot
$backendPath = Join-Path $projectRoot 'backend'

if ($aivenHost -in @('localhost', '127.0.0.1', '::1') -or $aivenPort -eq 3307) {
    throw 'Refusing a local MariaDB target.'
}
if (-not (Test-Path -LiteralPath $caCertificatePath)) {
    throw 'Aiven CA certificate was not found.'
}
if (-not (Test-Path -LiteralPath (Join-Path $backendPath 'artisan'))) {
    throw 'Laravel artisan was not found.'
}

$environmentNames = @(
    'APP_ENV', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME',
    'DB_PASSWORD', 'DB_URL', 'DB_SOCKET', 'MYSQL_ATTR_SSL_CA',
    'AIVEN_EXPECTED_DB_HOST', 'AIVEN_EXPECTED_DB_PORT',
    'AIVEN_EXPECTED_DB_DATABASE', 'AIVEN_EXPECTED_DB_USERNAME'
)
$previousEnvironment = @{}
foreach ($name in $environmentNames) {
    $previousEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

$securePassword = Read-Host 'Aiven MySQL password (masked; never logged)' -AsSecureString
$passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try {
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
}

try {
    [Environment]::SetEnvironmentVariable('APP_ENV', 'production', 'Process')
    [Environment]::SetEnvironmentVariable('DB_CONNECTION', 'mysql', 'Process')
    [Environment]::SetEnvironmentVariable('DB_HOST', $aivenHost, 'Process')
    [Environment]::SetEnvironmentVariable('DB_PORT', $aivenPort.ToString(), 'Process')
    [Environment]::SetEnvironmentVariable('DB_DATABASE', $aivenDatabase, 'Process')
    [Environment]::SetEnvironmentVariable('DB_USERNAME', $aivenUser, 'Process')
    [Environment]::SetEnvironmentVariable('DB_PASSWORD', $password, 'Process')
    [Environment]::SetEnvironmentVariable('DB_URL', $null, 'Process')
    [Environment]::SetEnvironmentVariable('DB_SOCKET', $null, 'Process')
    [Environment]::SetEnvironmentVariable('MYSQL_ATTR_SSL_CA', (Resolve-Path -LiteralPath $caCertificatePath), 'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_HOST', $aivenHost, 'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_PORT', $aivenPort.ToString(), 'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_DATABASE', $aivenDatabase, 'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_USERNAME', $aivenUser, 'Process')

    Push-Location $backendPath
    try {
        php -d variables_order=EGPCS artisan config:clear --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Could not clear local Laravel configuration.' }

        php -d variables_order=EGPCS artisan database:startup-health --config-check --critical-schema --table-count --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'AIVEN_READONLY_API_DIAGNOSTIC=FAIL' }

        # This reads Laravel's migration repository only; it does not run migrations.
        php -d variables_order=EGPCS artisan migrate:status --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Could not read migration status.' }
    } finally {
        Pop-Location
    }
} finally {
    foreach ($name in $environmentNames) {
        [Environment]::SetEnvironmentVariable($name, $previousEnvironment[$name], 'Process')
    }
    Remove-Variable password -ErrorAction SilentlyContinue
}

Write-Output 'AIVEN_READONLY_API_DIAGNOSTIC=OK'
