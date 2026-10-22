<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class VerifyOnlineDemo extends Command
{
    protected $signature = 'demo:verify-online';
    protected $description = 'Read-only verification of the idempotent Spring 2026 online demo dataset.';

    public function handle(): int
    {
        try {
            $students = DB::scalar("SELECT COUNT(*) FROM users WHERE email REGEXP '^student[1-8][1-5]@gmail\\\\.com$' AND role = 'student'");
            $faculty = DB::scalar("SELECT COUNT(*) FROM users WHERE email REGEXP '^spring26\\\\.faculty\\\\.[0-9]{2}@example\\\\.edu$' AND role = 'faculty'");
            $cohorts = DB::scalar("SELECT COUNT(*) FROM (SELECT s.current_semester FROM students s JOIN users u ON u.id = s.user_id WHERE u.email REGEXP '^student[1-8][1-5]@gmail\\\\.com$' GROUP BY s.current_semester HAVING COUNT(*) = 5) AS cohorts");
            $schedules = DB::scalar("SELECT COUNT(*) FROM course_schedules WHERE semester = 'Spring' AND year = 2026 AND section REGEXP '^[1-8]A$' AND source_reference LIKE 'Spring_2026_A_Sections_Official_Routine.csv%'");
            $enrollments = DB::scalar("SELECT COUNT(*) FROM course_enrollments ce JOIN students s ON s.id = ce.student_id JOIN users u ON u.id = s.user_id WHERE u.email REGEXP '^student[1-8][1-5]@gmail\\\\.com$' AND ce.semester = 'Spring' AND ce.year = 2026");
            $records = DB::scalar("SELECT COUNT(*) FROM academic_records ar JOIN students s ON s.id = ar.student_id JOIN users u ON u.id = s.user_id WHERE u.email REGEXP '^student[1-8][1-5]@gmail\\\\.com$' AND ar.source = 'spring-2026-demo'");
            $attendance = DB::scalar("SELECT COUNT(*) FROM attendance_records ar JOIN students s ON s.id = ar.student_id JOIN users u ON u.id = s.user_id WHERE u.email REGEXP '^student[1-8][1-5]@gmail\\\\.com$'");
            $duplicates = DB::scalar("SELECT COUNT(*) FROM (SELECT email FROM users WHERE email REGEXP '^student[1-8][1-5]@gmail\\\\.com$' GROUP BY email HAVING COUNT(*) > 1) AS duplicate_emails");

            $checks = [
                'ONLINE_DEMO_STUDENTS' => [(int) $students, 40],
                'ONLINE_DEMO_FACULTY' => [(int) $faculty, 8],
                'ONLINE_DEMO_COHORTS' => [(int) $cohorts, 8],
                'ONLINE_DEMO_OFFICIAL_SCHEDULES' => [(int) $schedules, 96],
                // OfficialSpring2026RoutineSeeder enrolls each cohort in every
                // unique section-course from the 96 approved routine rows.
                'ONLINE_DEMO_ENROLLMENTS' => [(int) $enrollments, 375],
                'ONLINE_DEMO_RECORDS' => [(int) $records, 105],
                'ONLINE_DEMO_ATTENDANCE' => [(int) $attendance, 1050],
                'ONLINE_DEMO_DUPLICATE_EMAILS' => [(int) $duplicates, 0],
            ];
            $valid = true;
            foreach ($checks as $label => [$actual, $expected]) {
                $this->line("{$label}={$actual}");
                if ($actual !== $expected) {
                    $valid = false;
                }
            }
            if (! $valid) {
                $this->error('ONLINE_DEMO_VERIFICATION=FAIL');
                return self::FAILURE;
            }
            $this->info('ONLINE_DEMO_VERIFICATION=OK');
            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('ONLINE_DEMO_VERIFICATION=FAIL');
            return self::FAILURE;
        }
    }
}
