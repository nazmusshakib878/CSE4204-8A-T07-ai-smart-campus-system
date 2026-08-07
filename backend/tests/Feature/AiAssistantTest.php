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
        config(['services.openai.api_key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);
        Http::fake(['api.openai.com/v1/responses' => Http::response(['output_text' => 'Your attendance is currently available in your dashboard.'])]);

        $this->actingAs($student, 'sanctum')->postJson('/api/ai/assistant', ['question' => ' How is my progress? '])
            ->assertOk()->assertJsonPath('status', true)
            ->assertJsonPath('data.answer', 'Your attendance is currently available in your dashboard.')
            ->assertJsonPath('data.model', 'gpt-4.1-mini');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/responses'
            && str_contains($request['input'], 'How is my progress?')
            && str_contains($request['input'], 'BSc in CSE')
            && ! str_contains($request['input'], 'password'));
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

    public function test_openai_failure_is_safe(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'private upstream detail']], 429)]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Help'])
            ->assertStatus(503)->assertJsonPath('message', 'AI Assistant is temporarily unavailable. Please try again.');
    }

    public function test_invalid_openai_response_is_safe(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['api.openai.com/v1/responses' => Http::response(['output' => []])]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Help'])
            ->assertStatus(503)->assertJsonPath('message', 'AI Assistant is temporarily unavailable. Please try again.');
    }

    private function student(): User
    {
        $user = User::factory()->create(['role' => 'student', 'department' => 'CSE']);
        Student::create(['user_id' => $user->id, 'student_number' => 'CSE-001', 'department' => 'CSE', 'program' => 'BSc in CSE', 'current_semester' => 6]);
        return $user;
    }
}
