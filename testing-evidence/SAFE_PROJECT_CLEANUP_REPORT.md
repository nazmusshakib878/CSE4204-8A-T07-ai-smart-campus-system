# Safe Project Cleanup Report

Date: 2026-10-19

## Scope and protections

This was a cleanup-only review. No source code, configuration, environment files, database files, SQL backups, MariaDB recovery/validation directories, official documents, testing evidence, application assets, or tracked files were modified or removed.

The following protected areas were explicitly retained:

- `database/recovery-backups/`
- `database/recovery-validation/`
- `backend/database/backups/`
- `testing-evidence/`
- `screenshots/`
- all untracked scripts and seeders
- all Git-tracked files and existing user modifications

`C:\xampp\mysql\` and the clean MariaDB instance were not accessed or changed.

## Audit result

The only confirmed disposable artifacts were five untracked PNG files under `tmp/pdfs/final-testing-report/`. They were PDF-render preview iterations, were the only files in `tmp/`, had no references outside `tmp/`, and were not tracked by Git.

No duplicate files were deleted. The previews were moved to quarantine rather than permanently deleted.

## Files moved to quarantine

Quarantine root: `C:\Users\Shakib\Desktop\AI-Smart-Campus-System-Quarantine\20261019-safe-cleanup`

| Original path | Quarantine path | Reason |
| --- | --- | --- |
| `tmp/pdfs/final-testing-report/page-1.png` | `C:\Users\Shakib\Desktop\AI-Smart-Campus-System-Quarantine\20261019-safe-cleanup\tmp\pdfs\final-testing-report\page-1.png` | Untracked, unreferenced PDF-render preview. |
| `tmp/pdfs/final-testing-report/page-2.png` | `C:\Users\Shakib\Desktop\AI-Smart-Campus-System-Quarantine\20261019-safe-cleanup\tmp\pdfs\final-testing-report\page-2.png` | Untracked, unreferenced PDF-render preview. |
| `tmp/pdfs/final-testing-report/fixed-page-1.png` | `C:\Users\Shakib\Desktop\AI-Smart-Campus-System-Quarantine\20261019-safe-cleanup\tmp\pdfs\final-testing-report\fixed-page-1.png` | Untracked, unreferenced revised PDF-render preview. |
| `tmp/pdfs/final-testing-report/fixed-page-2.png` | `C:\Users\Shakib\Desktop\AI-Smart-Campus-System-Quarantine\20261019-safe-cleanup\tmp\pdfs\final-testing-report\fixed-page-2.png` | Untracked, unreferenced revised PDF-render preview. |
| `tmp/pdfs/final-testing-report/final-page-2.png` | `C:\Users\Shakib\Desktop\AI-Smart-Campus-System-Quarantine\20261019-safe-cleanup\tmp\pdfs\final-testing-report\final-page-2.png` | Untracked, unreferenced final PDF-render preview. |

All five files were SHA-256 verified after the move. The project source directory contains no remaining files in that temporary preview folder.

## Verification

| Check | Result |
| --- | --- |
| Frontend lint (`npm run lint`) | Passed |
| Frontend production build (`npm run build`) | Passed |
| Frontend tests (`npm run test`) | Passed: 5 files, 12 tests (reverified 2026-10-20) |
| Laravel tests (`php artisan test`) | Passed: 96 tests, 509 assertions (reverified 2026-10-20) |
| Playwright navigation (`npm run test:e2e`) | Passed: 1 test |

The first Playwright attempt could not connect because the existing Vite server was listening on IPv6 loopback (`::1`) while the configuration targets `127.0.0.1`. A temporary IPv4 Vite listener was used for the rerun; it was stopped afterward, and the pre-existing IPv6 listener was left running.

## Restoration

To restore the quarantined previews, copy the required file from the quarantine path above back to its original relative path under the project. For example:

```powershell
Copy-Item 'C:\Users\Shakib\Desktop\AI-Smart-Campus-System-Quarantine\20261019-safe-cleanup\tmp\pdfs\final-testing-report\page-1.png' 'C:\Users\Shakib\Desktop\CSE4204-8A-T07-ai-smart-campus-system-main\tmp\pdfs\final-testing-report\page-1.png'
```

No restoration is required for application operation: the files were not referenced by code, tests, build scripts, or documentation.
