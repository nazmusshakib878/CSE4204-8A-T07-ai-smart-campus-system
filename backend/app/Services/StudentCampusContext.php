<?php

namespace App\Services;

use App\Models\CourseSchedule;
use App\Models\Student;

class StudentCampusContext
{
    private const ALLOWED_SECTIONS = ['profile', 'courses', 'attendance', 'results', 'schedule', 'appointments', 'policies'];

    public function get(Student $student, array $requestedSections): array
    {
        $sections = array_values(array_intersect(self::ALLOWED_SECTIONS, $requestedSections));
        $context = [];

        foreach ($sections as $section) {
            $context[$section] = match ($section) {
                'profile' => $this->profile($student),
                'courses' => $this->courses($student),
                'attendance' => $this->attendance($student),
                'results' => $this->results($student),
                'schedule' => $this->schedule($student),
                'appointments' => ['available' => false, 'message' => 'Appointment data is not available in this system.'],
                'policies' => ['available' => false, 'message' => 'Official institutional policy data is not available in this system.'],
            };
        }

        return $context;
    }

    private function profile(Student $student): array
    {
        $student->loadMissing('user:id,name,department');

        return [
            'name' => $student->user?->name,
            'department' => $student->department ?: $student->user?->department,
            'program' => $student->program,
            'current_semester' => $student->current_semester,
            'section' => $student->section,
        ];
    }

    private function courses(Student $student): array
    {
        $student->loadMissing('enrollments.course:id,course_code,title');

        return $student->enrollments->map(fn ($enrollment) => [
            'code' => $enrollment->course?->course_code,
            'title' => $enrollment->course?->title,
            'semester' => $enrollment->semester,
            'year' => $enrollment->year,
        ])->take(12)->values()->all();
    }

    private function attendance(Student $student): array
    {
        $student->loadMissing('attendanceRecords.course:id,course_code,title');

        if ($student->attendanceRecords->isEmpty()) {
            return ['available' => false, 'message' => 'No attendance records are available.'];
        }

        $summarize = function ($records): array {
            $attended = $records->whereIn('status', ['present', 'late'])->count();
            $total = $records->count();

            return [
                'attended_classes' => $attended,
                'total_classes' => $total,
                'percentage' => $total > 0 ? round($attended * 100 / $total, 1) : null,
            ];
        };

        return [
            'available' => true,
            'overall' => $summarize($student->attendanceRecords),
            'by_course' => $student->attendanceRecords->groupBy('course_id')->map(function ($records) use ($summarize) {
                return array_merge([
                    'course_code' => $records->first()?->course?->course_code,
                    'course_title' => $records->first()?->course?->title,
                ], $summarize($records));
            })->values()->all(),
        ];
    }

    private function results(Student $student): array
    {
        $student->loadMissing([
            'academicRecords.course:id,course_code,title',
            'assessmentRecords.course:id,course_code,title',
            'performanceMetrics',
        ]);

        $academicRecords = $student->academicRecords->filter(fn ($record) => $record->source !== 'demo');

        if ($academicRecords->isEmpty() && $student->assessmentRecords->isEmpty() && $student->performanceMetrics->isEmpty()) {
            return ['available' => false, 'message' => 'No result or performance records are available.'];
        }

        return [
            'available' => true,
            'latest_performance' => $student->performanceMetrics->sortByDesc('created_at')->take(4)->map(fn ($metric) => [
                'semester' => $metric->semester,
                'year' => $metric->year,
                'cgpa' => $metric->cgpa,
                'semester_gpa' => $metric->semester_gpa,
                'completed_credits' => $metric->completed_credits,
            ])->values()->all(),
            'grades' => $academicRecords->sortByDesc('created_at')->take(10)->map(fn ($record) => [
                'course_code' => $record->course?->course_code ?: $record->course_code_snapshot,
                'course_title' => $record->course?->title ?: $record->course_title_snapshot,
                'grade' => $record->grade,
                'grade_point' => $record->grade_point,
                'semester' => $record->semester,
                'year' => $record->year,
            ])->values()->all(),
            'assessments' => $student->assessmentRecords->sortByDesc('created_at')->take(10)->map(fn ($record) => [
                'course_code' => $record->course?->course_code,
                'course_title' => $record->course?->title,
                'quiz_marks' => $record->quiz_marks,
                'assignment_marks' => $record->assignment_marks,
                'mid_marks' => $record->mid_marks,
                'final_marks' => $record->final_marks,
                'total_marks' => $record->total_marks,
                'semester' => $record->semester,
                'year' => $record->year,
            ])->values()->all(),
        ];
    }

    private function schedule(Student $student): array
    {
        $courseIds = $student->enrollments()->pluck('course_id');
        $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $schedules = CourseSchedule::query()
            ->with('course:id,course_code,title')
            ->whereIn('course_id', $courseIds)
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        if ($schedules->isEmpty()) {
            return ['available' => false, 'message' => 'No class schedule is available for the enrolled courses.'];
        }

        return [
            'available' => true,
            'timezone' => (string) config('app.timezone'),
            'current_datetime' => now()->toIso8601String(),
            'classes' => $schedules->take(20)->map(fn ($schedule) => [
                'course_code' => $schedule->course?->course_code,
                'course_title' => $schedule->course?->title,
                'day' => $days[$schedule->day_of_week] ?? (string) $schedule->day_of_week,
                'starts_at' => $schedule->starts_at,
                'ends_at' => $schedule->ends_at,
                'room' => $schedule->room,
                'class_type' => $schedule->class_type,
                'semester' => $schedule->semester,
                'year' => $schedule->year,
            ])->all(),
        ];
    }
}
