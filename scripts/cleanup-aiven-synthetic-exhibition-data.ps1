[CmdletBinding()]
param()

# One-time production cleanup. It only targets the known synthetic email patterns,
# refuses every unexpected account shape, and never contacts local MariaDB.
$ErrorActionPreference = 'Stop'; Set-StrictMode -Version Latest
$hostName='mysql-200a9449-nazmusshakib335-1a15.h.aivencloud.com'; $port=15419; $database='defaultdb'; $user='avnadmin'
$ca='D:\CV\ca.pem'; $bin='D:\CV\mysql-26.7.0-winx64\mysql-26.7.0-winx64\bin'; $mysql=Join-Path $bin 'mysql.exe'; $dump=Join-Path $bin 'mysqldump.exe'
$backupDir='C:\tmp\ai-smart-campus-private-aiven-backups'
if($hostName -in @('localhost','127.0.0.1','::1') -or $port -eq 3307){throw 'Refusing local MariaDB.'}
foreach($path in @($ca,$mysql,$dump)){if(-not(Test-Path -LiteralPath $path -PathType Leaf)){throw "Required file unavailable: $path"}}
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
& icacls $backupDir /inheritance:r /grant:r "${env:USERNAME}:(OI)(CI)F" | Out-Null
$common=@('--protocol=TCP',"--host=$hostName","--port=$port","--user=$user","--database=$database",'--ssl-mode=VERIFY_IDENTITY',"--ssl-ca=$ca",'--batch','--skip-column-names','--raw')
function Query([string]$sql){ & $mysql @common '-p' "--execute=$sql"; if($LASTEXITCODE -ne 0){throw 'Aiven TLS query failed.'} }

$preflight=@'
SELECT DATABASE(), SUM(role='admin' AND approval_status='approved'), SUM(role='student'), SUM(role='faculty'), COUNT(*) FROM users;
SELECT COUNT(*) FROM users WHERE role='student' AND email REGEXP '^student[1-8][1-5]@gmail\\.com$';
SELECT COUNT(*) FROM users WHERE role='faculty' AND email REGEXP '^spring26\\.faculty\\.[0-9]{2}@example\\.edu$';
SELECT COUNT(*) FROM users WHERE NOT ((role='student' AND email REGEXP '^student[1-8][1-5]@gmail\\.com$') OR (role='faculty' AND email REGEXP '^spring26\\.faculty\\.[0-9]{2}@example\\.edu$') OR (role='admin' AND approval_status='approved'));
SHOW STATUS LIKE 'Ssl_cipher';
'@
Write-Host 'Password prompt 1 of 3: TLS preflight.' -ForegroundColor Cyan
$rows=@(Query $preflight)
if($rows.Count -ne 5){throw 'Unexpected preflight response; no cleanup occurred.'}
$counts=$rows[0] -split "`t"; $students=[int]$counts[2]; $faculty=[int]$counts[3]; $total=[int]$counts[4]
if($counts[0] -ne $database -or [int]$counts[1] -ne 1 -or $students -ne 40 -or $faculty -ne 8 -or $total -ne 49 -or [int]$rows[1] -ne 40 -or [int]$rows[2] -ne 8 -or [int]$rows[3] -ne 0 -or $rows[4] -notmatch "`t.+") { throw 'Safety gate failed: expected exactly one approved Admin and the 48 known synthetic accounts only. No cleanup occurred.' }

