# Production deployment guide

## 1. Server requirements

- PHP 8.2+, Composer, MySQL 8+, Node.js/npm
- PHP extensions required by Laravel and file validation
- Web root must point to `backend/public`
- HTTPS certificate
- A process manager for queue workers
- Cron access for Laravel scheduler

## 2. Environment

Copy `backend/.env.production.example` to `backend/.env`. Never commit the real file.

Set `APP_URL`, a restricted database user/password, `CORS_ALLOWED_ORIGINS`, mail settings, and optional OpenAI key. Then run:

```bash
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Copy `frontend/.env.production.example` to `frontend/.env.production` and set the public API URL.

## 3. Install and build

```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan uploads:cleanup --dry-run

cd ../frontend
npm ci
npm run build
```

Deploy `frontend/dist` to the frontend host or configure the web server to serve it with SPA fallback to `index.html`.

## 4. Permissions

The web server user needs write access only to:

- `backend/storage`
- `backend/bootstrap/cache`

Do not expose `.env`, logs, backups, source maps, or the repository root.

## 5. Scheduler and queue

Cron entry:

```cron
* * * * * cd /var/www/ai-smart-campus/backend && php artisan schedule:run >> /dev/null 2>&1
```

Run `php artisan queue:work --tries=3 --timeout=90` under Supervisor/systemd if queued email/SMS delivery is enabled.

## 6. CORS and rate limiting

Set `CORS_ALLOWED_ORIGINS` to comma-separated trusted frontend origins only. Authentication endpoints default to 5 requests/minute per email/IP. Authenticated APIs default to 60 requests/minute per user/IP.

Verify:

```bash
php artisan route:list -v
curl -i -H "Origin: https://campus.example.edu" https://api.example.edu/api/departments
```

## 7. Storage and cleanup

Run `php artisan storage:link` once per release target. Orphaned uploads are cleaned daily at 02:30 by the scheduler. Preview safely with:

```bash
php artisan uploads:cleanup --dry-run
```

## 8. Logging and monitoring

Production uses daily logs with configurable retention. Set `LOG_LEVEL=warning`; API requests log method, path, status, duration, user ID and IP, never passwords or request bodies. Monitor `/up`, HTTP 5xx rates, disk space, queue failures, and database health.

## 9. Backup and restore

Windows/XAMPP:

```powershell
.\scripts\backup-database.ps1
```

Linux:

```bash
MYSQL_PWD='secret' mysqldump --single-transaction --routines --triggers -h 127.0.0.1 -u ai_smart_campus ai_smart_campus > backup.sql
```

Restore only into a maintenance/staging database first:

```bash
mysql -h 127.0.0.1 -u ai_smart_campus -p ai_smart_campus < backup.sql
```

Encrypt backups, store them outside the web root, rotate them, and test restoration regularly.

## 10. Release checklist

- Backend tests pass
- Frontend component and E2E tests pass
- `APP_DEBUG=false`
- HTTPS and CORS verified
- Migration and backup completed
- Storage link and permissions verified
- Scheduler/queue running
- Demo accounts removed or passwords rotated
- Final screenshots contain no secrets or private student data
