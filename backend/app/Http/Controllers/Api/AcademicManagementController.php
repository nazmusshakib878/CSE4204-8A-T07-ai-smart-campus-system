<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicRecord;
use App\Models\AssessmentRecord;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Faculty;
use App\Models\PerformanceMetric;
use App\Models\Student;
use App\Services\AcademicResultService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->ensureAcademicStaff($request)) return $response;

        $courses = $this->accessibleCourses($request)
            ->with(['faculty.user:id,name,email,faculty_id', 'enrollments'])
            ->orderBy('course_code')
            ->get()
            ->map(fn (Course $course) => $this->formatCourse($course));

        $students = Student::with('user:id,name,email,department,student_id,approval_status')
            ->whereHas('user', fn ($query) => $query->where('approval_status', 'approved'))
            ->orderBy('student_number')
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'student_number' => $student->student_number ?: $student->user?->student_id,
                'name' => $student->user?->name,
                'email' => $student->user?->email,
                'department' => $student->department ?: $student->user?->department,
                'program' => $student->program,
            ]);

        $faculty = collect();
        if ($request->user()->role === 'admin') {
            $faculty = Faculty::with('user:id,name,email,department,faculty_id,approval_status')
                ->whereHas('user', fn ($query) => $query->where('approval_status', 'approved'))
                ->get()
                ->map(fn (Faculty $member) => [
                    'id' => $member->id,
                    'name' => $member->user?->name,
                    'faculty_id' => $member->user?->faculty_id,
                    'department' => $member->department ?: $member->user?->department,
                    'designation' => $member->designation,
                ]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'courses' => $courses,
                'students' => $students,
                'faculty' => $faculty,
                'can_manage_courses' => $request->user()->role === 'admin',
            ],
        ]);
    }

    public function storeCourse(Request $request): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) return $response;

        $data = $request->validate($this->courseRules());

        $course = Course::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Course created successfully.',
            'data' => $this->formatCourse($course->load('faculty.user')),
        ], 201);
    }

    public function updateCourse(Request $request, Course $course): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) return $response;

        $data = $request->validate($this->courseRules($course));
        $course->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Course updated successfully.',
            'data' => $this->formatCourse($course->fresh(['faculty.user', 'enrollments'])),
        ]);
    }

    public function destroyCourse(Request $request, Course $course): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) return $response;

        if ($course->enrollments()->exists() || $course->academicRecords()->exists() || $course->attendanceRecords()->exists() || $course->courseRecommendations()->exists()) {
            return response()->json(['status' => false, 'message' => 'This course has academic history and cannot be deleted. Mark it inactive instead.'], 409);
        }

        $course->delete();

        return response()->json(['status' => true, 'message' => 'Unused course deleted successfully.']);
    }

    public function workspace(Request $request, Course $course): JsonResponse
    {
        if ($response = $this->ensureCourseAccess($request, $course)) return $response;

        $course->load([
            'faculty.user:id,name',
            'enrollments.student.user:id,name,email,department,student_id',
            'enrollments.student.performanceMetrics',
            'enrollments.student.academicRecords' => fn ($query) => $query->where('course_id', $course->id),
            'enrollments.student.assessmentRecords' => fn ($query) => $query->where('course_id', $course->id),
            'enrollments.student.attendanceRecords' => fn ($query) => $query->where('course_id', $course->id),
        ]);

        $enrollments = $course->enrollments->map(function (CourseEnrollment $enrollment) {
            $student = $enrollment->student;
            $attendance = $student->attendanceRecords;
            $present = $attendance->whereIn('status', ['present', 'late'])->count();

            return [
                'enrollment_id' => $enrollment->id,
                'semester' => $enrollment->semester,
                'year' => $enrollment->year,
                'student' => [
                    'id' => $student->id,
                    'student_number' => $student->student_number ?: $student->user?->student_id,
                    'name' => $student->user?->name,
                    'email' => $student->user?->email,
                    'department' => $student->department ?: $student->user?->department,
                    'current_semester' => $student->current_semester,
                ],
                'attendance_percentage' => $attendance->count()
                    ? (int) round($present * 100 / $attendance->count())
                    : 0,
                'attendance_records' => $attendance->sortByDesc('attendance_date')->values()->map(fn ($record) => [
                    'id' => $record->id,
                    'date' => $record->attendance_date,
                    'status' => $record->status,
                ]),
                'assessments' => $student->assessmentRecords->sortByDesc('year')->values()->map(fn ($record) => [
                    'id' => $record->id, 'semester' => $record->semester, 'year' => $record->year,
                    'quiz_marks' => $record->quiz_marks, 'assignment_marks' => $record->assignment_marks,
                    'mid_marks' => $record->mid_marks, 'final_marks' => $record->final_marks, 'total_marks' => $record->total_marks,
                ]),                'grades' => $student->academicRecords->sortByDesc('year')->values()->map(fn ($record) => [
                    'id' => $record->id,
                    'grade' => $record->grade,
                    'semester' => $record->semester,
                    'year' => $record->year,
                ]),
                'performance' => $student->performanceMetrics->sortByDesc('year')->values()->map(fn ($metric) => [
                    'id' => $metric->id,
                    'semester' => $metric->semester,
                    'year' => $metric->year,
                    'cgpa' => $metric->cgpa,
                    'completed_credits' => $metric->completed_credits,
                ]),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'course' => $this->formatCourse($course),
                'enrollments' => $enrollments,
            ],
        ]);
    }

    public function enroll(Request $request, Course $course): JsonResponse
    {
        if ($response = $this->ensureCourseAccess($request, $course)) return $response;

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')],
            'semester' => ['required', Rule::in(['Spring', 'Fall'])],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        DB::transaction(function () use ($course, $data) {
            foreach (array_unique($data['student_ids']) as $studentId) {
                CourseEnrollment::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'student_id' => $studentId,
                        'semester' => $data['semester'],
                        'year' => $data['year'],
                    ],
                    []
                );
            }
        });

        return response()->json(['status' => true, 'message' => 'Students enrolled successfully.']);
    }

    public function unenroll(Request $request, Course $course, CourseEnrollment $enrollment): JsonResponse
    {
        if ($response = $this->ensureCourseAccess($request, $course)) return $response;
        if ($enrollment->course_id !== $course->id) {
            return response()->json(['status' => false, 'message' => 'Enrollment does not belong to this course.'], 422);
        }
        if ($course->attendanceRecords()->where('student_id', $enrollment->student_id)->exists() || $course->academicRecords()->where('student_id', $enrollment->student_id)->exists() || $course->assessmentRecords()->where('student_id', $enrollment->student_id)->exists()) {
            return response()->json(['status' => false, 'message' => 'This enrollment has academic history and cannot be removed.'], 409);
        }

        $enrollment->delete();

        return response()->json(['status' => true, 'message' => 'Student removed from the course.']);
    }

    public function saveAttendance(Request $request, Course $course): JsonResponse
    {
        if ($response = $this->ensureCourseAccess($request, $course)) return $response;

        $data = $request->validate([
            'attendance_date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'records.*.status' => ['required', Rule::in(['present', 'absent', 'late'])],
        ]);
        $this->assertEnrolledStudents($course, collect($data['records'])->pluck('student_id')->all());

        DB::transaction(function () use ($course, $data) {
            foreach ($data['records'] as $record) {
                AttendanceRecord::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'student_id' => $record['student_id'],
                        'attendance_date' => $data['attendance_date'],
                    ],
                    ['status' => $record['status']]
                );
            }
        });

        return response()->json(['status' => true, 'message' => 'Attendance saved successfully.']);
    }

    public function saveAssessments(Request $request, Course $course, AcademicResultService $calculator): JsonResponse
    {
        if ($response = $this->ensureCourseAccess($request, $course)) return $response;
        $data = $request->validate([
            'semester' => ['required', Rule::in(['Spring', 'Fall'])], 'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'records' => ['required', 'array', 'min:1'], 'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'records.*.quiz_marks' => ['required', 'numeric', 'min:0', 'max:15'], 'records.*.assignment_marks' => ['required', 'numeric', 'min:0', 'max:15'],
            'records.*.mid_marks' => ['required', 'numeric', 'min:0', 'max:30'], 'records.*.final_marks' => ['required', 'numeric', 'min:0', 'max:40'],
        ]);
        $this->assertEnrolledStudents($course, collect($data['records'])->pluck('student_id')->all());
        DB::transaction(function () use ($course, $data, $calculator) {
            foreach ($data['records'] as $record) {
                $total = round((float) $record['quiz_marks'] + (float) $record['assignment_marks'] + (float) $record['mid_marks'] + (float) $record['final_marks'], 2);
                [$grade] = $calculator->gradeFromMarks($total);
                AssessmentRecord::updateOrCreate(['student_id' => $record['student_id'], 'course_id' => $course->id, 'semester' => $data['semester'], 'year' => $data['year']], [...$record, 'total_marks' => $total]);
                AcademicRecord::updateOrCreate(['student_id' => $record['student_id'], 'course_id' => $course->id, 'semester' => $data['semester'], 'year' => $data['year']], ['grade' => $grade]);
                $calculator->recalculate(Student::findOrFail($record['student_id']), $data['semester'], (int) $data['year']);
            }
        });
        return response()->json(['status' => true, 'message' => 'Assessment marks, final grades, and CGPA calculated successfully.']);
    }
    public function saveGrades(Request $request, Course $course, AcademicResultService $calculator): JsonResponse
    {
        if ($response = $this->ensureCourseAccess($request, $course)) return $response;

        $data = $request->validate([
            'semester' => ['required', Rule::in(['Spring', 'Fall'])],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'records.*.grade' => ['required', 'string', Rule::in(['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'F'])],
        ]);
        $this->assertEnrolledStudents($course, collect($data['records'])->pluck('student_id')->all());

        DB::transaction(function () use ($course, $data, $calculator) {
            foreach ($data['records'] as $record) {
                AcademicRecord::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'student_id' => $record['student_id'],
                        'semester' => $data['semester'],
                        'year' => $data['year'],
                    ],
                    ['grade' => $record['grade']]
                );
                $calculator->recalculate(Student::findOrFail($record['student_id']), $data['semester'], (int) $data['year']);
            }
        });

        return response()->json(['status' => true, 'message' => 'Grades saved successfully.']);
    }

    public function savePerformance(Request $request, Course $course, Student $student): JsonResponse
    {
        if ($response = $this->ensureCourseAccess($request, $course)) return $response;
        $this->assertEnrolledStudents($course, [$student->id]);

        $data = $request->validate([
            'semester' => ['required', Rule::in(['Spring', 'Fall'])],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'current_semester' => ['required', 'integer', 'min:1', 'max:8'],
            'cgpa' => ['required', 'numeric', 'min:0', 'max:4'],
            'completed_credits' => ['required', 'integer', 'min:0', 'max:300'],
        ]);

        $student->update(['current_semester' => $data['current_semester']]);
        unset($data['current_semester']);

        $metric = PerformanceMetric::updateOrCreate(
            ['student_id' => $student->id, 'semester' => $data['semester'], 'year' => $data['year']],
            $data
        );

        return response()->json([
            'status' => true,
            'message' => 'CGPA and current semester updated successfully.',
            'data' => $metric,
        ]);
    }

    private function courseRules(?Course $course = null): array
    {
        return [
            'course_code' => ['required', 'string', 'max:30', Rule::unique('courses', 'course_code')->ignore($course?->id)],
            'title' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'credit_hours' => ['required', 'numeric', 'min:0.5', 'max:12'],
            'description' => ['nullable', 'string', 'max:2000'],
            'faculty_id' => ['nullable', Rule::exists('faculty', 'id')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function accessibleCourses(Request $request)
    {
        $query = Course::query();
        if ($request->user()->role === 'admin') return $query;

        $facultyId = Faculty::where('user_id', $request->user()->id)->value('id');
        return $query->where('faculty_id', $facultyId ?: 0);
    }

    private function ensureAcademicStaff(Request $request): ?JsonResponse
    {
        if (! in_array($request->user()?->role, ['admin', 'faculty'], true)) {
            return response()->json(['status' => false, 'message' => 'Only administrators and faculty can access academic management.'], 403);
        }
        return null;
    }

    private function ensureAdmin(Request $request): ?JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['status' => false, 'message' => 'Only administrators can manage courses and faculty assignments.'], 403);
        }
        return null;
    }

    private function ensureCourseAccess(Request $request, Course $course): ?JsonResponse
    {
        if ($response = $this->ensureAcademicStaff($request)) return $response;
        if ($request->user()->role === 'admin') return null;

        $facultyId = Faculty::where('user_id', $request->user()->id)->value('id');
        if (! $facultyId || $course->faculty_id !== $facultyId) {
            return response()->json(['status' => false, 'message' => 'This course is not assigned to you.'], 403);
        }
        return null;
    }

    private function assertEnrolledStudents(Course $course, array $studentIds): void
    {
        $allowed = CourseEnrollment::where('course_id', $course->id)
            ->whereIn('student_id', array_unique($studentIds))
            ->pluck('student_id')->unique();

        if ($allowed->count() !== collect($studentIds)->unique()->count()) {
            throw new HttpResponseException(response()->json([
                'status' => false,
                'message' => 'Attendance, grades, or performance can only be entered for enrolled students.',
            ], 422));
        }
    }

    private function formatCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'course_code' => $course->course_code,
            'title' => $course->title,
            'department' => $course->department,
            'credit_hours' => (float) $course->credit_hours,
            'description' => $course->description,
            'is_active' => (bool) $course->is_active,
            'faculty_id' => $course->faculty_id,
            'faculty_name' => $course->faculty?->user?->name,
            'faculty_number' => $course->faculty?->user?->faculty_id,
            'enrollment_count' => $course->relationLoaded('enrollments') ? $course->enrollments->count() : 0,
        ];
    }
}
