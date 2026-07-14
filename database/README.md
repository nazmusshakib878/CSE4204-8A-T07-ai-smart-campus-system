# Database backup and recovery

Executable migrations and seeders live in `backend/database`. Do not commit production database dumps.

## Create a backup

From repository root on Windows:

```powershell
.\scripts\backup-database.ps1
```

Backups are written to `database/backups`, which is ignored by Git. The script reads database settings from `backend/.env` and uses `MYSQL_PWD` temporarily rather than placing the password in the process arguments.

## Verify and restore

Record checksum and size, copy the encrypted backup off-server, and test restore in staging:

```powershell
Get-FileHash .\database\backups\ai_smart_campus-*.sql
mysql -h 127.0.0.1 -u root -p ai_smart_campus_staging < backup.sql
```

Use least-privilege credentials, retention rules, encrypted offsite storage, and a documented recovery owner.
