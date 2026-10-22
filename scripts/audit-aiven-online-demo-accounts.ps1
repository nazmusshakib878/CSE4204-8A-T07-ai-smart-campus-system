[CmdletBinding()]
param(
    [Parameter(Mandatory)] [string] $AivenHost,
    [Parameter(Mandatory)] [ValidateRange(1, 65535)] [int] $AivenPort,
    [Parameter(Mandatory)] [string] $AivenDatabase,
    [Parameter(Mandatory)] [string] $AivenUser,
    [Parameter(Mandatory)] [string] $CaCertificatePath,
    [Parameter(Mandatory)] [string] $ReportPath
)
$ErrorActionPreference = 'Stop'; $projectRoot = Split-Path -Parent $PSScriptRoot; $backendPath = Join-Path $projectRoot 'backend'
if ($AivenHost -in @('localhost','127.0.0.1','::1') -or $AivenPort -eq 3307) { throw 'Refusing local MariaDB target.' }
if (-not (Test-Path -LiteralPath $CaCertificatePath)) { throw 'Aiven CA certificate was not found.' }
$names=@('APP_ENV','DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','DB_URL','DB_SOCKET','MYSQL_ATTR_SSL_CA','AIVEN_EXPECTED_DB_HOST','AIVEN_EXPECTED_DB_PORT','AIVEN_EXPECTED_DB_DATABASE','AIVEN_EXPECTED_DB_USERNAME','DEMO_ACCOUNT_PASSWORD'); $previous=@{}; foreach($name in $names){$previous[$name]=[Environment]::GetEnvironmentVariable($name,'Process')}
function Read-PrivateValue([string]$prompt){$value=Read-Host $prompt -AsSecureString;$pointer=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($value);try{return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)}finally{[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)}}
$databasePassword=Read-PrivateValue 'Aiven MySQL password (not echoed)'; $demoPassword=Read-PrivateValue 'Temporary demo-account password (not echoed)'
try {
 [Environment]::SetEnvironmentVariable('APP_ENV','production','Process');[Environment]::SetEnvironmentVariable('DB_CONNECTION','mysql','Process');[Environment]::SetEnvironmentVariable('DB_HOST',$AivenHost,'Process');[Environment]::SetEnvironmentVariable('DB_PORT',$AivenPort.ToString(),'Process');[Environment]::SetEnvironmentVariable('DB_DATABASE',$AivenDatabase,'Process');[Environment]::SetEnvironmentVariable('DB_USERNAME',$AivenUser,'Process');[Environment]::SetEnvironmentVariable('DB_PASSWORD',$databasePassword,'Process');[Environment]::SetEnvironmentVariable('DB_URL',$null,'Process');[Environment]::SetEnvironmentVariable('DB_SOCKET',$null,'Process');[Environment]::SetEnvironmentVariable('MYSQL_ATTR_SSL_CA',(Resolve-Path -LiteralPath $CaCertificatePath),'Process');[Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_HOST',$AivenHost,'Process');[Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_PORT',$AivenPort.ToString(),'Process');[Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_DATABASE',$AivenDatabase,'Process');[Environment]::SetEnvironmentVariable('AIVEN_EXPECTED_DB_USERNAME',$AivenUser,'Process');[Environment]::SetEnvironmentVariable('DEMO_ACCOUNT_PASSWORD',$demoPassword,'Process')
 Push-Location $backendPath;try{php -d variables_order=EGPCS artisan config:clear --no-interaction;if($LASTEXITCODE-ne 0){throw 'Could not clear Laravel config.'};php -d variables_order=EGPCS artisan database:startup-health --no-interaction --config-check;if($LASTEXITCODE-ne 0){throw 'Aiven TLS verification failed.'};php -d variables_order=EGPCS artisan demo:audit-accounts --no-interaction "--report=$ReportPath";if($LASTEXITCODE-ne 0){throw 'Account audit failed.'}}finally{Pop-Location}
} finally {foreach($name in $names){[Environment]::SetEnvironmentVariable($name,$previous[$name],'Process')};Remove-Variable databasePassword,demoPassword -ErrorAction SilentlyContinue}
Write-Output 'AIVEN_ONLINE_DEMO_ACCOUNT_AUDIT=OK'
