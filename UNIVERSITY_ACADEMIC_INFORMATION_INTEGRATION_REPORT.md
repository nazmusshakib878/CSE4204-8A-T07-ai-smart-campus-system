# University Academic Information Integration Report

## Scope and safeguards

This local implementation uses the official information supplied in the integration request. No production/Aiven database was queried or changed. No migrations, seeders, enrollments, attendance, results, CGPA, notice records, emails, SMS messages, or deployment actions were performed.

The original Spring final-exam PDF and dues spreadsheet are not in the workspace. The supplied text is treated as the authoritative summary only where it explicitly provides data. No attachment has been fabricated.

## Implemented improvements

### Fall 2026 CSE Registration Reference

Campus Services now has a **Fall 2026 Reference** tab. It is a responsive, printable, read-only reference for semesters 2-8 and shows official course code, title, credits, and per-semester total. It deliberately has no enrollment action and no semester 1 entry.

The data is kept in `frontend/src/data/fall2026Rrf.js`, separate from the Spring 2026 routine/enrollment data.

### Notice visibility authorization

`target_semester` is now validated as an integer from 1 through 8. A nullable `target_section` field is included in a new local migration. For a Department notice directed at Students, the backend only returns it when the authenticated student's `current_semester` and, when supplied, `section` match. Empty targeting fields keep the established department-wide behavior. This filter is applied both to the notice list and individual notice/attachment access checks, so a direct API request cannot bypass it.

Archived notices retain the existing product behavior: they are hidden from students and remain visible to administrators for management.

## Unpublished, approval-required notice templates

These templates were prepared but intentionally not stored or published.

### Spring 2026 CSE Final Examination Schedule

- Category: `Exam`
- Audience: CSE Department Students; target each semester only after an administrator confirms the intended scope.
- Status: archived
- Attachment: none - the original PDF was not supplied.
- Body: the supplied archived-notice wording, with the preserved shifts: 10:30 AM-12:30 PM first shift, 10:00 AM-12:00 PM Friday first shift, and 02:00 PM-04:00 PM second shift.

The approved summary must not be turned into per-section exam records except for the explicit 8A examples: CSE 4203 on 27 Sep (second, RM), CSE 4201 on 03 Oct (second, FAI), CSE 4205 on 10 Oct (first, FA), and CSE 4207 on 16 Oct (Friday first timing, AMR). `CSE 2201` (special 4*) and `CSE 3205` (special 6*) require manual review and must not be assigned to 4A/6A.

### Spring 2026 Accounts and Dues Information

- Category: `Payment`
- Audience: relevant CSE students only, pending Admin approval
- Status: archived
- Attachment: none; the spreadsheet was not added, copied, committed, or exposed.
- Body: students should contact the authorized Accounts Office; individual balances and clearance must be verified through official records; the notice establishes neither a deadline nor an individual's balance.

No financial records, individual messages, email, SMS, payment gateway, deadline, or payment status were created.

## Course and section data-quality audit

The current local database does not align with the supplied official mapping. Key findings:

| Current catalog code | Current title | Verified meaning | Finding |
|---|---|---|---|
| `CSE4102` | Mobile Computing | CSE 4102 - Compiler Design Lab | Incorrect code/title mapping and missing hyphen normalization |
| `CSE-4203` | Information Security and Control | CSE 4203 - Mobile Computing | Incorrect mapping |
| `CSE-4201` | CSE 4201 | CSE 4201 - Embedded System | Title requires verified correction |
| `CSE-4205` | Machine Learning | CSE 4205 - Neural Network | Incorrect mapping |
| `CSE-4207` | Neural Network | CSE 4207 - Pattern Recognition | Incorrect mapping |
| `CSE-4101` | CSE 4101 | CSE 4101 - Compiler Design | Title requires verified correction |

The Spring section audit also found extra course enrollments outside the verified routine mapping: 1A (`CSE-1101` pending source review), 3A (`CSE-2103` pending source review), 4A (`CSE-2201`, `CSE-2206`, `CSE-2207`), 5A (`CSE-3101`, `CSE-3106`), 6A (`CSE-3201`, `CSE-3203`, `CSE-3206`), and 7A (`CSE-4103`, `CSE-4105`, `CSE-4106`). The 8A demo cohort matches its verified Spring routine; the separate Nazmus 8A record remains cross-semester enrolled and requires explicit academic approval before any correction.

These are findings only. Existing enrollments, attendance, results, CGPA, and demo records were not changed. A future approved correction should use a reviewed mapping plan and avoid deleting academic history.

## Validation

- Backend notice/dashboard authorization tests: **10 passed, 83 assertions**.
- New test coverage confirms each semester 1-8 sees only its own restricted Department notice, and a same-semester student in another section cannot retrieve a section-restricted notice directly.
- Frontend production build: **passed** with Vite.

## Approval required before the next step

1. Approve any catalog title/code normalization and a separately reviewed enrollment correction plan.
2. Provide the original exam PDF if it should be stored as a downloadable attachment.
3. Approve specific audience/semester targets and publication of the archived exam and dues notices.
4. Approve any production migration, seed, database change, notification delivery, or deployment. None has been performed.
