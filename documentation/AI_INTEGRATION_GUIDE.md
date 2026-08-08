# AI Integration Guide

## Overview

The AI Smart Campus System uses two server-side AI integrations:

- **Gemini Student Assistant** provides personalized academic guidance to authenticated students.
- **OpenAI Academic Risk Analyzer** provides structured decision support for authorized faculty and administrators.

Both integrations run only through the Laravel backend. API keys, system prompts, and trusted student context are never sent from or exposed to the React frontend.

## Student AI Smart Campus Assistant

The "POST /api/ai/assistant" endpoint accepts a JSON request containing a single question:

~~~json
{
  "question": "How can I improve this semester?"
}
~~~

The endpoint is restricted to authenticated students. Laravel validates the question, loads authorized academic context, calls Gemini, validates the response, and returns:

~~~json
{
  "status": true,
  "data": {
    "answer": "...",
    "model": "gemini-3.6-flash"
  }
}
~~~

Available context includes the student's name, department, program, attendance percentage, current CGPA, and enrolled courses. Passwords, access tokens, API credentials, and another student's records are excluded.

The assistant must:

- use only supplied context for university-specific answers;
- clearly state when information is unavailable;
- provide concise and practical guidance;
- protect private information;
- avoid presenting guidance as an official university decision.

## AI-Assisted Academic Risk Analysis

The risk analyzer helps authorized faculty and administrators identify students who may need support. It uses OpenAI to evaluate permitted indicators such as attendance, missed classes, CGPA, enrolled courses, and course scores.

It returns a risk score and level, prediction summary, contributing reasons, and recommended support actions. Results are persisted in "risk_alerts". They are decision-support records only; final decisions require human review.

## Request Flow

~~~text
Authorized user
  -> React frontend
  -> Laravel API and authorization
  -> Trusted database context
  -> Gemini or OpenAI provider
  -> Backend response validation
  -> Frontend display
~~~

## Source-Code Map

- "backend/app/Services/GeminiCampusAssistant.php" - Gemini request, system prompt, timeout, and response parsing.
- "backend/app/Http/Controllers/Api/AiAssistantController.php" - student authorization, validation, and trusted context.
- "backend/app/Services/OpenAiRiskAnalyzer.php" - structured OpenAI academic risk analysis.
- "backend/app/Http/Controllers/Api/StudentMonitoringController.php" - authorization and persisted risk alerts.
- "frontend/src/pages/AiAssistantPage.jsx" - assistant loading, answer, and error states.
- "frontend/src/services/api.js" - authenticated frontend API requests.
- "backend/tests/Feature/AiAssistantTest.php" - mocked assistant feature tests.
- "backend/tests/Feature/StudentMonitoringTest.php" - risk-analysis authorization and persistence tests.

## Environment Configuration

Configure credentials only in "backend/.env" locally or in protected production environment settings.

~~~dotenv
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3.6-flash
GEMINI_TIMEOUT=30

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
OPENAI_TIMEOUT=30
~~~

Never put API keys in React source code, Git, committed environment examples, documentation, or screenshots.

## Reliability and Security

- Invalid questions return validation errors.
- Unauthenticated and unauthorized roles are rejected.
- Missing credentials, timeouts, rate limits, invalid responses, and upstream failures return safe messages.
- Provider calls use bounded timeouts.
- Normal campus features remain available when AI is unavailable.
- Student context is assembled by the backend instead of trusted from browser input.
- AI never directly publishes results, changes marks, rejects accounts, or makes disciplinary decisions.

## Verification

~~~powershell
cd backend
php artisan test

cd ../frontend
npm test
npm run lint
npm run build
~~~

Automated tests use mocked provider responses and do not make paid AI requests.
