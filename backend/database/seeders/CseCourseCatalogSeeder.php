<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CseCourseCatalogSeeder extends Seeder
{
    private const DEPARTMENT = 'Computer Science & Engineering';

    public function run(): void
    {
        $facultyId = Faculty::where('department', self::DEPARTMENT)
            ->orderBy('id')
            ->value('id');

        $existing = Course::query()->get()->keyBy(
            fn (Course $course) => $this->normalizeCode((string) $course->course_code)
        );

        foreach ($this->catalog() as $item) {
            $normalizedCode = $this->normalizeCode($item['course_code']);
            $course = $existing->get($normalizedCode);

            if (! $course) {
                $course = new Course(['course_code' => $item['course_code']]);
            }

            $course->fill([
                'title' => $item['title'],
                'department' => self::DEPARTMENT,
                'credit_hours' => $item['credit_hours'],
                'description' => sprintf(
                    'NUBTK BSc in CSE curriculum course. Recommended for Year %d, Semester %d.',
                    $item['year_level'],
                    $item['semester_level'],
                ),
                'is_active' => true,
            ]);

            if (! $course->faculty_id && $facultyId) {
                $course->faculty_id = $facultyId;
            }

            $course->save();
            $existing->put($normalizedCode, $course);
        }

        $this->command?->info('NUBTK CSE course catalog seeded without duplicate course codes.');
    }

    private function normalizeCode(string $code): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    private function catalog(): array
    {
        return [
            $this->course('CSE-1101', 'Introduction to Computers', 1, 1),
            $this->course('CSE-1103', 'Structured Programming Language', 1, 1),
            $this->course('CSE-1201', 'Object-Oriented Programming I (C++)', 1, 2),
            $this->course('CSE-2101', 'Object-Oriented Programming II (JAVA)', 2, 3),
            $this->course('CSE-2103', 'Data Structure', 2, 3),
            $this->course('CSE-2105', 'Discrete Mathematics', 2, 3),
            $this->course('CSE-2201', 'Algorithm Design and Analysis', 2, 4),
            $this->course('CSE-2206', 'Software Development I', 2, 4, 1.5),
            $this->course('CSE-2207', 'Computer Architecture', 2, 4),
            $this->course('CSE-2209', 'Information System and Design', 2, 4),
            $this->course('CSE-3101', 'Database Management System', 3, 5),
            $this->course('CSE-3106', 'Software Engineering', 3, 5),
            $this->course('CSE-3107', 'Operating System', 3, 5),
            $this->course('CSE-3109', 'Data Communication', 3, 5),
            $this->course('CSE-3201', 'Computer Networks', 3, 6),
            $this->course('CSE-3203', 'Digital Image Processing', 3, 6),
            $this->course('CSE-3206', 'Software Development II: Web Development', 3, 6, 1.5),
            $this->course('CSE-4103', 'Artificial Intelligence and Expert System', 4, 7),
            $this->course('CSE-4106', 'Software Development III', 4, 7, 1.5),
            $this->course('CSE-4107', 'Compiler Design', 4, 7),
            $this->course('CSE-4109', 'Computer Graphics and Multimedia System', 4, 7),
            $this->course('CSE-4111', 'Introduction to Data Science', 4, 7),
            $this->course('CSE-4203', 'Information Security and Control', 4, 8),
            $this->course('CSE-4205', 'Machine Learning', 4, 8),
            $this->course('CSE-4207', 'Neural Network', 4, 8),
            $this->course('CSE-4209', 'Pattern Recognition', 4, 8),
            $this->course('CSE-4213', 'Client Service Technology', 4, 8),
            $this->course('CSE-4217', 'Embedded System', 4, 8),
            $this->course('CSE-4219', 'Cryptography', 4, 8),
        ];
    }

    private function course(
        string $courseCode,
        string $title,
        int $yearLevel,
        int $semesterLevel,
        float $creditHours = 3.0,
    ): array {
        return [
            'course_code' => $courseCode,
            'title' => $title,
            'year_level' => $yearLevel,
            'semester_level' => $semesterLevel,
            'credit_hours' => $creditHours,
        ];
    }
}
