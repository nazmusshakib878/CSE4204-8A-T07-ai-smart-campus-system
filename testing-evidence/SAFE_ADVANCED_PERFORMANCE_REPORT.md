# Safe Advanced Performance Report

**Executed:** 2026-10-18 (local Windows/XAMPP environment)  
**Scope:** React/Vite frontend, Laravel 12 API, XAMPP Apache/PHP, and the existing local MySQL database.

## Executive result

The system can safely be faster in the current XAMPP setup by using the already
configured Apache virtual host on port 8081 instead of the single-process
`php artisan serve` server. A second confirmed application bottleneck was the
faculty dashboard query shape; the existing working-tree change batches its
enrollment, grade, and attendance lookups without changing response fields,
permissions, ordering, or academic calculations.

No additional source optimization was applied during this measurement run. No
database migration, seed, reset, record update, token creation, AI request, or
banner-file change was performed. Existing uncommitted work was preserved.

## Measured baseline

Measurements used the same machine, existing database, endpoint, payload, and
sequential request pattern. Times are client-observed local HTTP times, not
production capacity measurements.

### Public API: `GET /api/departments`

| Server | First sample | Warm median (10 samples) | P95/max |
|---|---:|---:|---:|
| `php artisan serve`, port 8000 | 4,197.4 ms | 2,122.9 ms | 4,197.4 ms |
| XAMPP Apache, port 8081 | 1,602.8 ms | 611.6 ms | 1,602.8 ms |
| Apache improvement | 61.8% | 71.2% | 61.8% |

Both responses were HTTP 200 and 821 bytes. The earlier supplied report
measured a 906.2 ms Apache warm median; the new run was faster but remains
consistent with Apache being the lower-latency option. Local process scheduling
and the development configuration contribute materially to these values.

### Page responses

| URL | First sample | Five-sample median |
|---|---:|---:|
| Vite frontend, port 5173 | 99.8 ms | 35.9 ms |
| Laravel root through Apache, port 8081 | 399.9 ms | 756.7 ms |
| Laravel root through artisan, port 8000 | 820.7 ms | 673.0 ms |

The frontend page is a shell; authenticated readiness is dominated by API
requests and was not re-benchmarked with newly issued tokens because doing so
would create database records.

## Confirmed bottleneck and safe optimization

The faculty dashboard previously issued per-schedule enrollment counts and
per-course grade and attendance queries. For Demo Faculty 01, with three
assigned courses and four schedule rows, the recorded query shape changed from
15 minimum queries to 8 (7 fewer, 46.7%).

The existing batching in
[`DashboardController.php`](../backend/app/Http/Controllers/Api/DashboardController.php)
now:

- groups enrollment counts by course in one query;
- loads grades for all assigned courses together;
- reuses the already-loaded attendance collection by course.

The measured report evidence states that response fields, role scoping,
ordering, and calculations are unchanged. No latency claim is made for this
change because an isolated before/after request trace was not available under
identical authenticated conditions.

## Apache, PHP, Laravel, and MySQL investigation

### Apache

- Apache serves the Laravel `backend/public` virtual host on port 8081.
- `httpd.exe -t` was previously verified as **Syntax OK**.
- The original port-80 configuration remains available.
- Timestamped backups exist at:
  `C:\xampp\apache\conf\httpd.conf.pre-ai-smart-campus-20261018-211922.bak`
  and
  `C:\xampp\apache\conf\extra\httpd-vhosts.conf.pre-ai-smart-campus-20261018-211922.bak`.

### PHP OPcache

OPcache was **not confirmed enabled** in the available PHP runtime:

- `C:\xampp\php\php.exe -m` did not list `opcache`.
- CLI `php -m` also did not list `opcache`.
- The inspected XAMPP Apache configuration did not expose a PHP OPcache
  module or OPcache directives.

No OPcache setting was added because the Apache SAPI/module path was not safely
verifiable from the application repository. For a final presentation, enable
OPcache only in the XAMPP PHP configuration after confirming Apache uses that
same PHP binary, with development-safe settings:

```ini
opcache.enable=1
opcache.validate_timestamps=1
opcache.revalidate_freq=0
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
```

Restart Apache and verify through the Apache SAPI before retaining the change.
Do not use `validate_timestamps=0` during development.

