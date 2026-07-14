<?php

namespace App\Services;

use App\Models\AcademicRecord;
use App\Models\PerformanceMetric;
use App\Models\Student;

class AcademicResultService
{
    public function gradeFromMarks(float $marks): array
    {
        return match (true) {
            $marks >= 80 => ['A+', 4.00], $marks >= 75 => ['A', 3.75],
            $marks >= 70 => ['A-', 3.50], $marks >= 65 => ['B+', 3.25],
            $marks >= 60 => ['B', 3.00], $marks >= 55 => ['B-', 2.75],
            $marks >= 50 => ['C+', 2.50], $marks >= 45 => ['C', 2.25],
            $marks >= 40 => ['D', 2.00], default => ['F', 0.00],
        };
    }

    public function recalculate(Student $student, string $semester, int $year): PerformanceMetric
    {
        $records = AcademicRecord::with('course:id,credit_hours')->where('student_id', $student->id)->get();
        $points = $records->sum(fn ($record) => $this->pointForGrade($record->grade) * (float) ($record->course?->credit_hours ?? 0));
        $attempted = $records->sum(fn ($record) => (float) ($record->course?->credit_hours ?? 0));
        $completed = $records->reject(fn ($record) => $record->grade === 'F')->sum(fn ($record) => (float) ($record->course?->credit_hours ?? 0));

        return PerformanceMetric::updateOrCreate(
            ['student_id' => $student->id, 'semester' => $semester, 'year' => $year],
            ['cgpa' => $attempted > 0 ? round($points / $attempted, 2) : 0, 'completed_credits' => (int) $completed]
        );
    }

    private function pointForGrade(?string $grade): float
    {
        return ['A+' => 4, 'A' => 3.75, 'A-' => 3.5, 'B+' => 3.25, 'B' => 3, 'B-' => 2.75, 'C+' => 2.5, 'C' => 2.25, 'D' => 2, 'F' => 0][strtoupper((string) $grade)] ?? 0;
    }
}
