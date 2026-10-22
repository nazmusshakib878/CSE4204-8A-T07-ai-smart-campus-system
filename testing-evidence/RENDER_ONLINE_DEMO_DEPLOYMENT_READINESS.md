# Render Online Demo Deployment Readiness

Date: 2026-10-19  
Status: prepared in source control only. No Render deployment, production database connection, migration, seed, or environment change was performed.

## Ready in the repository

- `start-render.sh` runs `php artisan migrate --force` and never runs a seeder. It refuses production SQLite configuration.
- `render.yaml` keeps automatic deployment disabled and declares only secret placeholders for the database and demo credentials.
- The Docker image includes `pdo_mysql` / MariaDB client support and now copies `Spring_2026_A_Sections_Official_Routine.csv` to `/app/`, where the idempotent routine seeder expects it.
- `demo:seed-online` is a manual command, not a startup action. In production it requires all of:
  1. `ONLINE_DEMO_SEED_ENABLED=true`;
  2. a private `ONLINE_DEMO_SEED_CONFIRMATION` value of at least 24 characters passed as the command option; and
  3. `DEMO_ACCOUNT_PASSWORD`, a private password secret of at least 12 characters.
- The command invokes the existing idempotent Spring 2026 demo seed: 40 students, 8 faculty accounts, their results/attendance/enrollments, and the 96 verified official routine rows. It excludes the 13 unresolved routine rows and does not run the unrelated baseline sample seed in production.
- Demo passwords are no longer embedded in the two demo seeders. The command output does not print credentials.
- Production environment variable template: `backend/.env.production.example`.

## Local verification

- PHP syntax checks: passed for the online command and both changed seeders.
- Production-mode guard: passed; with seed enablement absent, `demo:seed-online` exited before any write.
- Invalid confirmation guard: passed; no seed was run.
- Backend test suite: passed — 86 tests, 432 assertions, including authentication, roles, dashboards, routine management, results, attendance isolation, and AI Assistant context tests.
- Frontend production build: passed.
- Container build: not run because Docker is unavailable in this workspace.

## Required Render setup before an approved deployment

1. Keep the existing production database untouched and take a provider-native backup plus a logical SQL export.
2. Select and create an external **managed MariaDB-compatible** database in the same or nearby region, with TLS enforced, private networking or tightly restricted inbound access, automated backups/PITR, and a dedicated least-privilege application user.
3. In Render, use **Save only** while entering the following secrets; do not trigger a deploy yet:
   - existing `APP_KEY` (preserve it; do not generate/rotate it during this change);
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `MYSQL_ATTR_SSL_CA` for the managed database;
   - `DEMO_ACCOUNT_PASSWORD` (unique, high-entropy, restricted-demo password);
   - `ONLINE_DEMO_SEED_CONFIRMATION` (unique 24+ character one-time confirmation secret);
   - real `APP_URL`, `CORS_ALLOWED_ORIGINS`, and any approved AI provider key.
4. Import the production backup into the new managed database only after reconciliation and approval. Validate row counts, migration status, and a restore procedure before directing the web service at it.
5. After an approved deployment, run migrations once. Then, in a Render one-off job or ephemeral shell, temporarily enable the demo seed flag and run:
   `php artisan demo:seed-online --confirmation="$ONLINE_DEMO_SEED_CONFIRMATION"`
   Review its output, then immediately set `ONLINE_DEMO_SEED_ENABLED=false` and rotate the confirmation token. Do not place this command in Render’s start command, build command, or cron configuration.
6. Verify a representative student and faculty login, role-restricted dashboard, A-section routine, existing result/attendance totals, and AI Assistant response using a non-sensitive test prompt. Disable or rotate demo credentials after the event.

## Blockers and decisions needed

- Render’s managed relational datastore is PostgreSQL, not MariaDB. This application is currently configured and packaged for MariaDB/MySQL. Moving to Render Postgres would require a separately reviewed engine/data migration and `pdo_pgsql` image support; it is not safe to do implicitly.
- An external managed MariaDB provider, region, backup-retention target, and connection/TLS method have not been selected.
- The actual production data source and current Render environment values were intentionally not accessed, so data migration and real online login verification remain pending approval.
- The 13 official routine rows with the malformed `05:40 AM` end time remain intentionally absent from the online seed until corrected source values are supplied.

## Cost estimate

- Render Starter web service (0.5 CPU / 512 MB): approximately **US$7/month** for an always-on demo, before overage/optional workspace costs.
- The managed MariaDB-compatible database is external to Render; its cost, storage, backups, and egress depend on the selected provider and cannot be estimated responsibly until that provider and retention requirement are chosen.
- A practical budget floor is therefore **US$7/month plus the selected managed-database plan**. Do not use a free expiring database for a persistent public demo.

## Render references

- Render web plans and current Starter resources: https://render.com/docs/compute-plans
- Render environment variables and dashboard-only secrets: https://render.com/docs/configure-environment-variables
- Render one-off jobs for manual tasks: https://render.com/docs/one-off-jobs
- Render persistent-data guidance (and why managed stores are preferred): https://render.com/docs/disks
