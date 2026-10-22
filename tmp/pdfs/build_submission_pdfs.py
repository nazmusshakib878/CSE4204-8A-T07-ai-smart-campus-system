from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (BaseDocTemplate, Frame, PageTemplate, Paragraph, Spacer, PageBreak,
                                Table, TableStyle, KeepTogether, Flowable)
from reportlab.platypus.tableofcontents import TableOfContents
from reportlab.graphics.shapes import Drawing, Rect, String, Line

ROOT = Path(r"C:\Users\Shakib\Desktop\CSE4204-8A-T07-ai-smart-campus-system-main")
OUT = ROOT / "documentation" / "final-submission"
OUT.mkdir(parents=True, exist_ok=True)

pdfmetrics.registerFont(TTFont("TimesNewRoman", r"C:\Windows\Fonts\times.ttf"))
pdfmetrics.registerFont(TTFont("TimesNewRoman-Bold", r"C:\Windows\Fonts\timesbd.ttf"))

NAVY = colors.HexColor("#17365D")
BLUE = colors.HexColor("#2F75B5")
LIGHT = colors.HexColor("#EAF1FB")
GREY = colors.HexColor("#F4F6F8")

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name="BodyAcademic", fontName="TimesNewRoman", fontSize=12, leading=18, alignment=TA_JUSTIFY, spaceAfter=8))
styles.add(ParagraphStyle(name="H1Academic", fontName="TimesNewRoman-Bold", fontSize=16, leading=21, textColor=NAVY, spaceBefore=10, spaceAfter=8, keepWithNext=True))
styles.add(ParagraphStyle(name="H2Academic", fontName="TimesNewRoman-Bold", fontSize=14, leading=18, textColor=NAVY, spaceBefore=8, spaceAfter=6, keepWithNext=True))
styles.add(ParagraphStyle(name="CoverTitle", fontName="TimesNewRoman-Bold", fontSize=24, leading=30, alignment=TA_CENTER, textColor=NAVY, spaceAfter=14))
styles.add(ParagraphStyle(name="CoverSub", fontName="TimesNewRoman", fontSize=14, leading=21, alignment=TA_CENTER, spaceAfter=10))
styles.add(ParagraphStyle(name="TableText", fontName="TimesNewRoman", fontSize=9, leading=12))
styles.add(ParagraphStyle(name="TableHead", fontName="TimesNewRoman-Bold", fontSize=9, leading=11, textColor=colors.white, alignment=TA_CENTER))
styles.add(ParagraphStyle(name="TOCStyle", fontName="TimesNewRoman", fontSize=12, leading=18))

def P(text, style="BodyAcademic"):
    return Paragraph(text.replace("\n", "<br/>"), styles[style])

def cell(text, head=False): return P(str(text), "TableHead" if head else "TableText")

def tbl(headers, rows, widths=None):
    data = [[cell(h, True) for h in headers]] + [[cell(x) for x in row] for row in rows]
    t = Table(data, colWidths=widths, repeatRows=1, hAlign="LEFT")
    t.setStyle(TableStyle([
        ("BACKGROUND", (0,0), (-1,0), NAVY), ("TEXTCOLOR", (0,0), (-1,0), colors.white),
        ("GRID", (0,0), (-1,-1), .35, colors.HexColor("#AAB7C4")),
        ("BACKGROUND", (0,1), (-1,-1), colors.white), ("VALIGN", (0,0), (-1,-1), "TOP"),
        ("LEFTPADDING", (0,0), (-1,-1), 5), ("RIGHTPADDING", (0,0), (-1,-1), 5),
        ("TOPPADDING", (0,0), (-1,-1), 5), ("BOTTOMPADDING", (0,0), (-1,-1), 5),
    ]))
    return t

