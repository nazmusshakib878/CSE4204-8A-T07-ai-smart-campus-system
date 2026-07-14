# Entity relationship diagram

This Mermaid diagram reflects the current executable migrations.

```mermaid
erDiagram
 USERS ||--o| STUDENTS : has
 USERS ||--o| FACULTY : has
 FACULTY ||--o{ COURSES : teaches
 STUDENTS ||--o{ COURSE_ENROLLMENTS : registers
 COURSES ||--o{ COURSE_ENROLLMENTS : contains
 STUDENTS ||--o{ ATTENDANCE_RECORDS : receives
 COURSES ||--o{ ATTENDANCE_RECORDS : tracks
 STUDENTS ||--o{ ACADEMIC_RECORDS : earns
 COURSES ||--o{ ACADEMIC_RECORDS : grades
 STUDENTS ||--o{ PERFORMANCE_METRICS : has
 COURSES ||--o{ COURSE_SCHEDULES : schedules
 STUDENTS ||--o{ RISK_ALERTS : receives
 USERS ||--o{ NOTICES : authors
 NOTICES ||--o{ NOTICE_READS : read_by
 USERS ||--o{ NOTICE_READS : reads
 USERS ||--o{ TASKS : owns
 USERS ||--o{ LEARNING_RESOURCES : uploads
 COURSES ||--o{ LEARNING_RESOURCES : provides
 USERS ||--o{ RECOMMENDATIONS : targets
 COURSES ||--o{ RECOMMENDATIONS : suggests
```

Authoritative schema: `backend/database/migrations`. Update this diagram whenever migrations change.
