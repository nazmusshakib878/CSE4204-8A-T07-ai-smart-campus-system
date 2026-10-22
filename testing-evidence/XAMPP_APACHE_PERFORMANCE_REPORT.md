# XAMPP Apache Local Performance Report

**Executed:** 2026-10-18 (Asia/Almaty)  
**Scope:** local XAMPP Apache, existing Laravel 12 API, existing React/Vite development frontend, and the unchanged local MySQL database.

## Result

**Use XAMPP Apache on `http://localhost:8081` for the backend during the final presentation.** In the repeated public API sample, Apache's warm median was lower than the existing `php artisan serve` endpoint. The normal `php artisan serve` configuration remains intact on port 8000 as the fallback.

No application source, UI, business logic, API contract, user account, database record, seed, migration, AI configuration, or banner file was changed. No paid/external AI request was made.

## Environment and compatibility

| Component | Observed result |
|---|---|
| XAMPP Apache | Apache/2.4.58 (Win64), PHP module loaded |
| XAMPP PHP | PHP 8.2.12 (Laravel requirement is `^8.2`) |
| Required extensions | `curl`, `mbstring`, `openssl`, `pdo_mysql`, `xml`, `zip`, `fileinfo`, tokenizer: present |
| Laravel | 12.62.0, Composer 2.9.5, dependencies installed |
| Database | Existing MySQL `ai_smart_campus`; `migrate:status` shows migrations ran; no migration/seed/reset executed |
| Frontend | Existing Vite server remains on 5173, with its normal proxy to 8000 unchanged |
| Auth | Sanctum bearer-token middleware is present on protected API routes; database session driver remains unchanged |
| CORS | Apache endpoint OPTIONS preflight returned 204 with the expected localhost:5173 allow-origin, methods, headers, and 3600-second max-age |

## Apache configuration

The existing port-80 XAMPP sites were preserved. Apache now also listens on **8081** and has this isolated virtual host:

- `DocumentRoot`: `C:/Users/Shakib/Desktop/CSE4204-8A-T07-ai-smart-campus-system-main/backend/public`
- `<Directory>` access is granted only to `backend/public`, with `Options -Indexes +FollowSymLinks` and `AllowOverride All` for Laravel rewriting.
- Apache `mod_rewrite` was already loaded.
- `.env`, `artisan`, `composer.json`, `composer.lock`, and `phpunit.xml` are explicitly denied by the virtual host.
- Per-site Apache access/error logs use `ai-smart-campus-8081-*` names.

Timestamped pre-change backups were created:

- `C:\xampp\apache\conf\httpd.conf.pre-ai-smart-campus-20261018-211922.bak`
- `C:\xampp\apache\conf\extra\httpd-vhosts.conf.pre-ai-smart-campus-20261018-211922.bak`

`httpd.exe -t` reported **Syntax OK** and `httpd.exe -S` reported `*:8081 localhost`.

## Endpoint and security verification

| Probe through Apache | Result |
|---|---|
| `GET /` | HTTP 200 |
| `GET /api/departments` | HTTP 200, same 821-byte response as development server probe |
| `GET /.env` | HTTP 403, confirming sensitive environment data is not served |
| CORS preflight to protected API route | HTTP 204 with `Access-Control-Allow-Origin: http://localhost:5173` |

## Performance comparison

### Conditions

- Same host, browser machine, PHP binary family, and existing local MySQL data.
- `php artisan serve`: `http://127.0.0.1:8000`.
- Apache: `http://127.0.0.1:8081`.
- 10 sequential `GET /api/departments` samples per server. This public endpoint is deliberately read-only and returns the same payload on both servers.
- Timing is client-observed end-to-end local HTTP time. First sample is the cold-ish sample after the respective server state; median is the warm comparison. Results are local-machine measurements, not production capacity results.

| Metric | Artisan serve | Apache | Apache change |
|---|---:|---:|---:|
| First sample | 1,082.2 ms | 862.7 ms | 20.3% faster |
| Warm median | 1,858.8 ms | 906.2 ms | 51.2% faster |
| P95 / maximum of 10 | 2,890.2 ms | 1,274.1 ms | 55.9% faster |

The very high local first/warm values include PHP application bootstrap, MySQL access, and local-process scheduling. Apache's persistent multi-threaded serving model produced the lower observed median in this run; it does not change the database query work or UI code.

### Browser regression / visible readiness

The existing Playwright public-navigation test passed on both paths:

| Path | Outcome | Suite wall time |
|---|---|---:|
| Normal Vite -> `php artisan serve` proxy | Public home visible; protected `/admin` redirects to login | 3.7 s |
| Temporary Vite -> Apache proxy | Same assertions passed | 4.3 s |

The Apache browser check used a temporary port-5174 Vite proxy only for the test, then removed it. The normal Vite configuration was never changed. Suite wall time is dominated by launching Chrome/test infrastructure and is not an API timing metric.

## Authentication and role workflows

- Existing route inspection confirms Admin, Faculty, and Student dashboards; academic management; monitoring; campus services; attendance; results; profile/session restoration; and navigation are protected by Sanctum and role/approval middleware as before.
- The focused regression suite passed: **12 tests, 64 assertions**. It covers database-backed dashboard scopes, admin/faculty/student authorization, academic results/CGPA, campus services, routines, attendance-related operations, student monitoring, and safe AI error paths. PHPUnit uses in-memory SQLite from `phpunit.xml`, so it did not touch local MySQL.
- Frontend lint and the frontend Vitest command completed successfully.
- Fresh token-issuing login calls were intentionally not made because this task's strict no-database-record-change rule would create personal access-token records. The repository's same-date local verification already records successful Admin, Faculty, and Student authenticated browser/API workflows against this unchanged database. This report therefore does not overclaim a new authenticated Apache browser measurement.

## Remaining limitations

1. Fresh, fully authenticated, role-by-role visible data-readiness timings require token/session creation. Run them only if temporary token records are expressly permitted, or benchmark with an approved disposable database copy.
2. Laravel local debug mode remains enabled and config/routes are not cached, intentionally preserving development behavior. Do not apply production cache commands permanently while actively developing.
3. The current CORS configuration supports the normal Vite origin. A direct frontend origin on another port would need a temporary `CORS_ALLOWED_ORIGINS` addition; the presentation setup below uses Vite's same-origin proxy and needs none.

## Presentation startup

1. Start XAMPP **MySQL** and **Apache**. Apache now listens on both 80 and 8081.
2. Confirm `http://localhost:8081/api/departments` returns JSON.
3. In `frontend`, run the existing frontend command:

```powershell
npm run dev
```

4. For the presentation only, start Vite with an environment-scoped proxy target (this does not edit project files):

```powershell
$env:VITE_API_PROXY_TARGET = 'http://127.0.0.1:8081'
npm run dev
```

`vite.config.js` now reads `VITE_API_PROXY_TARGET` and otherwise retains its exact original `http://127.0.0.1:8000` target. This is a development-only, per-process override: no `.env` file, production setting, UI, API contract, or database data is changed. Close any currently running Vite instance before setting the variable, because Vite reads it at startup.

Fallback remains:

```powershell
cd backend
php artisan serve
cd ..\frontend
Remove-Item Env:VITE_API_PROXY_TARGET -ErrorAction SilentlyContinue
npm run dev
```

## Recommendation

Apache is the faster verified backend for local read-only API work and is safely configured. For the presentation, start Vite with `VITE_API_PROXY_TARGET=http://127.0.0.1:8081` as documented above; keep `php artisan serve` available as fallback. If freshly measured authenticated role-by-role timings are required, obtain approval for temporary token records or use an approved disposable database copy.
