# Spring 2026 Official A-Sections Routine — Local Verification Report

Date: 2026-10-19  
Scope: local MariaDB only (`127.0.0.1:3307`); Render was not contacted, changed, or deployed.

## Source and import decision

Source: `Spring_2026_A_Sections_Official_Routine.csv`, derived from the supplied official PDF.

- CSV rows reviewed: 109
- Validated and imported: 96
- Excluded pending source correction: 13
- Imported sections: `1A`, `2A`, `3A`, `4A`, `5A`, `6A`, `7A`, `8A`
- Effective date retained as source metadata: 2026-08-01

All 13 excluded rows have the malformed source time `04:30 PM - 05:40 AM`, an empty structured end time, and `source_review_required=YES`. They were not imported. No end time was inferred, and instructor initials were not converted into faculty identities.

| Section | Day | Start | Room | Course | Initials | PDF page |
| --- | --- | --- | --- | --- | --- | ---: |
| 1A | Tue | 16:30 | 107 | CSE 1101 | MAR | 2 |
| 1A | Wed | 16:30 | 305 | ENG 1105 | AR | 3 |
| 2A | Thu | 16:30 | LAB 1 (201) | CSE 1202 | MSP | 3 |
| 3A | Sat | 16:30 | 107 | MATH 2103 | PPG | 2 |
| 3A | Thu | 16:30 | 302 | MATH 2103 | PPG | 3 |
| 4A | Sun | 16:30 | 302 | CSE 2213 | MFA | 2 |
| 4A | Tue | 16:30 | 102 | IPE 2201 | AHK | 2 |
| 4A | Wed | 16:30 | 302 | MATH 2201 | PPG | 3 |
| 5A | Sun | 16:30 | LAB 2 (206) | CSE 3108 | MTH | 2 |
| 6A | Sun | 16:30 | 305 | GED 3201 | VI | 2 |
| 6A | Tue | 16:30 | LAB 1 (201) | CSE 3202 | MTH | 2 |
| 7A | Wed | 16:30 | LAB 3 (301) | CSE 4114 | VK | 3 |
| 8A | Sun | 16:30 | LAB 4 (106) | CSE 4204 | RM | 2 |

## Local database protection

Pre-import backup verified:

- File: `backend/database/backups/ai_smart_campus-pre-official-routine-20261019-013000.sql`
- Size: 536,143 bytes
- SHA-256: `F0802EFD7C0A90A9EC968DE73A6424BFBC14EDA902224F123AA015EC9CFDD40C`

The import is transaction-wrapped and idempotent. It replaces only the former Spring 2026 routine slots for the eight authorized A-sections and legacy `SPR26-A` demo schedule scope. It does not delete students, user accounts, faculty, results, attendance, or other sections/terms.

## Post-import evidence

| Section | Official schedule rows | Demo students | Semester |
| --- | ---: | ---: | ---: |
| 1A | 12 | 5 | 1 |
| 2A | 13 | 5 | 2 |
| 3A | 11 | 5 | 3 |
| 4A | 10 | 5 | 4 |
| 5A | 14 | 5 | 5 |
| 6A | 13 | 5 | 6 |
| 7A | 12 | 5 | 7 |
| 8A | 11 | 5 | 8 |

- Imported official schedule rows: 96
- Imported rows with null/invalid end time: 0
- Existing Spring demo academic records preserved: 105
- Existing Spring demo attendance records preserved: 1,050

Schedule records now retain section, shift, instructor initials, room, class type, and source-page reference. Dashboard and AI schedule context were corrected to be section-scoped and to use the database weekday convention (`0=Sunday` through `6=Saturday`).

## Verification

- `php artisan migrate --force --no-interaction`: passed
- `php artisan db:seed --class=OfficialSpring2026RoutineSeeder --no-interaction`: passed; `96 verified rows; 13 flagged source rows excluded`
- `php artisan test`: passed — 86 tests, 432 assertions (includes authentication, dashboard, faculty access scope, routine management, and AI Assistant context tests)
- `npm run build`: passed

One optional interactive `php artisan tinker` inspection could not run because PsySH attempted to write command history outside the workspace and the sandbox rejected it: `Writing to C:/Users/Shakib/AppData/Roaming/PsySH/psysh_history is not allowed.` No permission change was made. This does not affect migrations, seeded data, automated login tests, or the production frontend build.

## Faculty identity boundary

The CSV supplies instructor initials, not authoritative faculty identities. Initials are stored exactly on each validated routine slot. Existing faculty accounts and course faculty assignments were preserved; no initials were guessed into people or accounts. A verified official faculty directory/initials mapping is required before making person-level faculty assignment claims for these new routine rows.

## Render status

Render remains on hold. No production database access, deployment, or seed execution occurred.
