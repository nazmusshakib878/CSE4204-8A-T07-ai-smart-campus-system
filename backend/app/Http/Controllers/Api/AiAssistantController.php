<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampusTask;
use App\Models\Notice;
use App\Services\OpenAiCampusAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AiAssistantController extends Controller
{
    public function ask(Request $request, OpenAiCampusAssistant $assistant): JsonResponse
    {
        if ($request->user()?->role !== 'student') {
            return response()->json(['status' => false, 'message' => 'Student access is required.'], 403);
        }

        $validator = Validator::make(
            ['question' => is_string($request->input('question')) ? trim($request->input('question')) : $request->input('question')],
            ['question' => ['required', 'string', 'max:1000']]
        );
        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $student = $request->user()->studentProfile;
        if (! $student) {
            return response()->json(['status' => false, 'message' => 'Your student profile is not available.'], 422);
        }

        try {
            $answer = $assistant->answer($validator->validated()['question'], $this->studentContext($student));
        } catch (RuntimeException $exception) {
            report($exception);
            return response()->json(['status' => false, 'message' => 'AI Assistant is temporarily unavailable. Please try again.'], 503);
        }

        return response()->json(['status' => true, 'data' => $answer]);
    }

    private function studentContext($student): array
    {
        $student->load([
            'user:id,name,department',
            'enrollments.course:id,course_code,title',
            'attendanceRecords.course:id,course_code,title',
            'academicRecords.course:id,course_code,title',
            'performanceMetrics',
        ]);

        $attendance = $student->attendanceRecords;
        $present = $attendance->whereIn('status', ['present', 'late'])->count();
        $courses = $student->enrollments->map(fn ($enrollment) => [
            'code' => $enrollment->course?->course_code,
            'title' => $enrollment->course?->title,
            'semester' => $enrollment->semester,
            'year' => $enrollment->year,
        ])->values()->all();
        $scores = $student->academicRecords->map(fn ($record) => [
            'course' => $record->course?->course_code ?: $record->course?->title,
            'grade' => $record->grade,
            'semester' => $record->semester,
            'year' => $record->year,
        ])->values()->all();
        $metric = $student->performanceMetrics->sortByDesc('created_at')->first();
        $tasks = CampusTask::query()->where('assigned_to_user_id', $student->user_id)
            ->where('status', '!=', 'completed')->orderBy('due_date')->limit(10)
            ->get(['title', 'description', 'due_date', 'status', 'priority'])->all();
        $user = $student->user;
        $notices = Notice::query()->whereNull('archived_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(function ($query) use ($student, $user) {
                $query->where('audience', 'All')
                    ->orWhere('audience', 'Students')
                    ->orWhere(fn ($department) => $department->where('audience', 'Department')
                        ->where('target_department', $student->department ?: $user?->department)
                        ->where(fn ($role) => $role->whereNull('target_role')->orWhere('target_role', 'All')->orWhere('target_role', 'Students')))
                    ->orWhere(fn ($individual) => $individual->where('audience', 'Individual')->where(function ($recipient) use ($user) {
                        $recipient->where('recipient_reference', $user?->student_id)->orWhere('recipient_reference', $user?->email);
                    }));
            })->orderByDesc('publish_date')->limit(5)->get(['title', 'description', 'publish_date'])->all();

        return [
            'student_name' => $student->user?->name,
            'department' => $student->department ?: $student->user?->department,
            'program' => $student->program,
            'current_semester' => $student->current_semester,
            'attendance_percentage' => $attendance->isNotEmpty() ? (int) round($present * 100 / $attendance->count()) : null,
            'current_cgpa' => $metric?->cgpa,
            'enrolled_courses' => $courses,
            'recorded_course_results' => $scores,
            'current_tasks' => $tasks,
            'recent_notices' => $notices,
        ];
    }
}
