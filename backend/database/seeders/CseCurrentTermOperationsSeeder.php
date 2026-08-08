<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CseCurrentTermOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $semester = $now->month <= 6 ? 'Spring' : 'Fall';
        $year = $now->year;
        $courses = Course::query()
            ->whereIn('course_code', ['CSE-1101', 'CSE-1103'])
            ->get()
            ->keyBy('course_code');

        if ($courses->count() !== 2) {
            $this->command?->warn('Current-term CSE operations skipped because first-semester courses are missing.');
            return;
        }

        Student::query()
            ->where('student_number', 'like', 'CSE%')
            ->where('current_semester', 1)
            ->each(function (Student $student) use ($courses, $semester, $year): void {
                foreach ($courses as $course) {
                    CourseEnrollment::firstOrCreate([
                        'course_id' => $course->id,
                        'student_id' => $student->id,
                        'semester' => $semester,
                        'year' => $year,
                    ]);
                }
            });

        $scheduleRows = [
            'CSE-1101' => ['day_of_week' => 1, 'starts_at' => '09:00:00', 'ends_at' => '10:30:00', 'room' => 'Room 301', 'class_type' => 'lecture'],
            'CSE-1103' => ['day_of_week' => 3, 'starts_at' => '11:00:00', 'ends_at' => '12:30:00', 'room' => 'Programming Lab 201', 'class_type' => 'lab'],
        ];

        foreach ($scheduleRows as $code => $row) {
            DB::table('course_schedules')->updateOrInsert(
                [
                    'course_id' => $courses[$code]->id,
                    'semester' => $semester,
                    'year' => $year,
                    'section' => '1A',
                    'day_of_week' => $row['day_of_week'],
                    'starts_at' => $row['starts_at'],
                ],
                array_merge($row, ['updated_at' => $now, 'created_at' => $now]),
            );
        }

        $firstExamDate = $now->copy()->addWeeks(3)->next(Carbon::MONDAY)->toDateString();
        $examRows = [
            'CSE-1101' => ['exam_date' => $firstExamDate, 'starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'room' => 'Exam Hall A'],
            'CSE-1103' => ['exam_date' => Carbon::parse($firstExamDate)->addDays(2)->toDateString(), 'starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'room' => 'Exam Hall B'],
        ];

        foreach ($examRows as $code => $row) {
            DB::table('exam_routines')->updateOrInsert(
                [
                    'course_id' => $courses[$code]->id,
                    'semester' => $semester,
                    'year' => $year,
                    'exam_type' => 'Midterm',
                ],
                array_merge($row, ['section' => '1A', 'updated_at' => $now, 'created_at' => $now]),
            );
        }

        $this->command?->info("{$semester} {$year} CSE enrollment and routines seeded safely.");
    }
}