param([string]$EnvironmentFile = "$PSScriptRoot\..\backend\.env", [string]$OutputDirectory = "$PSScriptRoot\..\database\backups", [string]$MySqlDumpPath = "")
$ErrorActionPreference = 'Stop'
if (-not (Test-Path $EnvironmentFile)) { throw "Environment file not found: $EnvironmentFile" }
$values = @{}
Get-Content $EnvironmentFile | Where-Object { $_ -match '^[A-Z0-9_]+=' } | ForEach-Object {
  $key, $value = $_ -split '=', 2
  $values[$key] = $value.Trim().Trim('"')
}
$required = 'DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME'
foreach ($key in $required) { if (-not $values[$key]) { throw "Missing $key in environment file." } }
New-Item -ItemType Directory -Force $OutputDirectory | Out-Null
$file = Join-Path $OutputDirectory ("{0}-{1}.sql" -f $values.DB_DATABASE, (Get-Date -Format 'yyyyMMdd-HHmmss'))
if (-not $MySqlDumpPath) {
  $command = Get-Command mysqldump -ErrorAction SilentlyContinue
  if ($command) { $MySqlDumpPath = $command.Source }
  elseif (Test-Path 'C:\xampp\mysql\bin\mysqldump.exe') { $MySqlDumpPath = 'C:\xampp\mysql\bin\mysqldump.exe' }
  else { throw 'mysqldump was not found. Install MySQL client tools or pass -MySqlDumpPath.' }
}
$previousPassword = $env:MYSQL_PWD
try {
  $env:MYSQL_PWD = $values.DB_PASSWORD
  & $MySqlDumpPath --host=$($values.DB_HOST) --port=$($values.DB_PORT) --user=$($values.DB_USERNAME) --single-transaction --routines --triggers --result-file=$file $values.DB_DATABASE
  if ($LASTEXITCODE -ne 0) { throw "mysqldump failed with exit code $LASTEXITCODE." }
} finally { $env:MYSQL_PWD = $previousPassword }
Write-Host "Backup created: $file"
