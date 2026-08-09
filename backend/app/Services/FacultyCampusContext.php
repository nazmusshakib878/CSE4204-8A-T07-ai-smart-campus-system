<?php

namespace App\Services;

use App\Models\AcademicRecord;
use App\Models\AttendanceRecord;
use App\Models\CourseSchedule;
use App\Models\Faculty;

class FacultyCampusContext
{
    private const ALLOWED_SECTIONS = ['profile', 'courses', 'attendance', 'results', 'schedule', 'appointments', 'policies'];

    public function get(Faculty $faculty, array $requestedSections): array
    {
        $sections = array_values(array_intersect(self::ALLOWED_SECTIONS, $requestedSections));
        $context = [];

        foreach ($sections as $section) {
            $context[$section] = match ($section) {
                'profile' => $this->profile($faculty),
                'courses' => $this->courses($faculty),
                'attendance' => $this->attendance($faculty),
                'results' => $this->results($faculty),
                'schedule' => $this->schedule($faculty),
                'appointments' => ['available' => false, 'message' => 'Appointment data is not available in this system.'],
                'policies' => ['available' => false, 'message' => 'Official institutional policy data is not available in this system.'],
            };
        }

        return $context;
    }

    private function profile(Faculty $faculty): array
    {
        $faculty->loadMissing('user:id,name,department');

        return [
            'role' => 'faculty',
            'name' => $faculty->user?->name,
            'department' => $faculty->department ?: $faculty->user?->department,
            'designation' => $faculty->designation,
        ];
    }

    private function courses(Faculty $faculty): array
    {
        return $faculty->courses()
            ->withCount('enrollments')
            ->orderBy('course_code')
            ->get(['id', 'faculty_id', 'course_code', 'title', 'department', 'credit_hours'])
            ->take(20)
            ->map(fn ($course) => [
                'code' => $course->course_code,
                'title' => $course->title,
                'department' => $course->department,
                'credit_hours' => $course->credit_hours,
                'enrolled_students' => $course->enrollments_count,
            ])->values()->all();
    }

    private function attendance(Faculty $faculty): array
    {
        $courseIds = $faculty->courses()->pluck('id');
        $records = AttendanceRecord::query()
            ->with(['course:id,course_code,title', 'student:id,user_id,student_number', 'student.user:id,name'])
            ->whereIn('course_id', $courseIds)
            ->latest('attendance_date')
            ->limit(200)
            ->get();

        if ($records->isEmpty()) {
            return ['available' => false, 'message' => 'No attendance records are available for your assigned courses.'];
        }

        return [
            'available' => true,
            'scope' => 'Only students and attendance from the authenticated faculty member\'s assigned courses.',
            'students' => $records->groupBy(fn ($record) => $record->student_id.':'.$record->course_id)
                ->map(function ($studentRecords) {
                    $attended = $studentRecords->whereIn('status', ['present', 'late'])->count();
                    $total = $studentRecords->count();

                    return [
                        'student_name' => $studentRecords->first()?->student?->user?->name,
                        'student_number' => $studentRecords->first()?->student?->student_number,
                        'course_code' => $studentRecords->first()?->course?->course_code,
                        'attended_classes' => $attended,
                        'total_classes' => $total,
                        'percentage' => $total > 0 ? round($attended * 100 / $total, 1) : null,
                    ];
                })->take(60)->values()->all(),
        ];
    }

    private function results(Faculty $faculty): array
    {
        $courseIds = $faculty->courses()->pluck('id');
        $records = AcademicRecord::query()
            ->with(['course:id,course_code,title', 'student:id,user_id,student_number', 'student.user:id,name'])
            ->whereIn('course_id', $courseIds)
            ->withoutDemo()
            ->latest()
            ->limit(100)
            ->get();

        if ($records->isEmpty()) {
            return ['available' => false, 'message' => 'No result records are available for your assigned courses.'];
        }

        return [
            'available' => true,
            'scope' => 'Only results from the authenticated faculty member\'s assigned courses.',
            'grades' => $records->map(fn ($record) => [
                'student_name' => $record->student?->user?->name,
                'student_number' => $record->student?->student_number,
                'course_code' => $record->course?->course_code ?: $record->course_code_snapshot,
                'course_title' => $record->course?->title ?: $record->course_title_snapshot,
                'grade' => $record->grade,
                'grade_point' => $record->grade_point,
                'semester' => $record->semester,
                'year' => $record->year,
            ])->values()->all(),
        ];
    }

    private function schedule(Faculty $faculty): array
    {
        $courseIds = $faculty->courses()->pluck('id');
        $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $schedules = CourseSchedule::query()
            ->with('course:id,course_code,title')
            ->whereIn('course_id', $courseIds)
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        if ($schedules->isEmpty()) {
            return ['available' => false, 'message' => 'No class schedule is available for your assigned courses.'];
        }

        return [
            'available' => true,
            'timezone' => (string) config('app.timezone'),
            'current_datetime' => now()->toIso8601String(),
            'classes' => $schedules->take(30)->map(fn ($schedule) => [
                'course_code' => $schedule->course?->course_code,
                'course_title' => $schedule->course?->title,
                'day' => $days[$schedule->day_of_week] ?? (string) $schedule->day_of_week,
                'starts_at' => $schedule->starts_at,
                'ends_at' => $schedule->ends_at,
                'room' => $schedule->room,
                'class_type' => $schedule->class_type,
                'semester' => $schedule->semester,
                'year' => $schedule->year,
            ])->values()->all(),
        ];
    }
}