class Doc(BaseDocTemplate):
    def __init__(self, filename, title):
        super().__init__(filename, pagesize=A4, rightMargin=1.8*cm, leftMargin=1.8*cm, topMargin=1.8*cm, bottomMargin=1.6*cm)
        self.title = title
        frame = Frame(self.leftMargin, self.bottomMargin, self.width, self.height, id="normal")
        self.addPageTemplates([PageTemplate(id="Normal", frames=[frame], onPage=self.header_footer)])
    def header_footer(self, canvas, doc):
        if doc.page == 1: return
        canvas.saveState(); canvas.setStrokeColor(colors.HexColor("#B7C9DD")); canvas.line(doc.leftMargin, A4[1]-1.25*cm, A4[0]-doc.rightMargin, A4[1]-1.25*cm)
        canvas.setFont("TimesNewRoman", 9); canvas.setFillColor(NAVY)
        canvas.drawString(doc.leftMargin, A4[1]-1.0*cm, "AI Smart Campus System")
        canvas.drawRightString(A4[0]-doc.rightMargin, A4[1]-1.0*cm, self.title)
        canvas.line(doc.leftMargin, 1.1*cm, A4[0]-doc.rightMargin, 1.1*cm)
        canvas.drawCentredString(A4[0]/2, .72*cm, f"Page {doc.page}")
        canvas.restoreState()
    def afterFlowable(self, flowable):
        if isinstance(flowable, Paragraph) and flowable.style.name == "H1Academic":
            self.notify("TOCEntry", (0, flowable.getPlainText(), self.page))

def cover(title, subtitle):
    return [Spacer(1, 4.2*cm), P("NORTHERN UNIVERSITY OF BUSINESS AND TECHNOLOGY, KHULNA", "CoverSub"),
            Spacer(1, .5*cm), P(title, "CoverTitle"), P("AI Smart Campus System", "CoverSub"),
            Spacer(1, 1.0*cm), P(subtitle, "CoverSub"), Spacer(1, 1.2*cm),
            P("Course: CSE 4204 - Mobile Computing Lab", "CoverSub"),
            P("Week 12 Final Submission", "CoverSub"), Spacer(1, .6*cm),
            P("Prepared by: [Team member names and student IDs to be completed]", "CoverSub"),
            P("Supervisor: [Supervisor name to be completed]", "CoverSub"),
            P("Submission date: 22 October 2026", "CoverSub"), PageBreak()]

def toc():
    t = TableOfContents(); t.levelStyles = [ParagraphStyle(name="toc1", fontName="TimesNewRoman", fontSize=12, leading=18, leftIndent=0, firstLineIndent=0, textColor=colors.black)]
    return [P("Table of Contents", "H1Academic"), t, PageBreak()]

def h(story, title): story.append(P(title, "H1Academic"))
def h2(story, title): story.append(P(title, "H2Academic"))
def para(story, text): story.append(P(text))

def architecture():
    d = Drawing(475, 170)
    boxes = [(10,105,110,40,"React / Vite\nFrontend"),(180,105,110,40,"Laravel REST API\nSanctum + RBAC"),(350,105,110,40,"MySQL\nAcademic Data"),(180,20,110,40,"Gemini / OpenAI\nServer-side APIs")]
    for x,y,w,hh,label in boxes:
        d.add(Rect(x,y,w,hh,strokeColor=NAVY,fillColor=LIGHT,rx=5,ry=5));
        a,b=label.split("\n"); d.add(String(x+w/2,y+24,a,fontName="TimesNewRoman-Bold",fontSize=10,textAnchor="middle",fillColor=NAVY)); d.add(String(x+w/2,y+11,b,fontName="TimesNewRoman",fontSize=9,textAnchor="middle"))
    for x1,y1,x2,y2 in [(120,125,180,125),(290,125,350,125),(235,105,235,60)]: d.add(Line(x1,y1,x2,y2,strokeColor=BLUE,strokeWidth=1.5))
    return d

