# AI Assistant Error Diagnosis Report

**Executed:** 2026-10-18  
**Scope:** local Laravel AI Assistant follow-up handling, Gemini error mapping,
frontend error display, and mocked-provider regression coverage.

## Confirmed root cause

The failure was an application bug in
[`CampusContextSelector.php`](../backend/app/Services/CampusContextSelector.php).
Follow-up questions caused this code path:

```php
collect($history)->take(-4)->pluck('content')->filter('is_string')
```

Laravel collection callbacks receive both the item and its key. PHP's
`is_string()` accepts only one argument, so the callback raised:

```text
ArgumentCountError:
is_string() expects exactly 1 argument, 2 given
```

The exception occurred before the Gemini request was dispatched. Therefore the
failure was not caused by Gemini quota, a Gemini HTTP 5xx response, oversized
academic context, CORS, or excessive provider retries.

## Captured incident evidence

The local Laravel log recorded the affected faculty requests:

```text
[2026-10-18 16:18:05] local.DEBUG: AI message processing started
[2026-10-18 16:18:05] local.ERROR: is_string() expects exactly 1 argument, 2 given
[2026-10-18 16:18:05] ... ArgumentCountError ... Arr.php:1240
```

The same exception was recorded again at 16:18:10 for a follow-up course
question. The authenticated log entry identified the local user only by
`userId`; no key, token, password, or academic record was copied into this
report.

### HTTP and frontend behavior

Because the exception escaped `AiAssistantController::ask()`, the affected
POST `/api/ai/assistant` request was an application server error (HTTP 500
class), not the controller's safe Gemini 429/503 response. The persisted log
does not contain a browser timing or access-log record, so an exact wire
duration for the historical request cannot be claimed. The log shows the
exception immediately after request processing began.

The frontend then displayed its generic retry state:

```text
Something went wrong. Please try again later.
```

The frontend Axios normalizer correctly handles explicit JSON 429 and 503
responses. The incident bypassed that safe mapping because the backend
exception was not caught by the AI controller.

## Fix applied

Changed the callback to a one-argument closure:

```php
filter(fn ($content) => is_string($content))
```

This is the smallest safe fix. It preserves conversation history, follow-up
selection, authorization, context limits, Gemini configuration, retry policy,
and API contracts.

Added a regression test for:

```text
What courses am I taking?
Oi course er class kobe?
```

The test verifies the follow-up reaches the schedule context and completes
without a backend exception.

## Exact files modified for this diagnosis

- [`backend/app/Services/CampusContextSelector.php`](../backend/app/Services/CampusContextSelector.php)
- [`backend/tests/Feature/AiAssistantTest.php`](../backend/tests/Feature/AiAssistantTest.php)
- [`testing-evidence/AI_ASSISTANT_ERROR_DIAGNOSIS_REPORT.md`](./AI_ASSISTANT_ERROR_DIAGNOSIS_REPORT.md)

Other pre-existing worktree changes were preserved and were not part of this
diagnosis fix.

## Provider and frontend investigation

### Gemini quota and provider failures

Existing mocked tests confirm the intended behavior:

- Gemini HTTP 429 daily quota returns HTTP 429 without retrying.
- Temporary 429 responses retry once when the quota metadata is not daily.
- Temporary HTTP 500/503 responses follow the existing bounded retry behavior.
- Connection failures become the safe unavailable response.
- Empty Gemini responses become the safe unavailable response.

Historical logs also contain genuine Gemini quota events from earlier testing,
including `RESOURCE_EXHAUSTED` daily and per-minute limits. Those are separate
provider limitations and were not the cause of the 16:18 application failure.

### Authentication, CORS, proxy, and context

- The controller continues to require an authenticated approved student or
  faculty user.
- Conversation IDs remain owner-scoped.
- Faculty and student context retrieval remains role-scoped.
- The exception occurred before the provider call and was unrelated to CORS or
  proxy routing.
- The corrected follow-up test confirms context selection proceeds normally.
- No real Gemini request was made during diagnosis.

## Verification

| Check | Result |
|---|---|
| AI assistant feature suite after fix | PASS - 21 tests, 101 assertions |
| Complete Laravel suite after fix | PASS - 86 tests, 432 assertions |
| Faculty/student context isolation suite | Previously PASS - 22 tests, 117 assertions |
| Frontend lint | PASS |
| Frontend production build | PASS |
| Frontend Vitest | PASS - 4 files, 10 tests |
| PHP syntax checks | PASS |
| `git diff --check` | PASS |
| Simple greeting mocked provider path | PASS |
| Faculty assigned-course context | PASS |
| Mixed-language follow-up | PASS after fix |
| Bangla/mixed response handling | PASS |
| Gemini timeout/quota/error handling | PASS through existing mocked tests |
| Retry functionality | PASS through existing frontend and backend tests |

## Resolution status

**Resolved for the confirmed application bug.** The exact `ArgumentCountError`
was reproduced from the log evidence, corrected with a minimal closure change,
and covered by a passing follow-up regression test.

The fix does not guarantee that Gemini will always be available. Provider
quota exhaustion, network failure, and upstream 5xx responses remain external
conditions and continue to produce the existing safe 429/503 behavior.

## Remaining limitations

1. The original browser Network-tab export was not retained, so the historical
   request's exact duration and raw response body cannot be reconstructed.
   The HTTP 500-class outcome is established by the uncaught Laravel exception.
2. No live Gemini request was sent to avoid paid traffic and transmission of
   academic context.
3. Existing provider quota limitations remain unchanged.
