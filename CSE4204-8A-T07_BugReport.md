# Bug Report: AI Smart Campus System
**Course:** CSE4204 | **Section:** 8A | **Team:** T07  
**Institution:** Northern University of Business and Technology, Khulna (NUBTK)  
**Execution Date:** September 2026  

---

## Bug Summary Dashboard
- **Total Bugs Found:** 5
- **Critical:** 0
- **High:** 1
- **Medium:** 3
- **Low:** 1
- **Fixed:** 5
- **Verified:** 5
- **Remaining Known Issues:** 0

---

## Detailed Bug Records

### [BUG-001] Whitespace trimming and email case-normalization missing on form submission in LoginPage and RegisterPage.
- **Module:** Authentication & Validation
- **Severity:** **MEDIUM** | **Status:** **Verified**
- **Steps to Reproduce:**
```
1. Navigate to /login.
2. Enter email with trailing space or mixed case (e.g., " Student1@NUBTKHULNA.ac.bd ").
3. Click Sign in securely.
```
- **Expected Behavior:** Form input should automatically trim leading/trailing whitespace and normalize to lowercase before API submission.
- **Actual Behavior:** Whitespace resulted in raw string submission and validation error from strict server regex before normalization fix.
- **Resolution:** Updated validateLoginForm and handleSubmit in LoginPage.jsx and RegisterPage.jsx to normalize email strings before client validation and API dispatch.
- **Retest Result:** Verified: Whitespace and mixed case emails are now normalized seamlessly and login completes successfully (HTTP 200).

---
### [BUG-002] Printable HTML transcript rendered "N/A" with raw template artifact when student model section attribute was null.
- **Module:** Academic Management / Export
- **Severity:** **LOW** | **Status:** **Verified**
- **Steps to Reproduce:**
```
1. Authenticate as student with unassigned section.
2. Access GET /api/student/transcript.
3. Observe HTML output header.
```
- **Expected Behavior:** Safe fallback to section placeholder without throwing template rendering warnings.
- **Actual Behavior:** Section rendered without null coalescing fallback.
- **Resolution:** Added null-safe operator and fallback $student->section ?? "N/A" in AcademicExportController.php.
- **Retest Result:** Verified: HTML transcript generates cleanly with proper student header information.

---
### [BUG-003] Playwright E2E configuration defaulted to downloading bundled Chromium which failed in restricted offline/Windows environments without system channel declaration.
- **Module:** Automated Testing Infrastructure
- **Severity:** **MEDIUM** | **Status:** **Verified**
- **Steps to Reproduce:**
```
1. Run npx playwright test in vanilla Windows terminal without downloaded playwright browser binaries.
2. Browser launch fails with Executable doesn't exist.
```
- **Expected Behavior:** Test configuration should leverage installed Google Chrome (channel: chrome) directly.
- **Actual Behavior:** Failed looking for non-existent AppData headless shell path.
- **Resolution:** Configured channel: "chrome" and baseURL in playwright.config.js.
- **Retest Result:** Verified: All browser and E2E tests execute cleanly using system Google Chrome (100% pass).

---
### [BUG-004] Faculty accessing academic workspace required explicit course-to-faculty ownership check to prevent unauthorized grading of other professors' courses.
- **Module:** Authorization & RBAC
- **Severity:** **HIGH** | **Status:** **Verified**
- **Steps to Reproduce:**
```
1. Log in as Faculty A.
2. Send GET /api/academic-management/courses/{courseId}/workspace for Course B (assigned to Faculty B).
```
- **Expected Behavior:** API must return HTTP 403 Forbidden preventing access to unassigned course grading rosters.
- **Actual Behavior:** Potential parameter tampering risk if course ID was manipulated.
- **Resolution:** Implemented strict ensureCourseAccess() policy method in AcademicManagementController.php verifying faculty user ID matches course assignment.
- **Retest Result:** Verified: Direct URL access to unassigned course workspace returns HTTP 403 Forbidden.

---
### [BUG-005] Student attendance CSV stream export lacked explicit quote encapsulation for course titles containing commas.
- **Module:** Academic Export / CSV Stream
- **Severity:** **MEDIUM** | **Status:** **Verified**
- **Steps to Reproduce:**
```
1. Enroll student in course with comma in title (e.g. "Software Development II: Web Development, Frameworks").
2. Download CSV attendance report.
```
- **Expected Behavior:** CSV fields must be encapsulated with fputcsv standard quoting.
- **Actual Behavior:** Raw unquoted strings caused column misalignment in Excel/spreadsheet viewers.
- **Resolution:** Standardized fputcsv stream writer in AcademicExportController.php with proper RFC 4180 field enclosure.
- **Retest Result:** Verified: CSV downloads import seamlessly with proper column structure.

---
