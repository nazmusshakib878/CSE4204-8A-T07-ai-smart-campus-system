[CmdletBinding()]
param(
    [Parameter(Mandatory)] [string] $AivenHost,
    [Parameter(Mandatory)] [ValidateRange(1, 65535)] [int] $AivenPort,
    [Parameter(Mandatory)] [string] $AivenDatabase,
    [Parameter(Mandatory)] [string] $AivenUser,
    [Parameter(Mandatory)] [string] $CaCertificatePath,
    [Parameter(Mandatory)] [string] $BackupDirectory,
    [ValidateSet('post-migration', 'post-seed')] [string] $BackupLabel = 'post-migration'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$backendPath = Join-Path $projectRoot 'backend'
if ($AivenHost -in @('localhost', '127.0.0.1', '::1') -or $AivenPort -eq 3307) { throw 'Refusing local MariaDB target.' }
if (-not (Test-Path -LiteralPath $CaCertificatePath)) { throw 'The Aiven CA certificate file was not found.' }
if (-not (Test-Path -LiteralPath (Join-Path $backendPath 'artisan'))) { throw 'Laravel backend/artisan was not found.' }
& php -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) { throw 'The local PHP CLI does not have pdo_mysql enabled.' }

$names = @('APP_ENV','DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','DB_URL','DB_SOCKET','MYSQL_ATTR_SSL_CA','AIVEN_EXPECTED_DB_HOST','AIVEN_EXPECTED_DB_PORT','AIVEN_EXPECTED_DB_DATABASE','AIVEN_EXPECTED_DB_USERNAME')
$previous = @{}; foreach ($name in $names) { $previous[$name] = [Environment]::GetEnvironmentVariable($name, 'Process') }
$securePassword = Read-Host 'Aiven MySQL password (not echoed)' -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try { $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }

try {
    [Environment]::SetEnvironmentVariable('APP_ENV','production','Process'); [Environment]::SetEnvironmentVariable('DB_CONNECTION','mysql','Process')
    [Environment]::SetEnvironmentVariable('DB_HOST',$AivenHost,'Process'); [Environment]::SetEnvironmentVariable('DB_PORT',$AivenPort.ToString(),'Process')
    [Environment]::SetEnvironmentVariable('DB_DATABASE',$AivenDatabase,'Process'); [Environment]::SetEnvironmentVariable('DB_USERNAME',$AivenUser,'Process')
    [Environment]::SetEnvironmentVariable('DB_PASSWORD',$password,'Process'); [Environment]::SetEnvironmentVariable('DB_URL',$null,'Process'); [Environment]::SetEnvironmentVariable('DB_SOCKET',$null,'Process')
    [Environment]::SetEnvironmentVariable('MYSQL_ATTR_SSL_CA',(Resolve-Path -LiteralPath $CaCertificatePath),'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_HOST',$AivenHost,'Process'); [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_PORT',$AivenPort.ToString(),'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_DATABASE',$AivenDatabase,'Process'); [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_USERNAME',$AivenUser,'Process')
    Push-Location $backendPath
    try {
        php -d variables_order=EGPCS artisan config:clear --no-interaction; if ($LASTEXITCODE -ne 0) { throw 'Could not clear Laravel config.' }
        php -d variables_order=EGPCS artisan database:startup-health --no-interaction --config-check --table-count; if ($LASTEXITCODE -ne 0) { throw 'Laravel TLS verification failed.' }
        php -d variables_order=EGPCS artisan migrate:status --no-interaction; if ($LASTEXITCODE -ne 0) { throw 'Migration status verification failed.' }
        $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'; $path = Join-Path $BackupDirectory ("{0}-{1}-{2}.sql" -f $AivenDatabase, $BackupLabel, $stamp)
        php -d variables_order=EGPCS (Join-Path $projectRoot 'scripts\backup-aiven-post-migration.php') $path; if ($LASTEXITCODE -ne 0) { throw 'Post-migration backup failed.' }
    } finally { Pop-Location }
} finally {
    foreach ($name in $names) { [Environment]::SetEnvironmentVariable($name, $previous[$name], 'Process') }
    Remove-Variable password -ErrorAction SilentlyContinue
}

Write-Output 'AIVEN_POST_MIGRATION_VERIFICATION=OK'