$stamp=Get-Date -Format 'yyyyMMdd-HHmmss'; $backup=Join-Path $backupDir "$database-before-synthetic-cleanup-$stamp.sql"
Write-Host 'Password prompt 2 of 3: verified logical backup.' -ForegroundColor Cyan
& $dump '--protocol=TCP' "--host=$hostName" "--port=$port" "--user=$user" '--ssl-mode=VERIFY_IDENTITY' "--ssl-ca=$ca" '--single-transaction' '--quick' '--routines' '--events' '--no-tablespaces' '--set-gtid-purged=OFF' '-p' "--result-file=$backup" '--databases' $database
if($LASTEXITCODE -ne 0 -or -not(Test-Path $backup) -or (Get-Item $backup).Length -le 0){throw 'Backup failed; cleanup was not attempted.'}
$hash=(Get-FileHash $backup -Algorithm SHA256).Hash
if((Select-String -LiteralPath $backup -Pattern '^CREATE TABLE ' -AllMatches).Count -lt 38){throw 'Backup verification failed; cleanup was not attempted.'}
if((Read-Host 'Type CLEANUP-48 to execute the approved production cleanup') -cne 'CLEANUP-48'){throw 'Confirmation did not match; no cleanup occurred.'}

$cleanup=@'
START TRANSACTION;
CREATE TEMPORARY TABLE demo_users (id BIGINT PRIMARY KEY, role VARCHAR(20)) AS
 SELECT id, role FROM users WHERE (role='student' AND email REGEXP '^student[1-8][1-5]@gmail\\.com$') OR (role='faculty' AND email REGEXP '^spring26\\.faculty\\.[0-9]{2}@example\\.edu$');
DELETE ar FROM academic_records ar JOIN students s ON s.id=ar.student_id JOIN demo_users d ON d.id=s.user_id;
DELETE ar FROM attendance_records ar JOIN students s ON s.id=ar.student_id JOIN demo_users d ON d.id=s.user_id;
DELETE ar FROM assessment_records ar JOIN students s ON s.id=ar.student_id JOIN demo_users d ON d.id=s.user_id;
DELETE pm FROM performance_metrics pm JOIN students s ON s.id=pm.student_id JOIN demo_users d ON d.id=s.user_id;
DELETE cr FROM course_recommendations cr JOIN students s ON s.id=cr.student_id JOIN demo_users d ON d.id=s.user_id;
DELETE ce FROM course_enrollments ce JOIN students s ON s.id=ce.student_id JOIN demo_users d ON d.id=s.user_id;
DELETE ra FROM risk_alerts ra JOIN students s ON s.id=ra.student_id JOIN demo_users d ON d.id=s.user_id;
DELETE nr FROM notice_reads nr JOIN demo_users d ON d.id=nr.user_id;
DELETE r FROM recommendations r JOIN demo_users d ON d.id=r.target_user_id;
DELETE t FROM tasks t JOIN demo_users d ON d.id=t.assigned_to_user_id;
DELETE ll FROM library_loans ll JOIN demo_users d ON d.id=ll.user_id;
DELETE ht FROM helpdesk_tickets ht JOIN demo_users d ON d.id=ht.user_id;
DELETE fr FROM fee_records fr JOIN students s ON s.id=fr.student_id JOIN demo_users d ON d.id=s.user_id;
DELETE fl FROM faculty_leaves fl JOIN faculty f ON f.id=fl.faculty_id JOIN demo_users d ON d.id=f.user_id;
DELETE cs FROM class_reschedules cs JOIN faculty f ON f.id=cs.faculty_id JOIN demo_users d ON d.id=f.user_id;
DELETE FROM users WHERE id IN (SELECT id FROM demo_users);
COMMIT;
SELECT SUM(role='admin' AND approval_status='approved'), SUM(role='student'), SUM(role='faculty'), COUNT(*) FROM users;
'@
Write-Host 'Password prompt 3 of 3: TLS transaction.' -ForegroundColor Cyan
$after=@(Query $cleanup)
if($after.Count -ne 1 -or $after[0] -notmatch '^1\s+0\s+0\s+1$'){throw 'Post-cleanup verification failed; inspect the private backup before any further action.'}
Write-Output 'AIVEN_SYNTHETIC_CLEANUP=SUCCESS'; Write-Output "AIVEN_BACKUP_FILE=$backup"; Write-Output "AIVEN_BACKUP_SHA256=$hash"
