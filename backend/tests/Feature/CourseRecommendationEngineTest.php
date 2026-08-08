<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseRecommendationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_cse_student_number_recovers_from_an_inconsistent_profile_department(): void
    {
        config(['services.gemini.api_key' => null]);

        $user = User::factory()->create(['role' => 'student', 'department' => 'Civil Engineering']);
        Student::create([
            'user_id' => $user->id,
            'student_number' => 'CSE1122032069',
            'department' => 'Civil Engineering',
            'current_semester' => 1,
        ]);
        $course = Course::create([
            'course_code' => 'CSE-1101',
            'title' => 'Introduction to Computers',
            'department' => 'Computer Science & Engineering',
            'credit_hours' => 3,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/recommendations')
            ->assertOk()
            ->assertJsonPath('source', 'rule_based')
            ->assertJsonPath('department', 'Computer Science & Engineering')
            ->assertJsonPath('data.0.course_id', $course->id);
    }

    public function test_rule_based_fallback_prioritizes_current_semester_and_excludes_enrolled_courses(): void
    {
        config(['services.gemini.api_key' => null]);

        $user = User::factory()->create([
            'role' => 'student',
            'department' => 'Computer Science & Engineering',
        ]);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => 'CSE11220320852',
            'department' => $user->department,
            'current_semester' => 1,
        ]);
        $current = Course::create([
            'course_code' => 'CSE-1101',
            'title' => 'Introduction to Computers',
            'department' => $user->department,
            'credit_hours' => 3,
            'is_active' => true,
        ]);
        $enrolled = Course::create([
            'course_code' => 'CSE-1103',
            'title' => 'Structured Programming Language',
            'department' => $user->department,
            'credit_hours' => 3,
            'is_active' => true,
        ]);
        Course::create([
            'course_code' => 'CSE-4205',
            'title' => 'Machine Learning',
            'department' => $user->department,
            'credit_hours' => 3,
            'is_active' => true,
        ]);
        $student->courses()->attach($enrolled->id, ['semester' => 'Spring', 'year' => 2026]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/recommendations')
            ->assertOk()
            ->assertJsonPath('data.0.course_id', $current->id)
            ->assertJsonPath('data.0.recommended_semester', 1);

        $this->assertNotContains($enrolled->id, collect($response->json('data'))->pluck('course_id'));
    }

    public function test_gemini_can_personalize_only_valid_backend_candidates(): void
    {
        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-test',
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'department' => 'Computer Science & Engineering',
        ]);
        Student::create([
            'user_id' => $user->id,
            'student_number' => 'CSE11220320852',
            'department' => $user->department,
            'current_semester' => 8,
        ]);
        $course = Course::create([
            'course_code' => 'CSE-4205',
            'title' => 'Machine Learning',
            'department' => $user->department,
            'credit_hours' => 3,
            'is_active' => true,
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'recommendations' => [[
                                    'course_id' => $course->id,
                                    'score' => 97,
                                    'reason' => 'Strong match for your Semester 8 CSE progression.',
                                ]],
                            ]),
                        ]],
                    ],
                ]],
            ]),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/recommendations')
            ->assertOk()
            ->assertJsonPath('source', 'ai')
            ->assertJsonPath('model', 'gemini-test')
            ->assertJsonPath('data.0.course_id', $course->id)
            ->assertJsonPath('data.0.score', 97)
            ->assertJsonPath('data.0.description', 'Strong match for your Semester 8 CSE progression.');

        Http::assertSentCount(1);
    }

    public function test_invalid_ai_response_falls_back_without_emptying_recommendations(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake(['*' => Http::response(['candidates' => []])]);

        $user = User::factory()->create([
            'role' => 'student',
            'department' => 'Computer Science & Engineering',
        ]);
        Student::create([
            'user_id' => $user->id,
            'student_number' => 'CSE11220320852',
            'department' => $user->department,
            'current_semester' => 1,
        ]);
        $course = Course::create([
            'course_code' => 'CSE-1101',
            'title' => 'Introduction to Computers',
            'department' => $user->department,
            'credit_hours' => 3,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/recommendations')
            ->assertOk()
            ->assertJsonPath('source', 'rule_based')
            ->assertJsonPath('data.0.course_id', $course->id);
    }
}