def final_report():
    s=cover("Final Project Report", "Professional Academic Report") + toc()
    h(s,"1. Introduction"); para(s,"AI Smart Campus System is a web-based academic management and student-success platform developed for Northern University of Business and Technology, Khulna (NUBTK). The project combines a React single-page frontend, a Laravel REST API, MySQL persistence, role-based access control, academic operations, notices, exports, and server-side AI-assisted workflows. The implementation is evidenced by the repository source, migrations, tests, and deployment guidance reviewed on 22 October 2026.")
    h(s,"2. Problem Statement"); para(s,"Academic administration commonly requires separate processes for account approval, course data, attendance, assessments, notices, routines, service requests, and early risk identification. The project addresses this fragmentation by providing a single authenticated workspace with student, faculty, and administrator scopes. It also supports advisory AI workflows without sending API credentials or authoritative academic records from the browser.")
    h(s,"3. Objectives"); s.append(tbl(["Objective","Implemented evidence"],[ ["Centralize academic workflows","Dashboards, academic management, notices, campus operations, exports and profile functions."],["Enforce role-aware access","Laravel Sanctum, approval middleware, policies and controller checks."],["Improve student support","Tasks, resources, course recommendations, academic monitoring and risk alerts."],["Provide advisory AI","Gemini assistant/recommendations and OpenAI risk-analysis integration with controlled server-side access."]],[5.0*cm,11.2*cm]));
    h(s,"4. Methodology"); para(s,"The system follows a layered web application approach. React components collect user input and call Axios API services. Laravel validates requests, authenticates tokens, applies role and approval checks, performs database operations, and invokes external AI providers only from the server. Database changes are versioned through Laravel migrations. Automated PHPUnit, Vitest, and Playwright tests are included in the repository.")
    h(s,"5. System Architecture"); s.append(architecture()); para(s,"Figure 1. Layered architecture. The frontend does not contain Gemini or OpenAI credentials. Provider outputs are parsed and validated before persistence or display; fallback behavior exists for selected workflows.")
    h(s,"6. Database Design"); para(s,"The authoritative schema is the Laravel migration set. Core entities include users, students, faculty, courses, course enrollments, attendance records, academic records, performance metrics, notices and notice reads, campus tasks, learning resources, recommendations, risk alerts, AI conversations and messages, departments, and campus-operation tables. User records are related one-to-one with student or faculty profiles; faculty teach courses; students connect to courses through enrollments and academic records.")
    s.append(tbl(["Domain","Representative tables"],[["Identity and access","users, personal_access_tokens, students, faculty, departments"],["Academic records","courses, course_enrollments, attendance_records, assessment_records, academic_records, performance_metrics, course_schedules"],["Communication and support","notices, notice_reads, campus_tasks, learning_resources, recommendations"],["AI and monitoring","risk_alerts, ai_conversations, ai_messages"],["Campus operations","exams, events, fees, tickets, leaves, reschedules, books and loans"]],[5.1*cm,11.1*cm]));
    h(s,"7. Technologies Used"); s.append(tbl(["Layer","Technologies evidenced in project files"],[["Frontend","React 19, React Router, Vite, Axios, Bootstrap, Material UI, React Markdown"],["Backend","PHP 8.2+, Laravel 12, Laravel Sanctum"],["Database","MySQL (default environment configuration)"],["AI","Google Gemini Generate Content API; OpenAI Responses API"],["Testing","PHPUnit, Vitest, Testing Library, Playwright"],["Deployment","Composer, npm, Laravel queues/scheduler, HTTPS/CORS guidance"]],[4.1*cm,12.1*cm]));
    h(s,"8. Features"); para(s,"Students can register, await approval, log in, manage profiles and photos, view dashboards, access courses/attendance/grades/schedules, download transcript and attendance exports, manage tasks, view resources and notices, use campus services, obtain course recommendations, and use the conversational assistant. Faculty have assigned-course workspaces, student enrollment/attendance/assessment/grade/performance entry, scoped monitoring, notices, services and assistant access. Administrators manage accounts, approvals, departments, courses, notices, routines, operations and institution-wide monitoring.")
    h(s,"9. AI Integration"); para(s,"The conversational assistant uses Gemini with a server-side system prompt, recent conversation history, authenticated context selection, timeout and retry handling. Course recommendation logic determines eligible candidates locally and may use Gemini to reorder validated candidate IDs; deterministic ranking remains available if the provider is unavailable. Risk analysis calls the OpenAI Responses API with a strict JSON schema. In the current local implementation, when a configured provider fails (for example, rate limiting or exhausted credit), verified baseline indicators are saved as source 'baseline'; missing OpenAI configuration remains a clear configuration error.")
    h(s,"10. Testing"); para(s,"The repository's Final Testing Report dated 18 October 2026 records 93 automated tests passed and 0 failed: 10 frontend tests and 83 Laravel tests with 423 assertions. The evidence states that AI provider calls were mocked in that run, so these results validate integration behavior and error paths but do not establish live provider availability. A later focused StudentMonitoringTest run after the baseline fallback change passed 5 tests and 30 assertions.")
    h(s,"11. Results"); para(s,"The codebase contains a complete role-aware web workflow, protected REST endpoints, migration-managed schema, test suites, and deployment instructions. The documented automated evidence supports regression readiness. The live AI risk provider was observed locally to return HTTP 429 because credits were unavailable; the code now preserves a data-derived baseline analysis path rather than failing the monitoring workflow.")
    h(s,"12. Limitations"); para(s,"Team member names, student IDs and supervisor details were not available in the repository and are intentionally left as cover-page placeholders. No current submission screenshots were present in screenshots/. Live AI behavior depends on valid provider credentials, quota and network availability. Automated provider tests use fakes and therefore do not substitute for an authenticated live demonstration. The application is a web system; no native mobile package is evidenced in the reviewed code.")
    h(s,"13. Future Work"); para(s,"Recommended next steps are to add controlled live-provider monitoring, expand offline/mobile delivery if required by the course, introduce CI execution and deployment gates, capture sanitized responsive screenshots, add accessibility audits, and conduct usability evaluation with representative student and faculty users.")
    h(s,"14. Conclusion"); para(s,"AI Smart Campus System demonstrates an integrated academic management solution with meaningful role separation, database-backed academic workflows, documented APIs and server-side AI integration. Its design emphasizes authorization, validation, provider isolation and graceful fallback while identifying clearly what remains dependent on external services and final institutional submission details.")
    h(s,"15. References"); para(s,"[1] Project source repository: README.md, backend/routes/api.php, backend/database/migrations, controllers, services and tests, reviewed 22 October 2026.\n[2] Laravel Documentation, Laravel 12 framework and Sanctum authentication.\n[3] React Documentation, React 19 and React Router.\n[4] Google AI for Developers, Gemini Generate Content API documentation.\n[5] OpenAI API Documentation, Responses API.\n[6] Project documentation: documentation/API.md, ER_DIAGRAM.md, DEPLOYMENT.md and testing-evidence/Final_Testing_Report.md.")
    return s

