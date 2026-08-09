<?php

namespace Database\Seeders;

use App\Models\AcademicRecord;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Faculty;
use App\Models\PerformanceMetric;
use App\Models\RiskAlert;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

class FacultyRiskDemoSeeder extends Seeder
{
    public function run(): void
    {
        $facultyUser = $this->currentFacultyUser();
        $faculty = Faculty::firstOrCreate(
            ['user_id' => $facultyUser->id],
            ['department' => $facultyUser->department, 'designation' => 'Faculty Member'],
        );

        $course = Course::updateOrCreate(
            ['course_code' => 'DEMO-RISK-'.$faculty->id],
            [
                'faculty_id' => $faculty->id,
                'title' => 'Faculty Risk Monitoring Demo',
                'department' => $faculty->department ?: $facultyUser->department,
                'credit_hours' => 3,
                'description' => 'Database-backed sample course for Faculty Risk Alerts.',
                'is_active' => true,
            ],
        );

        $year = (int) now()->year;
        $students = [
            [
                'key' => 'A',
                'name' => 'Ayesha Rahman',
                'attendance_present' => 13,
                'cgpa' => 1.85,
                'semester_gpa' => 1.70,
                'grade' => 'F',
                'risk_level' => 'high',
                'risk_score' => 92,
                'prediction' => 'Critical academic risk based on very low attendance and CGPA.',
                'advice' => 'Arrange an urgent advising meeting and a structured academic recovery plan.',
                'reasons' => ['Attendance is 52%', 'CGPA is 1.85'],
            ],
            [
                'key' => 'B',
                'name' => 'Farhan Ahmed',
                'attendance_present' => 17,
                'cgpa' => 2.30,
                'semester_gpa' => 2.35,
                'grade' => 'C',
                'risk_level' => 'medium',
                'risk_score' => 52,
                'prediction' => 'Medium academic risk based on attendance and CGPA trends.',
                'advice' => 'Monitor attendance weekly and provide targeted academic support.',
                'reasons' => ['Attendance is 68%', 'CGPA is 2.30'],
            ],
            [
                'key' => 'C',
                'name' => 'Nusrat Jahan',
                'attendance_present' => 22,
                'cgpa' => 3.45,
                'semester_gpa' => 3.50,
                'grade' => 'A-',
                'risk_level' => 'low',
                'risk_score' => 8,
                'prediction' => 'Academic indicators are on track.',
                'advice' => 'Continue the current study and attendance habits.',
                'reasons' => ['Attendance is 88%', 'CGPA is 3.45'],
            ],
        ];

        foreach ($students as $index => $data) {
            $suffix = $faculty->id.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $email = 'risk.demo.'.strtolower($data['key']).'.faculty'.$faculty->id.'@example.edu';
            $studentNumber = 'CSE-DEMO-'.$suffix;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $data['name'],
                    'phone' => '0198'.str_pad($suffix, 7, '0', STR_PAD_LEFT),
                    'password' => Hash::make('DemoStudent@2026'),
                    'role' => 'student',
                    'department' => $faculty->department ?: $facultyUser->department,
                    'student_id' => $studentNumber,
                    'faculty_id' => null,
                    'admin_id' => null,
                    'approval_status' => 'approved',
                ],
            );

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_number' => $studentNumber,
                    'department' => $user->department,
                    'program' => 'BSc in CSE',
                    'current_semester' => 6,
                ],
            );

            CourseEnrollment::updateOrCreate(
                ['student_id' => $student->id, 'course_id' => $course->id],
                ['semester' => 'Demo Term', 'year' => $year],
            );

            foreach (range(0, 24) as $day) {
                AttendanceRecord::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'attendance_date' => now()->subDays(24 - $day)->toDateString(),
                    ],
                    ['status' => $day < $data['attendance_present'] ? 'present' : 'absent'],
                );
            }

            AcademicRecord::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'semester' => 'Demo Term',
                    'year' => $year,
                ],
                [
                    'course_code_snapshot' => $course->course_code,
                    'course_title_snapshot' => $course->title,
                    'credit_hours_snapshot' => 3,
                    'grade' => $data['grade'],
                    'source' => 'demo-risk',
                ],
            );

            PerformanceMetric::updateOrCreate(
                ['student_id' => $student->id, 'semester' => 'Demo Term', 'year' => $year],
                [
                    'cgpa' => $data['cgpa'],
                    'semester_gpa' => $data['semester_gpa'],
                    'completed_credits' => 72,
                ],
            );

            RiskAlert::updateOrCreate(
                ['student_id' => $student->id, 'model' => 'faculty-risk-demo'],
                [
                    'faculty_user_id' => $facultyUser->id,
                    'risk_level' => $data['risk_level'],
                    'risk_score' => $data['risk_score'],
                    'prediction' => $data['prediction'],
                    'advice' => $data['advice'],
                    'reasons' => $data['reasons'],
                    'source' => 'baseline',
                    'analyzed_at' => now(),
                ],
            );
        }

        $this->command?->info('Seeded 3 database-backed Faculty Risk Alert demo students for '.$facultyUser->email.'.');
    }

    private function currentFacultyUser(): User
    {
        $facultyUsers = User::query()
            ->where('role', 'faculty')
            ->where('approval_status', 'approved')
            ->whereHas('facultyProfile')
            ->pluck('id');

        $activeFacultyId = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', $facultyUsers)
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->value('tokenable_id');

        $facultyUser = User::query()
            ->where('role', 'faculty')
            ->where('approval_status', 'approved')
            ->when($activeFacultyId, fn ($query) => $query->whereKey($activeFacultyId))
            ->first();

        $facultyUser ??= User::query()
            ->where('role', 'faculty')
            ->where('approval_status', 'approved')
            ->whereHas('facultyProfile')
            ->orderBy('id')
            ->first();

        return $facultyUser ?? throw new RuntimeException('No approved Faculty account is available for risk demo data.');
    }
}
