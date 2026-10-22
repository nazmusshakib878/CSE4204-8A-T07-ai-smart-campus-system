# Exhibition Demo Readiness Report

## Scope

This is a code and local-test audit only. No connection was made to local MariaDB port 3307, Aiven, or Render. No production records, environment variables, migrations, seeds, GitHub repository, or deployment were changed.

The supplied Render URL could not be independently reached by the available read-only browser tool, so live uptime, cold-start time, Aiven latency, memory consumption, and production logs remain unverified.

## What already exists

- Public registration accepts only `student` and `faculty`; admin self-registration is rejected at backend validation.
- Strong password validation requires 8+ characters with letters, mixed case, numbers, and symbols.
- Registration creates a `pending` account and returns no access token.
- Login blocks pending and non-approved accounts. All protected API routes also use the `approved` middleware; rejected accounts lose their current token, and admin rejection revokes all tokens.
- The admin area already lists pending student/faculty registrations and provides approve/reject actions. Controller checks require the authenticated user to be an approved admin.
- Newly approved visitors receive an empty student/faculty profile, not invented enrollments, attendance, grades, CGPA, or results. Existing dashboard empty states handle missing academic data.
- Frontend requests use a 15-second normal API timeout, session recovery keeps a session during temporary network failure, and protected pages show a loading state while session validation occurs.
- Gemini requests use bounded server timeouts, one retry for transient failures, no retry for daily quota exhaustion, 429 `Retry-After` forwarding, and client-side cooldown/request locking.
- Render configuration sets `autoDeployTrigger: off`; production demo seeding is disabled by default with `ONLINE_DEMO_SEED_ENABLED=false` and requires a private confirmation value for the one explicit command.

## Synthetic demo dataset and dependencies

The one-time Spring 2026 seeder identifies exactly:

- 40 synthetic students: `student11@gmail.com` through `student85@gmail.com` (five per semester, semesters 1-8).
- 8 synthetic faculty: `spring26.faculty.01@example.edu` through `spring26.faculty.08@example.edu`.
- Each synthetic student has a linked `students` record, Spring 2026 enrollments, attendance, academic records, and performance metric.
- Synthetic faculty are assigned as catalog-course faculty; schedules refer to those courses.

For the exhibition, the 40 students and 8 faculty are the minimum complete eight-semester showcase cohort. They should remain enabled if each semester dashboard/routine demonstration is required. They must not receive real emails/SMS; the notice delivery service already excludes the synthetic student email pattern from email delivery.

Do not disable or delete any account merely because it is not in these patterns. Any additional account must be classified from a read-only Aiven audit before action. The safe reduction candidates are only unrelated legacy sample accounts/courses after that audit; prefer `rejected`/disabled access over deletion, retain admins, and do not remove dependencies.

## Demonstrated and potential stability factors

### Demonstrated by code/tests

- Gemini quota/temporary-provider failures are handled without unlimited retries.
- Duplicate AI sends are prevented while a request is active.
- Authentication and dashboard calls have finite timeout/recovery behavior.
- The layout refreshes notice counts every 30 seconds, and admin users additionally refresh pending-user counts every 30 seconds. This is bounded but should be considered in capacity testing.

### Not yet proven in production

- Render cold starts/free-plan behavior. The checked render configuration requests the `starter` plan, but dashboard settings may differ.
- Aiven TLS connection time, connection pool exhaustion, query latency, and memory use.
- Actual API burst rate, browser errors, and QR-code mobile path.

These require Render/Aiven logs and a controlled production read-only verification; dataset size alone is not evidence of instability.

## Local changes prepared

- Corrected the AI-page test fixture so it preserves the same text element structure as the production alert component. This fixes two false-negative UI tests without changing runtime behavior.
- Earlier local work remains un-deployed: the Fall 2026 read-only reference and semester/section notice authorization migration/filter.

## Test results

- Backend auth, account-approval, rejection/token revocation, integrity, and Gemini quota/recovery: **40 passed, 194 assertions**.
- Frontend unit tests: **5 files, 12 passed**.
- Frontend lint: **passed**.
- Frontend production build: **passed**.

## Approval required before any production action

1. A read-only, TLS-verified Aiven backup and account/dependency audit.
2. A reversible disable/cleanup list for specifically identified synthetic accounts.
3. Any production migration (including the prepared notice section-targeting migration), data change, seed command, or environment change.
4. GitHub push, Render deployment, and post-deploy QR/mobile verification.
5. Any real notice, email, SMS, or account approval decision.