### Laravel

The local application is in debug mode with config and routes not cached.
That is appropriate for active development and preserves source-change
invalidation. Permanent `config:cache`, `route:cache`, or aggressive
application caching was not applied because it could stale development
configuration or user-specific data.

Frontend route-level lazy loading already exists in
[`App.jsx`](../frontend/src/App.jsx). No additional splitting or rendering
change was justified by measurements.

### MySQL and indexes

Representative `EXPLAIN` checks showed:

- enrollment aggregation uses `course_student_term_unique` as an index-only
  plan;
- attendance filtering uses the existing student/course foreign-key indexes;
- schedule lookup scans only 28 local rows and sorts them.

The existing schema has indexes for the inspected foreign keys and unique
academic relationships. No new index was added because the observed dataset
does not establish a migration-worthy bottleneck, and adding one would create
unnecessary schema risk.

Database counts captured before final verification were stable and read-only:
93 users, 77 students, 14 faculty, 32 courses, 202 enrollments, 2,010
attendance records, and 250 academic records.

## Duplicate requests and frontend rendering

Repository inspection found Axios centralized in
[`api.js`](../frontend/src/services/api.js), route-level lazy loading, and no
confirmed duplicate request trace requiring a code change. The existing
development proxy override in
[`vite.config.js`](../frontend/vite.config.js) is process-scoped and retains
the original port-8000 default.

## Verification

| Check | Result |
|---|---|
| Frontend lint | PASS |
| Frontend production build | PASS |
| Frontend Vitest | PASS - 4 files, 10 tests |
| Complete Laravel suite | PASS - command exited successfully |
| Focused authorization/integrity suite | PASS - 22 tests, 120 assertions |
| Playwright workflow suite | PASS - 1 test |
| Apache, artisan, and Vite endpoint readiness | PASS |
| Responsive matrix | PASS - 320, 375, 390, 428, 768, 1440 px; no horizontal overflow |
| Database row-count consistency | PASS |
| Authenticated Admin/Faculty/Student live timing | Not freshly rerun; would require creating tokens |

The supplied testing evidence additionally records database-backed Admin,
Faculty, and Student visual workflows, including responsive checks and the
faculty dashboard query reduction. The automated Playwright suite in this
repository currently covers public navigation and protected-route redirect;
it is not a substitute for authenticated role workflow timing.

## Security and behavior preservation

- No user-specific or sensitive academic data was globally cached.
- Sanctum authentication and role authorization were not weakened.
- No API response contract or academic calculation was changed.
- No student, faculty, attendance, result, or account data was modified.
- AI provider logic, credentials, models, timeouts, and billing behavior were
  not changed.
- `GET /.env` remained blocked by the Apache virtual-host configuration.

## Rollback

1. Stop the presentation Vite process.
2. Remove the per-process `VITE_API_PROXY_TARGET` variable:

   ```powershell
   Remove-Item Env:VITE_API_PROXY_TARGET -ErrorAction SilentlyContinue
   ```

3. Use the existing fallback:

   ```powershell
   cd backend
   php artisan serve
   cd ..\frontend
   npm run dev
   ```

4. If Apache configuration must be restored, stop Apache, back up the current
   files, restore the two timestamped `.bak` files listed above, validate with
   `httpd.exe -t`, and restart Apache.
5. If the faculty batching change is separately reverted, restore only
   [`DashboardController.php`](../backend/app/Http/Controllers/Api/DashboardController.php)
   from the approved repository revision; do not reset the worktree globally.

## Recommended final presentation configuration

Use XAMPP MySQL and Apache, with the isolated Laravel virtual host on port
8081. Start Vite with:

```powershell
$env:VITE_API_PROXY_TARGET = 'http://127.0.0.1:8081'
cd frontend
npm run dev
```

Keep `php artisan serve` on port 8000 as the immediate rollback path. Enable
OPcache only after confirming the Apache SAPI configuration and retaining
timestamp validation for development. Do not add persistent application
caching or schema changes based on the current evidence.

**Conclusion:** Apache is a measured, reversible improvement over the local
artisan server, and the faculty dashboard batching is the only confirmed
application-side optimization in scope. Further optimization should wait for
an authenticated trace on an approved disposable database or an explicitly
authorized token-preserving benchmark.
