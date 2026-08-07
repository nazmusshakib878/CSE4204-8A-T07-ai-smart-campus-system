# Week 8 — AI Integration

## AI features

### Student AI Smart Campus Assistant

Purpose: personalized academic and campus guidance for authenticated students.

`POST /api/ai/assistant` accepts only `{ "question": "..." }` from a Sanctum-authenticated student. Laravel trims and validates the question (required, string, maximum 1,000 characters), builds trusted context from MySQL, calls OpenAI, validates the returned text, and sends a predictable response:

```json
{"status": true, "data": {"answer": "...", "model": "gpt-4.1-mini"}}
```

The browser never sends academic values or an API key. Available context may include name, department, program, semester, courses, attendance percentage, recorded course results, latest CGPA, open tasks, and relevant recent notices. Passwords, tokens, credentials, and unnecessary personal data are excluded.

System prompt: the backend identifies the assistant as the NUBTK AI Smart Campus Assistant; it must use provided context where relevant, avoid invented university facts, provide concise supportive educational guidance, protect private data, and avoid claims of official academic decisions. Risk predictions are decision support, never final administrative decisions.

Example prompt: `How can I improve this semester?`

### AI-Assisted Student Academic Risk Analysis

Purpose: help faculty/admin identify students who may require academic support. It remains a separate, structured feature using attendance, missed classes, CGPA, courses, and course scores. The analyzer returns a risk score, risk level, prediction, reasons, and advice, and stores the result in `risk_alerts`.

## Workflow

```text
User
  ↓
React Frontend
  ↓
Laravel Backend
  ↓
MySQL Context
  ↓
OpenAI Responses API
  ↓
Response Validation
  ↓
Frontend Display
```

For risk analysis, the authenticated faculty/admin must also be authorized to access the student.

## Source-code map

- `backend/app/Services/OpenAiCampusAssistant.php` — system prompt, Responses API request, timeout/retry, response parsing.
- `backend/app/Http/Controllers/Api/AiAssistantController.php` — student authorization, input validation, and trusted context.
- `backend/app/Services/OpenAiRiskAnalyzer.php` — specialized strict JSON risk analysis.
- `backend/app/Http/Controllers/Api/StudentMonitoringController.php` — monitoring authorization, aggregation, and persisted risk alerts.
- `frontend/src/pages/AiAssistantPage.jsx` — real loading, answer, and error states.
- `frontend/src/services/api.js` — authenticated assistant request.
- `backend/tests/Feature/AiAssistantTest.php` — mocked assistant feature tests.

## OpenAI configuration and security

Selected platform: OpenAI Responses API. The model is the value of `OPENAI_MODEL`.

```dotenv
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
OPENAI_TIMEOUT=30
```

Set these only in `backend/.env` during local development or the Render backend service Environment settings for deployment. Do not put keys in React, Git, documentation, screenshots, or `.env.example`.

## Error handling and limitations

- Empty/invalid questions return JSON validation errors; unauthenticated and wrong-role requests are denied.
- Missing configuration, timeouts, network failures, invalid OpenAI payloads, rate limits, and temporary upstream failures return a friendly message without raw provider internals.
- Calls use a bounded timeout and two limited retries. There is no infinite retry loop.
- AI is optional: normal campus functions, baseline risk monitoring, and alerts remain available if OpenAI is unavailable.
- Guidance reflects only available records and is not an official university decision.

Future improvements: approved knowledge-base retrieval, carefully retained conversation history, and additional context only after it exists in the campus database.

## Verification

```powershell
cd backend
php artisan test

cd ../frontend
npm run lint
npm run build
```

OpenAI tests use Laravel HTTP fakes and never issue paid requests.
