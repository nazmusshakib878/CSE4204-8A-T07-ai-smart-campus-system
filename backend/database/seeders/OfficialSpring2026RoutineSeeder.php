<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSchedule;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Imports only complete rows from Spring_2026_A_Sections_Official_Routine.csv. */
class OfficialSpring2026RoutineSeeder extends Seeder
{
    private const TERM = 'Spring';
    private const YEAR = 2026;
    private const SOURCE = 'Spring_2026_A_Sections_Official_Routine.csv';

    public function run(): void
    {
        $rows = $this->readAndValidate();
        $approved = array_values(array_filter($rows, fn (array $row): bool => $row['source_review_required'] === 'NO'));
        $flagged = array_values(array_filter($rows, fn (array $row): bool => $row['source_review_required'] === 'YES'));

        if (count($flagged) !== 13 || collect($flagged)->contains(fn (array $row): bool => $row['end_time'] !== '')) {
            throw new \RuntimeException('Official routine import stopped: the 13 source-review rows must remain unresolved and unimported.');
        }

        DB::transaction(function () use ($approved): void {
            $courses = $this->upsertCourses($approved);
            $courseIds = $courses->pluck('id')->all();

            // Supersede only the earlier generated Spring 2026 demo routine, never results, attendance, or accounts.
            CourseSchedule::query()->where('semester', self::TERM)->where('year', self::YEAR)
                ->whereIn('section', ['1A', '2A', '3A', '4A', '5A', '6A', '7A', '8A'])->delete();
            CourseSchedule::query()->where('semester', self::TERM)->where('year', self::YEAR)
                ->where('section', 'SPR26-A')->delete();

            foreach ($approved as $row) {
                $course = $courses[$this->normaliseCode($row['course_code'])];
                CourseSchedule::create([
                    'course_id' => $course->id,
                    'semester' => self::TERM,
                    'year' => self::YEAR,
                    'section' => $row['section'],
                    'shift' => $row['shift'],
                    'day_of_week' => $this->dayNumber($row['day']),
                    'starts_at' => $row['start_time'],
                    'ends_at' => $row['end_time'],
                    'room' => $row['room'],
                    'instructor_initials' => $row['teacher_initials'],
                    'class_type' => Str::contains(Str::upper($row['room']), 'LAB') ? 'lab' : 'lecture',
                    'source_reference' => self::SOURCE . ' page ' . $row['source_pdf_page'],
                ]);
            }

            foreach (range(1, 8) as $semester) {
                $section = $semester . 'A';
                $students = Student::query()->where('student_number', 'like', "CSE-SPR26-S{$semester}-%")->get();
                if ($students->count() !== 5) {
                    throw new \RuntimeException("Expected five Spring 2026 demo students for semester {$semester}.");
                }
                $semesterCourses = collect($approved)->where('semester', (string) $semester)
                    ->map(fn (array $row) => $courses[$this->normaliseCode($row['course_code'])])->unique('id');
                foreach ($students as $student) {
                    $student->update(['section' => $section]);
                    foreach ($semesterCourses as $course) {
                        CourseEnrollment::updateOrCreate([
                            'student_id' => $student->id, 'course_id' => $course->id,
                            'semester' => self::TERM, 'year' => self::YEAR,
                        ]);
                    }
                }
            }
        });

        $this->command?->info(sprintf(
            'Official Spring 2026 routine imported: %d verified rows; %d flagged source rows excluded pending correction.',
            count($approved), count($flagged),
        ));
    }

    private function readAndValidate(): array
    {
        $path = base_path('../' . self::SOURCE);
        if (! is_file($path) || ($handle = fopen($path, 'r')) === false) {
            throw new \RuntimeException('Official routine CSV is required at project root: ' . self::SOURCE);
        }
        $header = fgetcsv($handle);
        if (is_array($header) && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]) ?? $header[0];
        }
        $expected = ['term', 'effective_date', 'semester', 'section', 'day', 'shift', 'start_time', 'end_time', 'pdf_time_slot', 'room', 'course_code', 'teacher_initials', 'source_pdf_page', 'source_review_required'];
        if ($header !== $expected) {
            throw new \RuntimeException('Official routine CSV header does not match the approved schema.');
        }
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) !== count($expected)) throw new \RuntimeException('Malformed official routine CSV row.');
            $row = array_combine($expected, $values);
            if ($row['term'] !== 'Spring 2026' || $row['effective_date'] !== '2026-08-01'
                || !preg_match('/^[1-8]$/', $row['semester']) || $row['section'] !== $row['semester'].'A'
                || !in_array($row['day'], ['Sat', 'Sun', 'Tue', 'Wed', 'Thu'], true)
                || !in_array($row['shift'], ['1st', '2nd'], true) || !preg_match('/^\d{2}:\d{2}$/', $row['start_time'])
                || !in_array($row['source_review_required'], ['YES', 'NO'], true)) {
                throw new \RuntimeException('Official routine CSV contains an invalid source row.');
            }
            if ($row['source_review_required'] === 'NO' && (!preg_match('/^\d{2}:\d{2}$/', $row['end_time']) || $row['end_time'] <= $row['start_time'])) {
                throw new \RuntimeException('A non-flagged routine row has an invalid end time.');
            }
            $rows[] = $row;
        }
        fclose($handle);
        if (count($rows) !== 109) throw new \RuntimeException('Official routine CSV row count changed; import stopped.');
        return $rows;
    }

    private function upsertCourses(array $rows)
    {
        $existing = Course::query()->get()->keyBy(fn (Course $course) => $this->normaliseCode($course->course_code));
        foreach (collect($rows)->unique('course_code') as $row) {
            $key = $this->normaliseCode($row['course_code']);
            $course = $existing->get($key) ?? new Course(['course_code' => preg_replace('/\s+/', '-', $row['course_code'])]);
            if (! $course->exists) {
                // The authoritative CSV supplies course codes, not course titles or faculty identities.
                $course->fill(['title' => $row['course_code'], 'department' => 'Computer Science & Engineering', 'credit_hours' => 3, 'description' => 'Official Spring 2026 routine course code; title pending catalog verification.', 'is_active' => true])->save();
            }
            $existing->put($key, $course);
        }
        return $existing;
    }

    private function normaliseCode(string $code): string { return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? ''); }
    private function dayNumber(string $day): int { return ['Sun' => 0, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Sat' => 6][$day]; }
}
