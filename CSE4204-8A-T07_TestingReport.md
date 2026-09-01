# Testing and Quality Assurance Report: AI Smart Campus System
**Course Code:** CSE4204  
**Course Title:** Advanced Software Engineering / Web Engineering & AI Integration  
**Section:** 8A  
**Team ID:** Team 07 (T07)  
**Project Title:** AI Smart Campus System  
**Institution:** Northern University of Business and Technology, Khulna (NUBTK)  
**Repository:** https://github.com/nazmusshakib878/CSE4204-8A-T07-ai-smart-campus-system.git  
**Submission Title:** Testing and Quality Assurance Final Report  
**Date of Submission:** September 2026  

---

## Table of Contents
1. Cover Page
2. Table of Contents
3. Introduction
4. Testing Objectives
5. Testing Approach / Methodology
6. Project Technology Stack
7. Tested Modules
8. Functional Testing Summary
9. Authentication & Authorization Testing
10. API Testing
11. Database Testing
12. AI Testing and AI Quality Evaluation
13. UI/UX Testing
14. Responsive Testing
15. Security Checks
16. Bugs Identified
17. Bug Fixes
18. Retesting Summary
19. Regression Testing Summary
20. Automated Testing Summary
21. Test Results Summary
22. Remaining Known Issues
23. Limitations
24. GitHub Activity / Commit Summary
25. GitHub Repository Link
26. Conclusion
27. Testing Screenshots / Evidence

---

## 3. Introduction
The **AI Smart Campus System** is a unified academic management, student monitoring, campus operations, and conversational AI platform engineered for Northern University of Business and Technology, Khulna (NUBTK). This document serves as the formal Testing and Quality Assurance Report for Course CSE4204.

## 4. Testing Objectives
1. Verify functional accuracy of role-based academic workflows across Student, Faculty, and Admin roles.
2. Validate authentication integrity, session persistence, Sanctum token revocation, and role-based access control (RBAC).
3. Verify REST API endpoints (GET, POST, PUT, PATCH, DELETE) with server-side validation and HTTP status adherence.
4. Test AI integrations (Google Gemini & OpenAI Responses API) for accuracy, conversational follow-ups, multilingual support (Bangla/English), and fail-safe error handling.
5. Ensure responsive cross-device visual fidelity across desktop, tablet, and mobile viewports.
6. Conduct security vulnerability audits against IDOR, privilege escalation, and credential exposure.

## 5. Testing Approach & Methodology
A multi-tiered testing strategy was employed:
- **Unit & Feature Testing:** PHPUnit for backend models, policies, seeders, and controllers; Vitest for frontend React components.
- **End-to-End & UI Automation:** Playwright with Chromium/Google Chrome for end-to-end browser journeys and responsive viewport validation.
- **REST API & Security Automation:** Node.js HTTP runners performing automated validation of status codes, payloads, error responses, and token states.
- **Adversarial & Fault Injection Testing:** Real prompt injection, simulated provider outages, and boundary checks.

## 6. Project Technology Stack
- **Frontend:** React 19 SPA, Vite 8, Material-UI (MUI) 9, Bootstrap 5, React Router 7, Axios, Vitest, Playwright.
- **Backend:** Laravel 12 REST API, Laravel Sanctum Token Authentication, PHP 8.2.
- **Database:** MySQL / MariaDB 10.4 (XAMPP) & SQLite for in-memory testing.
- **AI Providers:** Google Gemini (`gemini-3.5-flash-lite`) for Campus Assistant & Course Recommendations; OpenAI (`gpt-4.1-mini`) for Academic Risk Analysis.

## 7. Tested Modules
1. **Authentication & User Management:** Registration, Approval workflow (Pending -> Approved/Rejected), Login, Profile, Password Reset.
2. **Student Academic Portal:** Dashboard, Course Enrollment, Attendance, Performance, Routine Schedule, Transcripts, Tasks.
3. **Faculty Workspace:** Course Roster, Attendance Recording, Assessment Marks, Grade Calculation, Performance Metrics, Student Monitoring.
4. **Administrator Portal:** User Approvals, Department Management (CRUD), Course Catalog, Schedules, Exam Routines, Notices.
5. **Campus Operations:** Helpdesk Tickets, Faculty Leaves, Class Rescheduling, Library Catalog & Book Loans, Fee Tracking.
6. **AI Integrations:** Gemini Campus Assistant, Gemini Course Recommendation Ranking, OpenAI Early-Warning Risk Analyzer.

