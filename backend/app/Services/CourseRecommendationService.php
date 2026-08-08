<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class CourseRecommendationService
{
    public function __construct(private readonly GeminiCourseRecommender $ai)
    {
    }

    public function forStudent(User $user): array
    {
        $student = $user->studentProfile;
        if (! $student) {
            return $this->result([], 'rule_based', null, 'Student profile is unavailable.');
        }

        $department = $this->resolveDepartment($student, $user);
        $enrolledIds = $student->courses()->pluck('courses.id')->all();

        $candidates = Course::query()
            ->with('faculty.user:id,name')
            ->where('is_active', true)
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('course_code')
            ->get()
            ->filter(fn (Course $course) => $this->sameDepartment($course->department, $department))
            ->map(fn (Course $course) => $this->rankedCourse($course, $student))
            ->sortBy([
                ['score', 'desc'],
                ['course_code', 'asc'],
            ])
            ->take(6)
            ->values();

        if ($candidates->isEmpty()) {
            return $this->result(
                [],
                'rule_based',
                $department,
                'No active courses are available for your department and enrollment status.'
            );
        }

        try {
            $personalized = $this->ai->recommend([
                'department' => $department,
                'program' => $student->program,
                'current_semester' => $student->current_semester,
                'cgpa' => $student->performanceMetrics()->latest()->value('cgpa'),
            ], $candidates->all());

            return $this->result(
                $this->mergeAiRecommendations($candidates, $personalized),
                'ai',
                $department,
                null,
                $personalized['model'] ?? null,
            );
        } catch (Throwable $exception) {
            Log::info('AI course recommendations fell back to deterministic ranking', [
                'user_id' => $user->id,
                'reason' => $exception->getMessage(),
            ]);

            return $this->result($candidates, 'rule_based', $department);
        }
    }

    private function resolveDepartment(Student $student, User $user): ?string
    {
        $profileDepartment = trim((string) ($student->department ?: $user->department));
        $inferredDepartment = $this->departmentFromStudentNumber((string) $student->student_number);

        if ($profileDepartment !== '' && $this->hasCoursesFor($profileDepartment)) {
            if ($inferredDepartment && ! $this->sameDepartment($profileDepartment, $inferredDepartment)) {
                return $this->hasCoursesFor($inferredDepartment) ? $inferredDepartment : $profileDepartment;
            }

            return $profileDepartment;
        }

        return $inferredDepartment ?: ($profileDepartment !== '' ? $profileDepartment : null);
    }

    private function departmentFromStudentNumber(string $studentNumber): ?string
    {
        if (! preg_match('/^([A-Za-z]{2,5})/', trim($studentNumber), $matches)) {
            return null;
        }

        return match (strtoupper($matches[1])) {
            'CSE' => 'Computer Science & Engineering',
            'EEE' => 'Electrical & Electronic Engineering',
            'CE', 'CIVIL' => 'Civil Engineering',
            'BBA' => 'Business Administration',
            default => null,
        };
    }

    private function hasCoursesFor(string $department): bool
    {
        return Course::query()
            ->where('is_active', true)
            ->get(['department'])
            ->contains(fn (Course $course) => $this->sameDepartment($course->department, $department));
    }

    private function sameDepartment(?string $left, ?string $right): bool
    {
        if (! $left || ! $right) {
            return false;
        }

        return $this->departmentKey($left) === $this->departmentKey($right);
    }

    private function departmentKey(string $department): string
    {
        $key = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $department) ?? '');

        return match ($key) {
            'cse', 'computerscienceengineering', 'computerscienceandengineering' => 'cse',
            'eee', 'electricalelectronicengineering', 'electricalandelectronicengineering' => 'eee',
            'ce', 'civil', 'civilengineering' => 'civil',
            'bba', 'businessadministration' => 'bba',
            default => $key,
        };
    }

    private function rankedCourse(Course $course, Student $student): array
    {
        $recommendedSemester = $this->semesterFromCode((string) $course->course_code);
        $currentSemester = max(1, min(8, (int) ($student->current_semester ?: 1)));
        $distance = $recommendedSemester ? abs($recommendedSemester - $currentSemester) : 3;
        $score = max(65, 95 - ($distance * 7));

        return [
            'course_id' => $course->id,
            'course_code' => $course->course_code,
            'title' => $course->title,
            'description' => $recommendedSemester
                ? sprintf('Recommended for Semester %d and matched to your CSE study progression.', $recommendedSemester)
                : 'Recommended because it matches your department and is not already enrolled.',
            'score' => $score,
            'recommended_semester' => $recommendedSemester,
            'credit_hours' => (float) $course->credit_hours,
            'faculty' => $course->faculty?->user?->name,
        ];
    }

    private function semesterFromCode(string $courseCode): ?int
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $courseCode) ?? '');
        if (! preg_match('/^[A-Z]+([1-4])([12])\d{2}$/', $normalized, $matches)) {
            return null;
        }

        return (((int) $matches[1] - 1) * 2) + (int) $matches[2];
    }

    private function mergeAiRecommendations(Collection $candidates, array $aiResult): Collection
    {
        $byId = $candidates->keyBy('course_id');
        $merged = collect($aiResult['recommendations'] ?? [])
            ->map(function (array $item) use ($byId) {
                $course = $byId->get((int) ($item['course_id'] ?? 0));
                if (! $course) {
                    return null;
                }

                $course['score'] = max(0, min(100, (int) ($item['score'] ?? $course['score'])));
                $reason = trim((string) ($item['reason'] ?? ''));
                if ($reason !== '') {
                    $course['description'] = $reason;
                }

                return $course;
            })
            ->filter()
            ->unique('course_id')
            ->values();

        if ($merged->isEmpty()) {
            throw new \RuntimeException('AI recommendation payload did not contain valid candidate courses.');
        }

        return $merged;
    }

    private function result(
        Collection|array $courses,
        string $source,
        ?string $department,
        ?string $message = null,
        ?string $model = null,
    ): array {
        $items = collect($courses)->values()->map(fn (array $course) => [
            'id' => $source.'-'.$course['course_id'],
            'course_id' => $course['course_id'],
            'title' => $course['course_code'].' | '.$course['title'],
            'description' => $course['description'],
            'recommendation_type' => $source === 'ai' ? 'AI Personalized' : 'Smart Match',
            'score' => $course['score'],
            'source' => $source,
            'recommended_semester' => $course['recommended_semester'],
            'course' => [
                'course_code' => $course['course_code'],
                'title' => $course['title'],
                'credit_hours' => $course['credit_hours'],
                'faculty' => $course['faculty'],
            ],
        ]);

        return [
            'source' => $source,
            'model' => $model,
            'department' => $department,
            'message' => $message,
            'data' => $items,
        ];
    }
}
