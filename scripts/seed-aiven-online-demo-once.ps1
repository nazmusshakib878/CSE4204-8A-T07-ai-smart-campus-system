[CmdletBinding()]
param(
    [Parameter(Mandatory)] [string] $AivenHost,
    [Parameter(Mandatory)] [ValidateRange(1, 65535)] [int] $AivenPort,
    [Parameter(Mandatory)] [string] $AivenDatabase,
    [Parameter(Mandatory)] [string] $AivenUser,
    [Parameter(Mandatory)] [string] $CaCertificatePath
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot; $backendPath = Join-Path $projectRoot 'backend'
if ($AivenHost -in @('localhost', '127.0.0.1', '::1') -or $AivenPort -eq 3307) { throw 'Refusing local MariaDB target.' }
if (-not (Test-Path -LiteralPath $CaCertificatePath) -or -not (Test-Path -LiteralPath (Join-Path $backendPath 'artisan'))) { throw 'Required CA certificate or Laravel backend is missing.' }
& php -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);"; if ($LASTEXITCODE -ne 0) { throw 'pdo_mysql is unavailable.' }

$names = @('APP_ENV','DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','DB_URL','DB_SOCKET','MYSQL_ATTR_SSL_CA','AIVEN_EXPECTED_DB_HOST','AIVEN_EXPECTED_DB_PORT','AIVEN_EXPECTED_DB_DATABASE','AIVEN_EXPECTED_DB_USERNAME','ONLINE_DEMO_SEED_ENABLED','ONLINE_DEMO_SEED_CONFIRMATION','DEMO_ACCOUNT_PASSWORD')
$previous = @{}; foreach ($name in $names) { $previous[$name] = [Environment]::GetEnvironmentVariable($name, 'Process') }
function Read-PrivateValue([string] $prompt) { $value = Read-Host $prompt -AsSecureString; $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($value); try { return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) } }
$databasePassword = Read-PrivateValue 'Aiven MySQL password (not echoed)'
$demoPassword = Read-PrivateValue 'Temporary demo-account password (not echoed)'
if ($demoPassword.Length -lt 12) { throw 'Temporary demo-account password must have at least 12 characters.' }
$tokenBytes = New-Object byte[] 32
$randomGenerator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
try {
    $randomGenerator.GetBytes($tokenBytes)
    # BitConverter is available in Windows PowerShell 5.1; unlike
    # Convert.ToHexString it does not require a newer .NET runtime.
    $token = [BitConverter]::ToString($tokenBytes).Replace('-', '')
} finally {
    $randomGenerator.Dispose()
    Remove-Variable tokenBytes,randomGenerator -ErrorAction SilentlyContinue
}

try {
    [Environment]::SetEnvironmentVariable('APP_ENV','production','Process'); [Environment]::SetEnvironmentVariable('DB_CONNECTION','mysql','Process')
    [Environment]::SetEnvironmentVariable('DB_HOST',$AivenHost,'Process'); [Environment]::SetEnvironmentVariable('DB_PORT',$AivenPort.ToString(),'Process'); [Environment]::SetEnvironmentVariable('DB_DATABASE',$AivenDatabase,'Process'); [Environment]::SetEnvironmentVariable('DB_USERNAME',$AivenUser,'Process'); [Environment]::SetEnvironmentVariable('DB_PASSWORD',$databasePassword,'Process'); [Environment]::SetEnvironmentVariable('DB_URL',$null,'Process'); [Environment]::SetEnvironmentVariable('DB_SOCKET',$null,'Process'); [Environment]::SetEnvironmentVariable('MYSQL_ATTR_SSL_CA',(Resolve-Path -LiteralPath $CaCertificatePath),'Process')
    [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_HOST',$AivenHost,'Process'); [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_PORT',$AivenPort.ToString(),'Process'); [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_DATABASE',$AivenDatabase,'Process'); [Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_USERNAME',$AivenUser,'Process')
    [Environment]::SetEnvironmentVariable('ONLINE_DEMO_SEED_ENABLED','true','Process'); [Environment]::SetEnvironmentVariable('ONLINE_DEMO_SEED_CONFIRMATION',$token,'Process'); [Environment]::SetEnvironmentVariable('DEMO_ACCOUNT_PASSWORD',$demoPassword,'Process')
    Push-Location $backendPath
    try {
        php -d variables_order=EGPCS artisan config:clear --no-interaction; if ($LASTEXITCODE -ne 0) { throw 'Could not clear Laravel config.' }
        php -d variables_order=EGPCS artisan database:startup-health --no-interaction --config-check --table-count; if ($LASTEXITCODE -ne 0) { throw 'Aiven TLS verification failed.' }
        php -d variables_order=EGPCS artisan demo:seed-online --no-interaction --confirmation=$token; if ($LASTEXITCODE -ne 0) { throw 'Online demo seeding failed.' }
        php -d variables_order=EGPCS artisan demo:verify-online --no-interaction; if ($LASTEXITCODE -ne 0) { throw 'Online demo verification failed.' }
    } finally { Pop-Location }
} finally {
    foreach ($name in $names) { [Environment]::SetEnvironmentVariable($name, $previous[$name], 'Process') }
    Remove-Variable databasePassword,demoPassword,token -ErrorAction SilentlyContinue
}
Write-Output 'AIVEN_ONLINE_DEMO_SEED=OK'