def manual():
    s=cover("User Manual", "Installation and Operational Guide")+toc()
    h(s,"1. System Overview"); para(s,"AI Smart Campus System is a browser-based campus platform. It provides distinct student, faculty and administrator workspaces after authentication and approval. The exact menu depends on the signed-in role.")
    h(s,"2. Installation and Setup"); s.append(tbl(["Step","Action"],[["1","Install PHP 8.2+, Composer, MySQL, Node.js and npm."],["2","In backend, copy .env.example to .env; configure database values and protected AI keys; run composer install."],["3","Run php artisan key:generate, php artisan migrate, php artisan storage:link and php artisan db:seed as appropriate for the target database."],["4","Run php artisan serve to start the Laravel API."],["5","In frontend, run npm install and npm run dev. Use npm.cmd run dev in PowerShell if npm.ps1 is blocked."],["6","Open the Vite address shown by the development server. The reviewed local capture used localhost:5173."]],[2.0*cm,14.2*cm]));
    h(s,"3. Login and Account Approval"); para(s,"Open the Login page, enter a registered email and password, then select Sign In. Public registration accepts Student or Faculty roles; administrator self-registration is rejected. A newly registered account may require administrator approval before authenticated access. Use Forgot Password to request reset instructions, then use the reset link and a valid new password.")
    h(s,"4. User Roles"); s.append(tbl(["Role","Main capabilities"],[["Student","Dashboard, profile, academic data and exports, tasks, resources, messages/notices, campus services, course recommendations and AI Assistant."],["Faculty","Faculty dashboard, assigned academic workspaces, attendance/assessments/grades/performance, scoped monitoring, notices, services and AI Assistant."],["Administrator","Approval and user management, departments, course and campus-operation management, notices, dashboards and institution-wide student monitoring."]],[3.2*cm,13.0*cm]));
    h(s,"5. Student Workflow"); para(s,"1. Sign in with an approved student account. 2. Open Dashboard to view personal academic information. 3. Open Campus Services for routines, exams, events, fee information, library-related actions, support tickets, leave or rescheduling requests as permitted. 4. Use Course Recommendations to review advisor-created or eligible rule/AI-ranked recommendations and create a study-plan task. 5. Open Messages to read applicable notices; select View, Download an authorized attachment, or mark a notice as read. 6. Open My Profile to update permitted profile information, password or photo. 7. Use transcript or attendance export links to download personal records. 8. Open AI Assistant, enter a question and submit; campus-specific answers use the account's authorized context.")
    h(s,"6. Faculty Workflow"); para(s,"1. Sign in with an approved faculty account. 2. Use Faculty Dashboard and Academic Management to open an assigned course workspace. 3. Enroll or remove students where authorized. 4. Save attendance, assessment, grade and performance records. 5. Open Student Monitoring to inspect only students accessible through assigned courses. 6. Select analysis for a student to create a risk alert. When an external provider is unavailable, the result may be labelled BASELINE and is calculated from verified indicators. 7. Create or manage notices and permitted campus-service records. 8. Use the AI Assistant for general questions or authenticated faculty context.")
    h(s,"7. Administrator Workflow"); para(s,"1. Sign in as an administrator. 2. Use Manage Users to review pending users, approve or change approval status, and inspect all users. 3. Use department management to create, enable/disable, or remove departments. 4. Use Academic Management to manage courses and institution-wide academic records. 5. Use notice management to publish, target, archive or remove notices. 6. Use Campus Services to manage authorized schedules, examinations, events, fee notices, library catalog and requests. 7. Use Student Monitoring and Risk Alerts to review university-wide academic risk data.")
    h(s,"8. Dashboard and Feature Guide"); s.append(tbl(["Feature","How to use"],[["Tasks","Open the task area; create, view, update or delete personal tasks according to your authorization."],["Learning resources","Browse eligible resources; faculty/admin may upload or manage resources under policy rules."],["Notices","Open Messages; unread notices are counted in navigation; read, download authorized attachments, archive or manage according to role."],["Academic management","Select a course then use its workspace controls to maintain enrollments, attendance, assessments, grades and performance."],["Campus operations","Open Campus Services; view data or submit/manage exams, schedules, events, fees, tickets, leave/reschedule, book/loan actions according to role."],["AI Assistant","Student/faculty only: type a question; wait for a response; start a new conversation if required."],["Risk analysis","Faculty/admin: choose a permitted student from Student Monitoring; run analysis and review source, score, reasons, prediction and advice."],["Exports","Download transcript or attendance export from the student-facing academic area or authorized student route."]],[4.1*cm,12.1*cm]));
    h(s,"9. Screenshots"); para(s,"No current, submission-ready screenshot files were available in screenshots/ at documentation time; the folder contained only an index requesting future captures. Therefore this manual does not include invented or stale screenshots. Before submission, capture approved, sanitized screens at desktop and mobile viewports, redact private records and never show passwords, tokens or API keys.")
    h(s,"10. Troubleshooting"); s.append(tbl(["Symptom","Action"],[["Cannot log in","Confirm email/password, account approval, backend availability and auth rate-limit cooldown."],["Messages show database error","Run pending Laravel migrations against the configured database, then refresh."],["AI unavailable","Check provider key, model, network and provider quota. Gemini/OpenAI keys must remain only in backend/.env. Risk analysis can show BASELINE when a configured provider is unavailable."],["No access to a page","Confirm the signed-in role and faculty course assignment; access is intentionally role scoped."],["Image/upload fails","Confirm storage link, backend/storage permissions and file validation requirements."],["Frontend cannot reach API","Check Vite API configuration, Laravel server URL and CORS_ALLOWED_ORIGINS."],["No email reset received","Verify mail configuration and logs; the application returns a privacy-safe response for unknown addresses."]],[4.5*cm,11.7*cm]));
    h(s,"11. Deployment Notes"); para(s,"For production, point the web root to backend/public, serve frontend/dist with SPA fallback, enable HTTPS, set trusted CORS origins, use a restricted database account, set APP_DEBUG=false, run migrations with --force, configure queue workers if notice delivery is enabled, and protect .env, logs, backups and source maps from public access.")
    return s

