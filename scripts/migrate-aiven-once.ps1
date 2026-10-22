[CmdletBinding()]
param(
    [Parameter(Mandatory)] [string] $AivenHost,
    [Parameter(Mandatory)] [ValidateRange(1, 65535)] [int] $AivenPort,
    [Parameter(Mandatory)] [string] $AivenDatabase,
    [Parameter(Mandatory)] [string] $AivenUser,
    [Parameter(Mandatory)] [string] $CaCertificatePath,
    [Parameter(Mandatory)] [string] $PreMigrationBackupPath,
    [switch] $ConnectionCheckOnly,
    [switch] $ExecuteAivenMigration
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$backendPath = Join-Path $projectRoot 'backend'

if ($ConnectionCheckOnly -eq $ExecuteAivenMigration) {
    throw 'Choose exactly one mode: -ConnectionCheckOnly or -ExecuteAivenMigration.'
}
if ($AivenHost -in @('localhost', '127.0.0.1', '::1') -or $AivenPort -eq 3307) {
    throw 'Refusing local MariaDB target. This script is for the remote Aiven database only.'
}
if (-not (Test-Path -LiteralPath $CaCertificatePath)) {
    throw 'The Aiven CA certificate file was not found.'
}
if (-not (Test-Path -LiteralPath $PreMigrationBackupPath) -or (Get-Item -LiteralPath $PreMigrationBackupPath).Length -eq 0) {
    throw 'A non-empty pre-migration backup is required before migration.'
}
if (-not (Test-Path -LiteralPath (Join-Path $backendPath 'artisan'))) {
    throw 'Laravel backend/artisan was not found.'
}

$driverCheck = & php -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    throw 'The local PHP CLI does not have pdo_mysql enabled.'
}

$environmentNames = @('APP_ENV', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_URL', 'DB_SOCKET', 'MYSQL_ATTR_SSL_CA', 'AIVEN_EXPECTED_DB_HOST', 'AIVEN_EXPECTED_DB_PORT', 'AIVEN_EXPECTED_DB_DATABASE', 'AIVEN_EXPECTED_DB_USERNAME')
$previousEnvironment = @{}
foreach ($name in $environmentNames) {
    $previousEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

$securePassword = Read-Host 'Aiven MySQL password (not echoed)' -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try {
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
}

try {
    [Environment]::SetEnvironmentVariable('APP_ENV', 'production', 'Process')
    [Environment]::SetEnvironmentVariable('DB_CONNECTION', 'mysql', 'Process')
    [Environment]::SetEnvironmentVariable('DB_HOST', $AivenHost, 'Process')
    [Environment]::SetEnvironmentVariable('DB_PORT', $AivenPort.ToString(), 'Process')
    [Environment]::SetEnvironmentVariable('DB_DATABASE', $AivenDatabase, 'Process')
    [Environment]::SetEnvironmentVariable('DB_USERNAME', $AivenUser, 'Process')
    [Environment]::SetEnvironmentVariable('DB_PASSWORD', $password, 'Process')
    [Environment]::SetEnvironmentVariable('DB_URL', $null, 'Process')
    [Environment]::SetEnvironmentVariable('DB_SOCKET', $null, 'Process')
    [Environment]::SetEnvironmentVariable('MYSQL_ATTR_SSL_CA', (Resolve-Path -LiteralPath $CaCertificatePath), 'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_HOST', $AivenHost, 'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_PORT', $AivenPort.ToString(), 'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_DATABASE', $AivenDatabase, 'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_USERNAME', $AivenUser, 'Process')

    Push-Location $backendPath
    try {
        # This PHP CLI is configured with variables_order=GPCS. Include E so Laravel's
        # dotenv repository sees the inherited Aiven environment values, not local .env values.
        php -d variables_order=EGPCS artisan config:clear --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Could not clear local Laravel configuration cache.' }
        # This command makes only SELECT/SHOW queries and confirms that Laravel,
        # not just the native client, receives the same TLS and authentication settings.
        $healthOptions = @('database:startup-health', '--no-interaction', '--config-check')
        if ($ConnectionCheckOnly) { $healthOptions += '--table-count' }
        php -d variables_order=EGPCS artisan @healthOptions
        if ($LASTEXITCODE -ne 0) { throw 'Laravel could not authenticate to Aiven. No migration was started.' }
        if ($ConnectionCheckOnly) {
            Write-Output 'AIVEN_LARAVEL_CONNECTION=OK'
            return
        }
        php -d variables_order=EGPCS artisan migrate --force --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Aiven migration failed. Do not seed; inspect the migration output and restore by Aiven fork/PITR if needed.' }
    } finally {
        Pop-Location
    }
} finally {
    foreach ($name in $environmentNames) {
        [Environment]::SetEnvironmentVariable($name, $previousEnvironment[$name], 'Process')
    }
    Remove-Variable password -ErrorAction SilentlyContinue
}

Write-Output 'AIVEN_MIGRATION=OK'
