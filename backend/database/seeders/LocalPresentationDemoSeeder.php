<?php

namespace Database\Seeders;

use App\Models\AcademicRecord;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSchedule;
use App\Models\Faculty;
use App\Models\PerformanceMetric;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocalPresentationDemoSeeder extends Seeder
{
    private const DEPARTMENT = 'Computer Science & Engineering';
    private const TERM = 'Spring';
    private const YEAR = 2026;

    public function run(): void
    {
        DB::transaction(function (): void {
            // The online demo must not add the unrelated baseline sample data to production.
            if (! app()->environment('production')) {
                $this->call([CampusDemoSeeder::class, CseCourseCatalogSeeder::class]);
            }
            $password = Hash::make($this->demoPassword());
            // Do not use the separate risk-monitoring sample course as part of the academic routine.
            $studentIds = Student::query()->where('student_number', 'like', 'CSE-SPR26-%')->pluck('id');
            $riskCourseIds = Course::query()->where('course_code', 'like', 'DEMO-%')->pluck('id');
            if ($studentIds->isNotEmpty() && $riskCourseIds->isNotEmpty()) {
                AcademicRecord::query()->whereIn('student_id', $studentIds)->whereIn('course_id', $riskCourseIds)->where('source', 'spring-2026-demo')->delete();
                AttendanceRecord::query()->whereIn('student_id', $studentIds)->whereIn('course_id', $riskCourseIds)->delete();
                CourseEnrollment::query()->whereIn('student_id', $studentIds)->whereIn('course_id', $riskCourseIds)->where('semester', self::TERM)->where('year', self::YEAR)->delete();
                CourseSchedule::query()->whereIn('course_id', $riskCourseIds)->where('semester', self::TERM)->where('year', self::YEAR)->where('section', 'SPR26-A')->delete();
            }
            $faculty = collect(range(1, 8))->map(function (int $number) use ($password) {
                $user = User::updateOrCreate(['email' => sprintf('spring26.faculty.%02d@example.edu', $number)], [
                    'name' => sprintf('Spring 2026 Faculty %02d', $number), 'phone' => sprintf('0188600%04d', $number),
                    'password' => $password, 'role' => 'faculty', 'department' => self::DEPARTMENT,
                    'faculty_id' => sprintf('SPR26-FAC-%02d', $number), 'student_id' => null, 'admin_id' => null,
                    'approval_status' => 'approved',
                ]);
                return Faculty::updateOrCreate(['user_id' => $user->id], ['department' => self::DEPARTMENT, 'designation' => $number % 3 === 0 ? 'Assistant Professor' : 'Lecturer']);
            });
            $courses = Course::query()->where('department', self::DEPARTMENT)->where('course_code', 'not like', 'DEMO-%')->orderBy('course_code')->get();
            foreach ($courses as $index => $course) {
                $course->update(['faculty_id' => $faculty[$index % $faculty->count()]->id, 'is_active' => true]);
            }
            $grades = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D'];
            foreach (range(1, 8) as $semester) {
                $semesterCourses = $courses->filter(fn (Course $course) => $this->semesterFor($course->course_code) === $semester)->take(3)->values();
                if ($semesterCourses->isEmpty()) {
                    throw new \RuntimeException("Spring 2026 setup requires at least one catalog course for semester {$semester}.");
                }
                foreach (range(1, 5) as $slot) {
                    $number = sprintf('CSE-SPR26-S%d-%02d', $semester, $slot);
                    $user = User::updateOrCreate(['email' => strtolower($number).'@example.edu'], [
                        'name' => sprintf('Spring 2026 Student S%d-%02d', $semester, $slot), 'phone' => sprintf('01726%05d', $semester * 10 + $slot),
                        'password' => $password, 'role' => 'student', 'department' => self::DEPARTMENT,
                        'student_id' => $number, 'faculty_id' => null, 'admin_id' => null, 'approval_status' => 'approved',
                    ]);
                    $student = Student::updateOrCreate(['user_id' => $user->id], ['student_number' => $number, 'department' => self::DEPARTMENT, 'program' => 'BSc in CSE', 'current_semester' => $semester, 'section' => $semester.'A']);
                    foreach ($semesterCourses as $courseIndex => $course) {
                        CourseEnrollment::updateOrCreate(['student_id' => $student->id, 'course_id' => $course->id, 'semester' => self::TERM, 'year' => self::YEAR]);
                        for ($day = 0; $day < 10; $day++) {
                            $atRisk = $slot === 5;
                            $status = $atRisk && $day >= 5 ? 'absent' : ($day === 2 ? 'late' : 'present');
                            $date = now()->setDate(self::YEAR, 3, 1)->startOfDay()->addDays($day)->toDateString();
                            AttendanceRecord::updateOrCreate(['student_id' => $student->id, 'course_id' => $course->id, 'attendance_date' => $date], ['status' => $status]);
                        }
                        $grade = $slot === 5 ? 'C' : $grades[($slot + $courseIndex + $semester) % count($grades)];
                        AcademicRecord::updateOrCreate(['student_id' => $student->id, 'course_id' => $course->id, 'semester' => self::TERM, 'year' => self::YEAR], ['course_code_snapshot' => $course->course_code, 'course_title_snapshot' => $course->title, 'credit_hours_snapshot' => $course->credit_hours, 'grade' => $grade, 'grade_point' => $this->gradePoint($grade), 'semester_number' => $semester, 'source' => 'spring-2026-demo']);
                    }
                    $cgpa = $slot === 5 ? 2.35 : round(3.0 + (($slot + $semester) % 8) * 0.09, 2);
                    PerformanceMetric::updateOrCreate(['student_id' => $student->id, 'semester' => self::TERM, 'year' => self::YEAR], ['semester_gpa' => $cgpa, 'cgpa' => $cgpa, 'completed_credits' => max(0, ($semester - 1) * 18)]);
                }
                foreach ($semesterCourses as $courseIndex => $course) {
                    CourseSchedule::updateOrCreate(['course_id' => $course->id, 'semester' => self::TERM, 'year' => self::YEAR, 'section' => $semester.'A', 'day_of_week' => (($courseIndex * 2) + 1), 'starts_at' => sprintf('%02d:00:00', 9 + $courseIndex * 2)], ['ends_at' => sprintf('%02d:30:00', 10 + $courseIndex * 2), 'room' => sprintf('CSE-%d0%d', $semester, $courseIndex + 1), 'class_type' => $courseIndex === 2 ? 'lab' : 'lecture']);
                }
            }
            if (! app()->environment('production')) {
                $this->call([CseCurrentTermOperationsSeeder::class, FacultyRiskDemoSeeder::class]);
            }
            $this->call([OfficialSpring2026RoutineSeeder::class]);
        });
        $this->command?->info('Spring 2026 demo data seeded idempotently: 40 synthetic students (five per semester) and eight faculty members.');
    }
    private function semesterFor(string $courseCode): int
    {
        return match (substr(preg_replace('/[^0-9]/', '', $courseCode), 0, 2)) {
            '11' => 1, '12' => 2, '21' => 3, '22' => 4,
            '31' => 5, '32' => 6, '41' => 7, '42' => 8,
            default => 1,
        };
    }

    private function gradePoint(string $grade): float
    {
        return ['A+' => 4.0, 'A' => 3.75, 'A-' => 3.5, 'B+' => 3.25, 'B' => 3.0, 'B-' => 2.75, 'C+' => 2.5, 'C' => 2.25, 'D' => 2.0][$grade];
    }

    private function demoPassword(): string
    {
        $password = (string) env('DEMO_ACCOUNT_PASSWORD', '');
        if (strlen($password) < 12) {
            throw new \RuntimeException('DEMO_ACCOUNT_PASSWORD must be a private environment secret of at least 12 characters.');
        }
        return $password;
    }
}
