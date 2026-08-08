<?php

namespace Tests\Feature;

use App\Models\Course;
use Database\Seeders\CseCourseCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CseCourseCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeding_is_idempotent_and_preserves_existing_course_codes(): void
    {
        Course::create([
            'course_code' => 'CSE 4103',
            'title' => 'Legacy AI Title',
            'department' => 'Computer Science & Engineering',
            'credit_hours' => 3,
            'description' => null,
            'is_active' => false,
        ]);

        $this->seed(CseCourseCatalogSeeder::class);
        $this->seed(CseCourseCatalogSeeder::class);

        $normalizedCodes = Course::query()
            ->get()
            ->map(fn (Course $course) => preg_replace('/[^A-Za-z0-9]/', '', strtoupper($course->course_code)));

        $this->assertCount(29, $normalizedCodes);
        $this->assertCount(29, $normalizedCodes->unique());
        $this->assertDatabaseHas('courses', [
            'course_code' => 'CSE 4103',
            'title' => 'Artificial Intelligence and Expert System',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('courses', ['course_code' => 'CSE-4103']);
        $this->assertDatabaseHas('courses', [
            'course_code' => 'CSE-4219',
            'title' => 'Cryptography',
            'department' => 'Computer Science & Engineering',
            'credit_hours' => 3,
            'is_active' => true,
        ]);
    }

    public function test_catalog_courses_are_available_to_academic_management_and_recommendations(): void
    {
        $this->seed(CseCourseCatalogSeeder::class);

        $this->assertSame(
            29,
            Course::query()
                ->where('department', 'Computer Science & Engineering')
                ->where('is_active', true)
                ->count()
        );

        $this->assertDatabaseHas('courses', [
            'course_code' => 'CSE-3206',
            'title' => 'Software Development II: Web Development',
            'credit_hours' => 1.5,
        ]);
        $this->assertDatabaseHas('courses', [
            'course_code' => 'CSE-4205',
            'title' => 'Machine Learning',
            'description' => 'NUBTK BSc in CSE curriculum course. Recommended for Year 4, Semester 8.',
        ]);
    }
}
