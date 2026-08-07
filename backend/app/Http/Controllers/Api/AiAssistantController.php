<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            ['question' => ['required', 'string', 'max:1000', 'min:1']]
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
            'attendanceRecords',
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
        $metric = $student->performanceMetrics->sortByDesc('created_at')->first();

        return [
            'name' => $student->user?->name,
            'department' => $student->department ?: $student->user?->department,
            'program' => $student->program,
            'attendance' => $attendance->isNotEmpty() ? (int) round($present * 100 / $attendance->count()) : null,
            'cgpa' => $metric?->cgpa,
            'courses' => $courses,
        ];
    }
}