endpoints = [
("POST","/register","Public","Register student/faculty"),("POST","/login","Public","Authenticate and issue token"),("POST","/forgot-password","Public","Request password reset"),("POST","/reset-password","Public","Reset password"),("GET","/departments","Public","List active departments"),
("GET","/profile","Auth","Current profile"),("PUT","/profile","Auth","Update profile"),("PUT","/profile/password","Auth","Change password"),("POST","/profile/photo","Auth","Upload profile photo"),("DELETE","/profile/photo","Auth","Delete profile photo"),("POST","/logout","Auth","Revoke current token"),
("GET","/admin/dashboard","Admin","Admin dashboard"),("GET","/faculty/dashboard","Faculty","Faculty dashboard"),("POST","/admin/create-admin","Admin","Create administrator"),("GET","/admin/pending-users","Admin","Pending users"),("GET","/admin/all-users","Admin","All users"),("PATCH","/admin/users/{user}/approval","Admin","Change approval"),("GET|POST","/admin/departments","Admin","List/create departments"),("PATCH","/admin/departments/{department}/status","Admin","Update department status"),("DELETE","/admin/departments/{department}","Admin","Delete department"),
("GET","/student/dashboard","Student","Student dashboard"),("GET","/faculty/student-monitoring","Faculty/Admin","Accessible students and charts"),("POST","/faculty/students/{student}/analyze-risk","Faculty/Admin","Risk analysis"),
("GET","/ai/assistant/conversation","Student/Faculty","Latest scoped conversation"),("POST","/ai/assistant/conversation","Student/Faculty","Start conversation"),("POST","/ai/assistant","Student/Faculty","Ask Gemini assistant"),
("GET","/student/transcript","Student","Own transcript export"),("GET","/student/attendance-export","Student","Own attendance export"),("GET","/students/{student}/transcript","Authorized","Student transcript export"),("GET","/students/{student}/attendance-export","Authorized","Student attendance export"),
("GET","/campus-services","Auth","Campus operations data"),("GET","/campus-services/official-routine-pdf","Auth","Official routine PDF"),
("POST|PUT|DELETE","/campus-services/exams[/{exam}]","Role scoped","Create/update/delete exam"),("POST|PUT|DELETE","/campus-services/schedules[/{schedule}]","Role scoped","Create/update/delete schedule"),("POST|PUT|DELETE","/campus-services/events[/{event}]","Role scoped","Create/update/delete event"),("POST","/campus-services/fees","Role scoped","Create fee"),("POST","/campus-services/tickets","Auth","Create support ticket"),("PATCH","/campus-services/tickets/{ticket}","Role scoped","Update ticket"),("POST","/campus-services/leaves","Auth","Create leave request"),("PATCH","/campus-services/leaves/{leave}","Role scoped","Review leave"),("POST","/campus-services/reschedules","Auth","Create reschedule request"),("PATCH","/campus-services/reschedules/{reschedule}","Role scoped","Review reschedule"),("POST","/campus-services/books","Role scoped","Create book"),("POST","/campus-services/books/{book}/borrow","Auth","Borrow book"),("PATCH","/campus-services/loans/{loan}/return","Role scoped","Return loan"),
("GET|POST","/academic-management","Faculty/Admin","List accessible courses / create course"),("POST|PUT|DELETE","/academic-management/courses[/{course}]","Faculty/Admin","Course CRUD"),("GET","/academic-management/courses/{course}/workspace","Faculty/Admin","Course workspace"),("POST","/academic-management/courses/{course}/enrollments","Faculty/Admin","Enroll student"),("DELETE","/academic-management/courses/{course}/enrollments/{enrollment}","Faculty/Admin","Unenroll student"),("PUT","/academic-management/courses/{course}/attendance","Faculty/Admin","Save attendance"),("PUT","/academic-management/courses/{course}/assessments","Faculty/Admin","Save assessments"),("PUT","/academic-management/courses/{course}/grades","Faculty/Admin","Save grades"),("PUT","/academic-management/courses/{course}/students/{student}/performance","Faculty/Admin","Save performance"),
("GET|POST|GET/{id}|PUT/{id}|DELETE/{id}","/learning-resources","Auth/policy","Resource API resource"),("GET|POST|GET/{id}|PUT/{id}|DELETE/{id}","/tasks","Auth/policy","Task API resource"),("GET|POST|GET/{id}|PUT/{id}|DELETE/{id}","/recommendations","Auth/policy","Recommendation API resource"),("GET|POST|GET/{id}|PUT/{id}|DELETE/{id}","/notices","Auth/policy","Notice API resource"),("POST","/notices/{notice}/read","Auth","Mark notice read"),("PATCH","/notices/{notice}/archive","Auth","Archive notice"),("GET","/notices/{notice}/attachment","Authorized","Download attachment")]

