<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_request_a_validated_ai_response(): void
    {
        $student = $this->student();
        config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => 'gemini-3.6-flash']);
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Your attendance is currently available in your dashboard.'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($student, 'sanctum')->postJson('/api/ai/assistant', ['question' => ' How is my progress? '])
            ->assertOk()->assertJsonPath('status', true)
            ->assertJsonPath('data.answer', 'Your attendance is currently available in your dashboard.')
            ->assertJsonPath('data.model', 'gemini-3.6-flash');

        Http::assertSent(fn ($request) => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent'
            && $request->hasHeader('x-goog-api-key', 'test-key')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'How is my progress?')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'BSc in CSE')
            && ! str_contains($request['contents'][0]['parts'][0]['text'], 'password'));
    }

    public function test_empty_question_is_rejected(): void
    {
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => '   '])
            ->assertUnprocessable()->assertJsonValidationErrors('question');
    }

    public function test_faculty_cannot_use_student_assistant(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $this->actingAs($faculty, 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Help'])
            ->assertForbidden()->assertJsonPath('status', false);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/ai/assistant', ['question' => 'Help'])->assertUnauthorized();
    }

    public function test_gemini_api_failure_is_safe(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent' => Http::response(['error' => ['message' => 'private upstream detail']], 500)]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Help'])
            ->assertOk()->assertJsonPath('data.model', 'campus-data-fallback')->assertJsonPath('data.fallback', true);
    }

    public function test_gemini_rate_limit_is_safe(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent' => Http::response(['error' => ['message' => 'rate limited']], 429)]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Help'])
            ->assertOk()->assertJsonPath('data.model', 'campus-data-fallback')->assertJsonPath('data.fallback', true);
    }

    public function test_invalid_gemini_response_is_safe(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent' => Http::response(['candidates' => []])]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Help'])
            ->assertOk()->assertJsonPath('data.model', 'campus-data-fallback')->assertJsonPath('data.fallback', true);
    }

    private function student(): User
    {
        $user = User::factory()->create(['role' => 'student', 'department' => 'CSE']);
        Student::create(['user_id' => $user->id, 'student_number' => 'CSE-001', 'department' => 'CSE', 'program' => 'BSc in CSE', 'current_semester' => 6]);
        return $user;
    }
}
