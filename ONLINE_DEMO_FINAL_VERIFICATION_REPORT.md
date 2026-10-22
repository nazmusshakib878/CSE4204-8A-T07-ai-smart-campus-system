# Online Demo Final Verification Report

Generated: 2026-10-19 (Asia/Dhaka)

## Production safety status

| Check | Status | Evidence |
| --- | --- | --- |
| Local MariaDB protection | PASS | The Aiven scripts reject localhost, loopback, and port 3307. No local database command was run. |
| Aiven target parameters | READY | The guarded scripts require a non-local host, port, database, user, CA certificate, and non-empty backup. |
| CA certificate | PASS | The supplied CA file exists and is readable; its contents were not displayed. |
| Pre-migration backup | PASS | `database/backups/aiven-pre-migration/defaultdb-pre-migration-20261019-031734.sql` exists and is non-empty. |
| Laravel CLI environment handling | PASS (static) | `scripts/migrate-aiven-once.ps1` clears `DB_URL` and `DB_SOCKET`, injects only process-scoped Aiven settings, restores the prior process environment, and executes Artisan with `variables_order=EGPCS`. |
| Laravel-to-Aiven TLS connection | PENDING | This must be run through the script's masked password prompt. No database password was read, saved, or logged. |
| Aiven schema migration | PENDING | Not run: the connection verification and private password prompt are still required. |
| Post-migration backup | PENDING | Only created after a successful migration. |
| Online seed | NOT RUN | Deliberately held pending explicit user approval. |
| Deployment | NOT RUN | Auto-deploy remains off in `render.yaml`; no deployment was requested. |

## Online-demo data readiness

| Dataset | Expected | Status |
| --- | ---: | --- |
| Synthetic students | 40 (5 each in semesters 1-8) | READY, not seeded |
| Student email pattern | `student11@gmail.com` through `student85@gmail.com` | READY |
| Synthetic faculty | 8 | READY, not seeded |
| Spring 2026 sections | `1A`-`8A` | READY |
| Official routine rows | 96 | PASS: source validation counted 96 importable rows |
| Excluded routine rows | 13 | PASS: source validation counted 13 review-only rows |

The seeders are idempotent. They require a private `DEMO_ACCOUNT_PASSWORD` with at least 12 characters and the guarded command additionally requires a separate private confirmation token. No shared demo password is embedded in the seeders. The synthetic Gmail student range is excluded from password-reset and notice-email delivery.

## Test results

| Area | Result |
| --- | --- |
| Backend Laravel suite | PASS - 88 tests, 439 assertions |
| Focused production TLS/readiness tests | PASS |
| Idempotent faculty risk-demo seeder test | PASS |
| Frontend Vitest suite | PASS - 10 tests |
| Frontend production build | PASS |
| Public Render root endpoint | PASS - HTTPS HTTP 200 |
| Public Render database-backed `/api/departments` endpoint | FAIL - HTTPS HTTP 500, safe generic database-error response |

## Changes made during verification

- Updated online-demo student addresses to the requested synthetic Gmail naming range.
- Prevented password-reset and notice emails from being sent to that synthetic range.
- Removed hardcoded demo passwords from the faculty-risk and live-admin seeders; both now require the private demo-password environment secret.
- Stabilized an existing frontend AI-assistant retry test by waiting for conversation initialization before interacting with the form.

## Remaining limitations and required next actions

1. A masked private terminal entry of the Aiven password is required to run the already-guarded Laravel TLS verification. This cannot be supplied through chat or written to a file.
2. After the TLS check passes, the authorized one-time schema migration can be run. Then table, migration, and foreign-key inventories must be verified and a post-migration backup created.
3. The Render public API currently returns a database error. Its dashboard database settings (especially Aiven port, CA mount path, MySQL TLS settings, and DB credentials) must be checked without exposing values before it can be considered connected. The repository's `render.yaml` contains a placeholder port of `3306`; the live dashboard value must be the Aiven service port.
4. Explicit approval is required before any online demo seed. Login, role-based live workflows, persistence-after-restart, and full online data verification remain unverified until that seed has run.
5. Explicit approval is required before any deployment. Auto-deploy is configured off.
