# Spring 2026 University Demo Setup Report

Date: 2026-10-19

## Environment and safeguard result

- Workspace access was successfully verified after the reported VS Code restart; no Windows sandbox access error occurred.
- MariaDB connectivity was verified only on `localhost:3307` (MariaDB 10.4.32).
- The existing pre-demo dump was checked as a MariaDB SQL dump before any database write.
- A fresh pre-seed backup was created and checked before seeding:
  - File: `backend/database/backups/ai_smart_campus-pre-spring-2026-20261019-005832.sql`
  - Size: 414,557 bytes
  - SHA-256: `3C5E22E22127E04A7079101A52CE99F3E4017655A865861FE93B067496F1FB9D`

## Seeded Spring 2026 demo data

The idempotent `LocalPresentationDemoSeeder` now creates a separate Spring 2026 cohort without deleting or overwriting existing student data.

| Item | Verified count |
| --- | ---: |
| Synthetic students | 40 |
| Students in each semester (1 through 8) | 5 |
| Synthetic faculty | 8 |
| Spring 2026 enrollments | 105 |
| Attendance records (2026-03-01 through 2026-03-10) | 1,050 |
| Academic-result records | 105 |
| Spring 2026 section schedules | 21 |

Students use the `CSE-SPR26-S{semester}-{number}` identifier and schedules use section `SPR26-A`. Academic records are tagged `spring-2026-demo`. The separate `DEMO-*` risk-monitoring course is deliberately excluded from this academic routine.

Results include varied grades and GPA values, and the fifth student in each semester has a lower-attendance/lower-performance profile suitable for risk-alert demonstrations. Each enrollment has a matching academic record and ten dated attendance entries.

## Routine source note

No official routine PDF was present anywhere in the workspace, so no unverified PDF-derived courses, rooms, or times were invented. Schedules use the project's existing CSE catalog and Spring 2026 term/section data model. The sparse catalog has one course for semester 2 and two for semester 1; later semesters receive three catalog courses each.

## Verification

- `php -l database/seeders/LocalPresentationDemoSeeder.php`: passed.
- `php artisan migrate:status --no-interaction`: all migrations applied.
- `php artisan test`: passed, 86 tests and 432 assertions.
- `npm run build` in `frontend`: passed.
- Database integrity queries confirmed 5 students per semester, no Spring 2026 enrollment linked to a `DEMO-*` course, and no enrollment missing a result record.

## Operational note

The supplied `scripts/backup-database.ps1` could not be launched because the machine's PowerShell execution policy blocks scripts. No execution policy or permission setting was changed; the identical `mysqldump` operation was run directly with the configured port 3307 to create the verified backup above.