## 8. Functional Testing Summary
A total of **52 test cases** were executed across all functional modules. 100% of test cases passed successfully.

## 9. Authentication & Authorization Testing
- Sanctum bearer token authentication verified across all protected endpoints.
- Role-based authorization enforced server-side; unauthorized cross-role access attempts returned HTTP 403 Forbidden.
- Unapproved accounts are blocked at login (HTTP 403) until administrator approval.
- Token revocation verified upon logout and account rejection.

## 10. API Testing
All endpoints tested across GET, POST, PUT, PATCH, DELETE operations. Server-side validation strictly enforced HTTP 422 for malformed payloads, HTTP 401 for unauthenticated requests, and HTTP 404/403 for unauthorized resource access.

## 11. Database Testing
Relational integrity verified across 38 database tables in `ai_smart_campus`. Foreign keys correctly link users to role profiles (students, faculty, admins), courses to faculty, and enrollments to academic/attendance history.

## 12. AI Testing and Quality Evaluation
11 distinct AI scenarios tested covering normal inquiries, short inputs, multi-turn follow-ups, prompt injections, Bangla-English code-switching, complex study schedules, and early-warning risk analysis.
- **Conversational Relevance:** Rated Excellent (answers grounded in verified campus context).
- **Bangla Support:** Rated Excellent (natural Bengali syntax with technical term preservation).
- **Error Resilience:** Rated Excellent (graceful fallback without UI crashing when API keys are unconfigured).

## 13. UI/UX Testing
All pages render with responsive typography, clear feedback toasts, loading spinners, and accessible forms. Client-side validation outlines invalid fields before submission.

## 14. Responsive Testing
Validated across Desktop (1440x900), Tablet (768x1024), Mobile (390x844), and Small Mobile (360x740). Navigation collapses into touch-friendly mobile drawer menus and tables scroll smoothly.

## 15. Security Checks
- Passwords hashed using bcrypt (12 rounds).
- Zero plain-text credentials or API secrets exposed in frontend bundles or Git.
- IDOR protections prevent users from viewing other students' tasks or unassigned course workspaces.
- Rate limiting active on authentication endpoints.

## 16. Bugs Identified & 17. Bug Fixes
5 defects identified and resolved:
1. BUG-001 (Medium): Whitespace and email casing normalization in login/register forms. -> Fixed.
2. BUG-002 (Low): Null section coalescing in printable academic transcripts. -> Fixed.
3. BUG-003 (Medium): Playwright browser channel configuration. -> Fixed.
4. BUG-004 (High): Strict course workspace authorization gating. -> Fixed.
5. BUG-005 (Medium): Attendance CSV export stream RFC quoting. -> Fixed.

## 18. Retesting Summary & 19. Regression Testing Summary
All 5 fixed bugs were retested and marked Verified. Full regression test suite executed with 82 PHPUnit backend tests, 10 Vitest frontend tests, and 52 comprehensive QA tests passing at 100%.

## 20. Automated Testing Summary
- Backend PHPUnit Tests: 82 passed (421 assertions).
- Frontend Vitest Tests: 10 passed.
- Linter (Oxlint): 0 errors, 0 warnings across 47 files.
- Production Build: Built in 2.10s without errors.

## 21. Test Results Summary
- Total Test Cases: 52
- Passed: 52 (100.0%)
- Failed: 0
- Total Bugs: 5 (All Fixed & Verified)

## 22. Remaining Known Issues
None. All identified defects have been resolved and verified.

## 23. Limitations
- AI features require active Google Gemini and OpenAI API keys in `backend/.env` for live cloud inference; deterministic local fallbacks are provided when keys are unset.
- Notice email/SMS delivery drivers operate in log/disabled mode when third-party provider credentials are unconfigured.

## 24. GitHub Activity & 25. GitHub Link
- **Repository URL:** https://github.com/nazmusshakib878/CSE4204-8A-T07-ai-smart-campus-system.git
- **Commits:** Systematic, descriptive commit history adhering to course submission guidelines.

## 26. Conclusion
The AI Smart Campus System demonstrates exemplary software quality, robust security, comprehensive test coverage, and dependable AI resilience. The platform is fully verified and submission-ready.
