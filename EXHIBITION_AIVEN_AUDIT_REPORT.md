# Exhibition Aiven Audit Report

## Status

Completed using a TLS-verified, read-only Aiven MySQL connection. No database writes, account changes, migrations, seeding, deployment, or local-MariaDB access were performed.

## Backup verification

| Check | Result |
| --- | --- |
| Private location | C:\tmp\ai-smart-campus-private-aiven-backups (outside repository) |
| Backup filename | defaultdb-exhibition-readonly-20261019-214759.sql |
| Backup size (bytes) | 232998 |
| SHA-256 | 9E4838BBEB05345A3A22E4D6A959DE6C5FB9E792867D496ED9188EBE0C49E118 |
| CREATE TABLE definitions / expected base tables | 38 / 38 |

## Sanitized account and dependency counts

| Category | Count / value |
| --- | --- |
| TLS cipher negotiated | TLS_AES_128_GCM_SHA256 |
| Target database | defaultdb |
| Base tables | 38 |
| Approximate schema size (bytes) | 1703936 |
| Synthetic students | 40 |
| Complete five-student cohorts | 8 |
| Synthetic faculty | 8 |
| Other accounts | 0 |
| Pending registrations | 0 |
| Admin accounts | 0 |
| Demo enrollments | 375 |
| Demo attendance records | 1050 |
| Demo academic records | 105 |
| Demo performance metrics | 40 |
| Official Spring 2026 routine rows | 96 |

### Users by role and approval status

| Role / approval | Count |
| --- | ---: |
| student / approved | 40 |
| faculty / approved | 8 |

## Database health observations

| Status variable | Value |
| --- | --- |
| Threads connected | 1 |
| Max used connections | 3 |
| Slow queries | 1 |
| Questions | 172646 |
| Uptime seconds | 72389 |

These are point-in-time read-only observations, not a load test.

## Reversible cleanup recommendation

The expected exhibition cohort is 40 synthetic students in eight five-student cohorts plus 8 synthetic faculty. If the observed counts are complete, none of those accounts is unnecessary for a full eight-semester demonstration. Keep admins and all non-pattern accounts unchanged. Any future reduction should first disable specifically approved synthetic accounts, preserve dependent academic rows, verify role/dashboard behavior, and retain this backup for rollback.

## Remaining exhibition risks

- Render cold starts, application memory, and QR/mobile path are not measured by this database audit.
- A verified backup is a logical snapshot; restore testing requires a separate isolated environment and approval.
- No real-user classification or cleanup has been performed.

## Actions requiring approval

- Any account disablement, data cleanup, migration, seed, restore drill, Render change, GitHub push, or deployment.
- Any connection-setting change or performance load test beyond these read-only point-in-time queries.
