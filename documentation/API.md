# API reference

Base URL: `/api`. Protected endpoints require `Authorization: Bearer <token>` and `Accept: application/json`.

## Public and authentication

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | /register | Student/faculty registration |
| POST | /login | Login and token creation |
| GET | /departments | Active departments |
| GET | /profile | Current user |
| POST | /logout | Revoke current token |

## Profile

| Method | Endpoint |
| --- | --- |
| PUT | /profile |
| PUT | /profile/password |
| POST | /profile/photo |
| DELETE | /profile/photo |

## Dashboards and monitoring

| Method | Endpoint | Roles |
| --- | --- | --- |
| GET | /student/dashboard | Student |
| GET | /faculty/dashboard | Faculty |
| GET | /admin/dashboard | Admin |
| GET | /faculty/student-monitoring | Faculty/Admin |
| POST | /faculty/students/{student}/analyze-risk | Faculty/Admin |

## Academic management

`GET /academic-management` lists accessible courses. Course CRUD, enrolment, attendance, grades and performance endpoints are under `/academic-management/courses`. Admin manages courses; assigned faculty/admin manage course records.

## Tasks, resources and recommendations

- `/tasks`: authenticated resource; non-admin users are owner-scoped.
- `/learning-resources`: students read eligible resources; faculty/admin create; uploader/admin modify.
- `/recommendations`: students receive advisor-created or rule-based recommendations; staff creation is authorized.

## Notices

| Method | Endpoint |
| --- | --- |
| GET/POST | /notices |
| GET/PUT/PATCH/DELETE | /notices/{id} |
| POST | /notices/{notice}/read |
| PATCH | /notices/{notice}/archive |
| GET | /notices/{notice}/attachment |

Notice listing is paginated. Attachments require an authenticated request and audience authorization.

## Admin

Admin-only endpoints cover pending users, approval, admin creation, department management, notices, dashboards and course setup.

## Error format

```json
{"status": false, "message": "Human-readable error", "errors": {"field": ["Validation message"]}}
```

Common statuses: 401 unauthenticated, 403 forbidden, 404 missing, 409 duplicate, 422 validation, 429 rate limited, 500 server error.

Import the reviewed Postman collection for executable examples.