def api_doc():
    s=cover("API Documentation", "REST API Reference")+toc()
    h(s,"1. Overview"); para(s,"This reference reflects backend/routes/api.php reviewed on 22 October 2026. Base path is /api. All protected routes are within Laravel auth:sanctum, approved and throttle:api middleware. Public authentication routes use throttle:auth. Resource routes are generated by Laravel apiResource and use the {id} parameter mapping declared in routes.")
    h(s,"2. Authentication and Headers"); para(s,"Login returns a Sanctum bearer token. Send protected requests with Authorization: Bearer &lt;token&gt; and Accept: application/json. An authenticated account must also be approved. Authentication routes are rate limited; the deployment guide documents defaults of five auth requests/minute per email/IP and 60 authenticated requests/minute per user/IP.")
    h(s,"3. Response and Error Conventions"); s.append(tbl(["Status","Meaning"],[["200/201","Successful request / resource created"],["401","No valid Sanctum token"],["403","Authenticated but forbidden by role, policy or faculty scope"],["404","Resource or scoped conversation not found"],["409","Duplicate/conflict where applicable"],["422","Validation failure; errors object contains field messages"],["429","Rate limit or AI provider quota response"],["500/502/503","Unexpected server/provider/configuration failure"]],[3.0*cm,13.2*cm])); para(s,"Typical validation response: {\"status\": false, \"message\": \"The given data was invalid.\", \"errors\": {\"field\": [\"Validation message\"]}}.")
    h(s,"4. Endpoint Catalogue"); s.append(tbl(["Method","Endpoint","Access","Purpose"], endpoints, [2.25*cm,6.2*cm,3.0*cm,5.15*cm]));
    h(s,"5. Representative Request and Response Examples"); h2(s,"Registration"); para(s,"POST /api/register\n{\"name\":\"Student Name\",\"email\":\"student@example.edu\",\"password\":\"ExamplePass1!\",\"password_confirmation\":\"ExamplePass1!\",\"role\":\"student\",\"student_id\":\"CSE000000000\"}\nSuccessful registration is subject to server validation and approval workflow. Administrator role is not accepted by the public registration endpoint.")
    h2(s,"Login"); para(s,"POST /api/login\n{\"email\":\"student@example.edu\",\"password\":\"ExamplePass1!\"}\nA successful response includes status, a bearer token and a user object. Do not store a token in documentation, source code or screenshots.")
    h2(s,"AI Assistant"); para(s,"POST /api/ai/assistant\nAuthorization: Bearer &lt;token&gt;\n{\"question\":\"Create a study plan for this week\",\"conversation_id\": 12}\nSuccess shape: {\"status\":true,\"data\":{\"answer\":\"...\",\"model\":\"configured Gemini model\",\"conversation_id\":12}}. The question is required, string and limited to 4000 characters. Access is limited to student and faculty accounts with required profiles.")
    h2(s,"Risk Analysis"); para(s,"POST /api/faculty/students/{student}/analyze-risk\nAuthorization: Bearer &lt;token&gt;\nNo request body is required. The server derives attendance, missed classes, CGPA, course information and baseline indicators from the database. Success returns risk_score, risk_level, prediction, reasons, advice, source, model and analyzed_at. Faculty may analyze only students in assigned-course scope; administrators have institution-wide access. Source is openai for a valid provider analysis or baseline when the configured provider is unavailable.")
    h2(s,"Academic Attendance Update"); para(s,"PUT /api/academic-management/courses/{course}/attendance\nAuthorization: Bearer &lt;token&gt;\nThe request is validated by AcademicManagementController and is restricted to accessible course management users. Use the frontend course workspace as the authoritative form for the exact request payload; do not submit student records outside the authorized course scope.")
    h2(s,"Notice Creation"); para(s,"POST /api/notices\nAuthorization: Bearer &lt;token&gt;\nNotice data is validated server-side and may include title, description, category, audience, targeting fields, publication information and an authorized attachment. Audience rules and attachment access are checked server-side; notices may be marked read or archived with their dedicated endpoints.")
    h(s,"6. Validation, Authorization and Security"); para(s,"Controllers use Laravel validation rules; user input is not trusted as an academic source. Sanctum protects API access, approved middleware blocks unapproved accounts, policies protect tasks/resources/recommendations, and controller checks protect administrative and faculty-scoped operations. The frontend does not receive AI keys. Password reset responses are privacy-safe, and deployment guidance requires HTTPS, restricted CORS origins, APP_DEBUG=false, protected environment files, logs and backups. API request logging is documented to avoid passwords and request bodies.")
    h(s,"7. AI API Behavior"); para(s,"Gemini assistant calls are made server-side with timeout, connection handling and retry logic. Gemini course ranking is constrained to backend-supplied candidates and has a deterministic fallback. OpenAI risk-analysis output is constrained through a JSON schema and validated before storage. If the provider returns a temporary failure, the current Student Monitoring controller records an explicitly labelled deterministic baseline analysis using verified database indicators; missing OpenAI configuration returns a clear service configuration error.")
    h(s,"8. Endpoint Notes"); para(s,"The catalogue is route-complete for backend/routes/api.php at review time. Request-field detail varies by controller action and file upload. For executable request collections, use the repository's documentation/postman materials after setting a local bearer token. Never include real tokens, credentials, API keys or personal academic data in submitted examples.")
    return s

def make(filename, title, story):
    doc=Doc(str(OUT/filename), title); doc.multiBuild(story)

make("Final_Report.pdf", "Final Project Report", final_report())
make("User_Manual.pdf", "User Manual", manual())
make("API_Documentation.pdf", "API Documentation", api_doc())
with ZipFile(OUT/"CSE4204_Documentation.zip", "w", ZIP_DEFLATED) as z:
    for name in ["Final_Report.pdf","User_Manual.pdf","API_Documentation.pdf"]: z.write(OUT/name, arcname=name)
print("Created", OUT)
