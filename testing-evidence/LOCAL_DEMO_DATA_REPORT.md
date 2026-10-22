# Local Demo Data Report

**Project:** AI Smart Campus System  
**Execution date:** 2026-10-18 (Asia/Dhaka)  
**Database scope:** verified local MySQL database `ai_smart_campus` on `127.0.0.1` only.

## Safety record

- Backup created before seeding: `database/backups/ai_smart_campus_pre_local_demo_20261018.sql`.
- No `migrate:fresh`, `db:wipe`, truncation, reset, or production/remote connection was used.
- All writes use deterministic identifiers and `updateOrCreate` / `firstOrCreate` inside the new presentation seeder transaction.
- Re-running the seeder produced identical validation counts.

## Seeded coverage

| Item | Verified count |
|---|---:|
| Deterministic CSE demo students | 67 |
| Synthetic faculty accounts (`demo.faculty.*@example.edu`) | 10 |
| Semester 1 students | 8 |
| Semester 2 students | 8 |
| Semester 3 students | 8 |
| Semester 4 students | 8 |
| Semester 5 students | 8 |
| Semester 6 students | 11 |
| Semester 7 students | 8 |
| Semester 8 students | 8 |
| Demo student course enrollments | 179 |
| Demo attendance records | 1,910 |
| Demo academic records/transcript rows | 179 |
| Section-A schedules | 22 |

Primary coverage is **Computer Science & Engineering / BSc in CSE**. The seeder uses all eight supported CSE semester code groups: 11, 12, 21, 22, 31, 32, 41, and 42.

## Data characteristics

The `LocalPresentationDemoSeeder` creates deterministic `CSE-DEMO-*` students with synthetic `@example.edu` addresses, eight students in each supported semester, a three-course enrollment set, ten attendance sessions per course, academic records, GPA/CGPA metrics, and section-A routines. Each semester includes an academic-attention scenario (lower attendance and grade) alongside stable and high-performing examples.

It also assigns catalog courses across ten synthetic faculty accounts so faculty dashboards have teaching data. Existing campus demo seeders continue to provide notices, tasks, current-term routines/exams, and baseline monitoring examples.

## Demo access

Existing documented Admin, Faculty, and Student demo accounts were preserved. The locally seeded synthetic faculty accounts are intended for data coverage, not as published credentials. Passwords are intentionally not recorded here. Use the already documented local demo accounts for presentation login.

## Validation performed

- Local migration state verified before seeding.
- Counts above captured before and after a second seeder run; they were identical.
- Every semester 1 through 8 has demo students.
- Course enrollments, attendance, academic records, performance metrics, and schedules are connected through actual foreign keys.
- `php artisan test --filter='CampusOperationsTest|DashboardDataTest|StudentMonitoringTest'`: **12 passed, 64 assertions**.
- No external Gemini/OpenAI request was made during data seeding or validation.

## Known limitations

- `FacultyRiskDemoSeeder` is an existing project seeder and selects an approved active faculty account for its three risk examples. Its data is synthetic, but it may attach to an existing local faculty identity; it is not used as a published synthetic login.
- Campus-service entities are populated only where supported by existing project seeders; no unsupported academic program, semester, or service entity was invented.
- Live OpenAI risk analysis remains subject to the provider quota limitation documented in the presentation-readiness report.

## Safe rerun instruction

From `backend/`, after confirming the local database is running and making a backup:

```powershell
php artisan db:seed --class=LocalPresentationDemoSeeder
```

The command is designed to be idempotent. Do not run it against a production or remote database.
