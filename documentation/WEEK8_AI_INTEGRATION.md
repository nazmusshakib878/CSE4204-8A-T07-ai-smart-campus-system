# Week 8 — AI Integration

## Feature

AI-assisted Student Academic Risk Analysis for faculty members.

The system combines real attendance, CGPA, missed-class, course, and score data with an OpenAI analysis. The AI returns a structured risk score, risk level, reasons, prediction, and supportive action advice.

## Required workflow

```text
Faculty clicks "Analyze with AI"
        |
        v
React frontend
POST /api/faculty/students/{student}/analyze-risk
        |
        v
Laravel validates login, role, and course ownership
        |
        v
Laravel collects attendance + CGPA + course scores from MySQL
        |
        v
OpenAI Responses API receives a bounded academic-risk prompt
        |
        v
Structured JSON response (score, level, reasons, advice)
        |
        v
Laravel stores the result in risk_alerts
        |
        v
React refreshes Student Monitoring and Risk Alerts
```

This implements: `User Input → Backend → AI API → AI Response → Frontend`.

## Source-code map

- `app/Services/OpenAiRiskAnalyzer.php` — OpenAI request, prompt, JSON schema, response parsing
- `app/Http/Controllers/Api/StudentMonitoringController.php` — access control, data aggregation, charts, AI endpoint
- `app/Models/RiskAlert.php` — persisted AI analysis
- `routes/api.php` — authenticated faculty endpoints
- `frontend/src/pages/StudentMonitoringPage.jsx` — live monitoring and AI action
- `frontend/src/pages/RiskAlertsPage.jsx` — saved AI alerts and faculty follow-up
- `frontend/src/services/api.js` — frontend/backend requests
- `tests/Feature/StudentMonitoringTest.php` — filtering, access, AI mock, persistence tests

## API endpoints

`GET /api/faculty/student-monitoring` returns the authenticated faculty member's course students, charts, and risk summary.

`POST /api/faculty/students/{studentDatabaseId}/analyze-risk` sends that student's academic indicators from Laravel to OpenAI and stores the structured response.

## Prompt engineering

The backend prompt:

- Gives the model one role: academic early-warning assistant
- Supplies only academic indicators
- Prohibits protected/personal-trait inference
- Defines the 0–100 risk scale
- Requests concise and supportive advice
- Uses strict JSON schema output
- Limits output to 350 tokens
- Uses low temperature for consistent results

Example input:

```text
attendance_percentage: 62
missed_classes: 12
cgpa: 2.45
course_scores: CSE 4103 = 58
baseline_risk_score: 68
```

Example structured response:

```json
{
  "risk_score": 72,
  "risk_level": "high",
  "prediction": "The student is likely to need academic support.",
  "reasons": ["Attendance is below target", "CGPA trend needs attention"],
  "advice": "Schedule an advising meeting and provide focused learning resources."
}
```

## Setup

Add the real key only to `backend/.env`:

```dotenv
OPENAI_API_KEY=your_real_key_here
OPENAI_MODEL=gpt-4.1-mini
OPENAI_TIMEOUT=30
```

Never put the key in React, Git, screenshots, or `.env.example`.

Run `php artisan config:clear`, `php artisan migrate`, the backend server, and the frontend development server.

## Database and access control

Monitoring reads `students`, `faculty`, `courses`, `academic_records`, `attendance_records`, `performance_metrics`, and `risk_alerts`.

- New registrations create student/faculty profile rows.
- A migration backfills profiles for existing users.
- Sanctum authentication is required.
- Student accounts receive HTTP 403.
- Faculty can access only assigned-course students; same-department fallback is used before courses are assigned.
- Administrators can view all students.
- The API key never reaches the browser.
- A deterministic baseline score remains visible when AI is not configured.
- AI is decision support; faculty makes the final academic decision.

## Verification

Backend: `php artisan test`

Frontend: `npm run lint` and `npm run build`

The backend suite mocks OpenAI and verifies structured output, persistence, faculty filtering, and access denial.

## Official documentation

- Responses API: https://developers.openai.com/api/reference/resources/responses/methods/create
- Text generation: https://developers.openai.com/api/docs/guides/text
- Structured outputs: https://developers.openai.com/api/docs/guides/structured-outputs
