<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\RiskAlert;
use App\Models\Student;
use App\Services\OpenAiRiskAnalyzer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class StudentMonitoringController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->ensureFacultyOrAdmin($request)) {
            return $response;
        }

        $students = $this->accessibleStudents($request)->with([
            'user:id,name,email,department,student_id,updated_at',
            'attendanceRecords.course:id,course_code,title',
            'academicRecords.course:id,course_code,title',
            'performanceMetrics',
            'riskAlerts' => fn ($query) => $query->latest('analyzed_at')->latest(),
        ])->get();

        $formatted = $students->map(fn (Student $student) => $this->formatStudent($student));

        return response()->json([
            'status' => true,
            'data' => [
                'students' => $formatted->values(),
                'charts' => $this->chartData($students),
                'summary' => [
                    'total' => $formatted->count(),
                    'high_risk' => $formatted->where('priority', 'high')->count(),
                    'medium_risk' => $formatted->where('priority', 'medium')->count(),
                    'on_track' => $formatted->where('priority', 'low')->count(),
                ],
                'ai_configured' => (string) config('services.openai.api_key') !== '',
            ],
        ]);
    }

    public function analyze(Request $request, Student $student, OpenAiRiskAnalyzer $analyzer): JsonResponse
    {
        if ($response = $this->ensureFacultyOrAdmin($request)) {
            return $response;
        }

        if (! $this->accessibleStudents($request)->whereKey($student->id)->exists()) {
            return response()->json(['status' => false, 'message' => 'This student is not assigned to your courses.'], 403);
        }

        $student->load([
            'user:id,name,email,department,student_id,updated_at',
            'attendanceRecords.course:id,course_code,title',
            'academicRecords.course:id,course_code,title',
            'performanceMetrics',
        ]);
        $record = $this->formatStudent($student);

        try {
            $analysis = $analyzer->analyze([
                'student_reference' => $record['id'],
                'department' => $record['department'],
                'attendance_percentage' => $record['attendance'],
                'missed_classes' => $record['missed'],
                'cgpa' => $record['cgpa'],
                'courses' => $record['courses'],
                'course_scores' => $record['course_scores'],
                'baseline_risk_score' => $record['baseline_risk_score'],
                'baseline_reasons' => $record['baseline_reasons'],
            ]);
        } catch (RuntimeException $exception) {
            $status = str_contains($exception->getMessage(), 'OPENAI_API_KEY') ? 503 : 502;
            return response()->json(['status' => false, 'message' => $exception->getMessage()], $status);
        }

        $alert = RiskAlert::create([
            'student_id' => $student->id,
            'faculty_user_id' => $request->user()->id,
            'risk_level' => $analysis['risk_level'],
            'risk_score' => $analysis['risk_score'],
            'prediction' => $analysis['prediction'],
            'advice' => $analysis['advice'],
            'reasons' => $analysis['reasons'],
            'source' => 'openai',
            'model' => $analysis['model'],
            'analyzed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'AI risk analysis completed successfully.',
            'data' => [
                'id' => $alert->id,
                'risk_score' => $alert->risk_score,
                'risk_level' => $alert->risk_level,
                'prediction' => $alert->prediction,
                'reasons' => $alert->reasons,
                'advice' => $alert->advice,
                'source' => $alert->source,
                'model' => $alert->model,
                'analyzed_at' => $alert->analyzed_at?->toIso8601String(),
            ],
        ]);
    }

    private function ensureFacultyOrAdmin(Request $request): ?JsonResponse
    {
        if (! in_array($request->user()?->role, ['faculty', 'admin'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Only faculty members and administrators can access student monitoring.',
            ], 403);
        }
        return null;
    }

    private function accessibleStudents(Request $request): Builder
    {
        $query = Student::query();
        $user = $request->user();

        if ($user->role === 'admin') {
            return $query;
        }

        $faculty = Faculty::with('courses:id,faculty_id')->where('user_id', $user->id)->first();
        $courseIds = $faculty?->courses->pluck('id')->all() ?? [];

        if ($courseIds !== []) {
            return $query->where(function (Builder $students) use ($courseIds) {
                $students
                    ->whereHas('academicRecords', fn (Builder $records) => $records->whereIn('course_id', $courseIds))
                    ->orWhereHas('attendanceRecords', fn (Builder $records) => $records->whereIn('course_id', $courseIds));
            });
        }

        return $query->whereHas('user', fn (Builder $users) => $users->where('department', $user->department));
    }

    private function formatStudent(Student $student): array
    {
        $attendanceTotal = $student->attendanceRecords->count();
        $present = $student->attendanceRecords->whereIn('status', ['present', 'late'])->count();
        $attendance = $attendanceTotal ? (int) round($present * 100 / $attendanceTotal) : 0;
        $missed = $student->attendanceRecords->where('status', 'absent')->count();
        $metric = $student->performanceMetrics->sortByDesc('created_at')->first();
        $cgpa = round((float) ($metric?->cgpa ?? 0), 2);
        $courseScores = $student->academicRecords
            ->groupBy(fn ($record) => $record->course?->course_code ?: 'Unknown')
            ->map(fn ($records) => (int) round($records->avg(fn ($record) => $this->gradeScore($record->grade))))
            ->all();
        $baseline = $this->baselineRisk($attendance, $cgpa, $missed, $attendanceTotal);
        $alert = $student->relationLoaded('riskAlerts') ? $student->riskAlerts->first() : null;
        $level = $alert?->risk_level ?? $baseline['level'];
        $reasons = $alert?->reasons ?: $baseline['reasons'];

        return [
            'database_id' => $student->id,
            'id' => $student->student_number ?: $student->user?->student_id ?: (string) $student->id,
            'name' => $student->user?->name ?: 'Unknown student',
            'email' => $student->user?->email,
            'department' => $student->department ?: $student->user?->department,
            'program' => $student->program,
            'attendance' => $attendance,
            'cgpa' => $cgpa,
            'missed' => $missed,
            'last_active' => $student->user?->updated_at?->diffForHumans(),
            'courses' => $student->academicRecords->pluck('course.course_code')
                ->merge($student->attendanceRecords->pluck('course.course_code'))
                ->filter()->unique()->values()->all(),
            'course_scores' => $courseScores,
            'status' => $level === 'high' ? 'Critical' : ($level === 'medium' ? 'Risk' : 'Good'),
            'priority' => $level,
            'risk_level' => ucfirst($level).' Risk',
            'risk_score' => $alert?->risk_score ?? $baseline['score'],
            'risk_reason' => implode('; ', $reasons),
            'risk_reasons' => $reasons,
            'advice' => $alert?->advice,
            'prediction' => $alert?->prediction,
            'analysis_source' => $alert?->source ?? 'baseline',
            'analysis_model' => $alert?->model,
            'analyzed_at' => $alert?->analyzed_at?->toIso8601String(),
            'baseline_risk_score' => $baseline['score'],
            'baseline_reasons' => $baseline['reasons'],
        ];
    }

    private function baselineRisk(int $attendance, float $cgpa, int $missed, int $total): array
    {
        $score = 0;
        $reasons = [];

        if ($total === 0) {
            $score += 20; $reasons[] = 'No attendance records are available yet';
        } elseif ($attendance < 60) {
            $score += 45; $reasons[] = 'Attendance is critically below 60%';
        } elseif ($attendance < 75) {
            $score += 28; $reasons[] = 'Attendance is below the recommended 75%';
        }

        if ($cgpa === 0.0) {
            $score += 15; $reasons[] = 'No CGPA record is available yet';
        } elseif ($cgpa < 2.5) {
            $score += 40; $reasons[] = 'CGPA is below 2.50';
        } elseif ($cgpa < 3.0) {
            $score += 22; $reasons[] = 'CGPA is below 3.00';
        }

        if ($missed >= 10) {
            $score += 20; $reasons[] = 'Ten or more classes were missed';
        } elseif ($missed >= 5) {
            $score += 10; $reasons[] = 'Five or more classes were missed';
        }

        $score = min(100, $score);
        $level = $score >= 65 ? 'high' : ($score >= 35 ? 'medium' : 'low');
        if ($reasons === []) $reasons[] = 'Academic indicators are currently within the expected range';

        return compact('score', 'level', 'reasons');
    }

    private function chartData($students): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->subMonths($offset)->format('M'));
        $trend = $months->map(function (string $month) use ($students) {
            $grades = $students->flatMap->academicRecords
                ->filter(fn ($record) => Carbon::parse($record->created_at)->format('M') === $month);
            $attendance = $students->flatMap->attendanceRecords
                ->filter(fn ($record) => Carbon::parse($record->attendance_date ?? $record->created_at)->format('M') === $month);
            return [
                'label' => $month,
                'score' => $grades->isEmpty() ? 0 : (int) round($grades->avg(fn ($record) => $this->gradeScore($record->grade))),
                'attendance' => $attendance->isEmpty() ? 0 : (int) round($attendance->whereIn('status', ['present', 'late'])->count() * 100 / $attendance->count()),
            ];
        });

        $distribution = $students->flatMap->academicRecords
            ->filter(fn ($record) => $record->course)
            ->groupBy(fn ($record) => $record->course->course_code)
            ->map(function ($records, string $code) {
                $scores = $records->map(fn ($record) => $this->gradeScore($record->grade));
                return ['label' => $code, 'average' => (int) round($scores->avg()), 'highest' => (int) $scores->max()];
            })->values()->take(8);

        return ['performance_trend' => $trend->values(), 'score_distribution' => $distribution];
    }

    private function gradeScore(?string $grade): int
    {
        if (is_numeric($grade)) return max(0, min(100, (int) round((float) $grade)));
        return [
            'A+' => 95, 'A' => 88, 'A-' => 82, 'B+' => 78, 'B' => 73,
            'B-' => 68, 'C+' => 63, 'C' => 58, 'D' => 50, 'F' => 35,
        ][strtoupper((string) $grade)] ?? 0;
    }
}
