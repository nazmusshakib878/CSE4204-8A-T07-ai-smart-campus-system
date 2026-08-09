<?php

namespace Tests\Feature;
use App\Exceptions\GeminiServiceException;

use App\Models\AiConversation;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use App\Services\GeminiCampusAssistant;
use Illuminate\Log\Logger as IlluminateLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    private const QUOTA_METRIC = 'generativelanguage.googleapis.com/generate_content_free_tier_requests';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => 'gemini-3.5-flash-lite']);
        Sleep::fake();
    }

    public function test_normal_message_uses_exactly_one_gemini_call_without_campus_context(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('Hi! How can I help?'))]);

        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => ' Hi '])
            ->assertOk()->assertJsonPath('data.answer', 'Hi! How can I help?');

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $payload = $request->data();
            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent'
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && ! str_contains($request->url(), 'test-key')
                && data_get($payload, 'contents.0.parts.0.text') === 'Hi'
                && ! str_contains(json_encode($payload), 'compact, authenticated, read-only')
                && ! array_key_exists('tools', $payload);
        });
    }

    public function test_help_message_uses_exactly_one_gemini_call_without_campus_processing(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('Sure. What do you need help with?'))]);

        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'I need help'])
            ->assertOk()->assertJsonPath('data.answer', 'Sure. What do you need help with?');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => ! str_contains(json_encode($request->data()), 'compact, authenticated, read-only'));
    }

    public function test_courses_are_loaded_locally_and_sent_in_the_same_single_generation_call(): void
    {
        $user = $this->student();
        $course = Course::create(['course_code' => 'CSE4204', 'title' => 'Artificial Intelligence', 'department' => 'CSE']);
        CourseEnrollment::create(['student_id' => $user->studentProfile->id, 'course_id' => $course->id, 'semester' => '6', 'year' => 2026]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('You are taking CSE4204.'))]);

        $this->actingAs($user, 'sanctum')->postJson('/api/ai/assistant', ['question' => 'What courses am I taking?'])
            ->assertOk()->assertJsonPath('data.answer', 'You are taking CSE4204.');

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $system = data_get($request->data(), 'systemInstruction.parts.0.text', '');
            return str_contains($system, 'VERIFIED CAMPUS CONTEXT')
                && str_contains($system, 'CSE4204')
                && str_contains($system, 'Artificial Intelligence')
                && ! str_contains($system, 'attendanceRecords')
                && ! str_contains($system, 'password');
        });
    }

    public function test_attendance_uses_one_call_with_only_compact_verified_attendance(): void
    {
        $user = $this->student();
        $course = Course::create(['course_code' => 'CSE4204', 'title' => 'Artificial Intelligence', 'department' => 'CSE']);
        AttendanceRecord::create(['student_id' => $user->studentProfile->id, 'course_id' => $course->id, 'attendance_date' => now()->toDateString(), 'status' => 'present']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('Your recorded attendance is 100%.'))]);

        $this->actingAs($user, 'sanctum')->postJson('/api/ai/assistant', ['question' => "What's my attendance?"])
            ->assertOk()->assertJsonPath('data.answer', 'Your recorded attendance is 100%.');

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $system = data_get($request->data(), 'systemInstruction.parts.0.text', '');
            return str_contains($system, '"attendance"')
                && str_contains($system, '"percentage":100')
                && ! str_contains($system, '"courses"')
                && ! str_contains($system, '"profile"');
        });
    }

    public function test_recent_history_keeps_roles_and_each_message_still_uses_one_call(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->geminiText('Great. What part of Python are you learning?'))
            ->push($this->geminiText('Functions can feel difficult at first.'))
            ->push($this->geminiText('A function is a named, reusable block of code.'))]);
        $user = $this->student();

        $first = $this->actingAs($user, 'sanctum')->postJson('/api/ai/assistant', ['question' => "I'm learning Python."])->assertOk();
        $second = $this->actingAs($user, 'sanctum')->postJson('/api/ai/assistant', [
            'question' => 'Functions are difficult.',
            'conversation_id' => $first->json('data.conversation_id'),
        ])->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/ai/assistant', [
            'question' => 'Explain it simply.',
            'conversation_id' => $second->json('data.conversation_id'),
        ])->assertOk()->assertJsonPath('data.answer', 'A function is a named, reusable block of code.');

        $requests = Http::recorded();
        $this->assertCount(3, $requests);
        $contents = $requests[2][0]->data()['contents'];
        $this->assertSame(['user', 'model', 'user', 'model', 'user'], array_column($contents, 'role'));
        $this->assertSame("I'm learning Python.", data_get($contents, '0.parts.0.text'));
        $this->assertSame('Functions are difficult.', data_get($contents, '2.parts.0.text'));
        $this->assertSame('Explain it simply.', data_get($contents, '4.parts.0.text'));
    }

    public function test_429_respects_server_retry_delay_and_returns_normal_answer_when_retry_succeeds(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->quotaResponse('GenerateRequestsPerMinutePerProjectPerModel-FreeTier', '0.25s'), 429)
            ->push($this->geminiText('Recovered response.'))]);

        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Hello'])
            ->assertOk()->assertJsonPath('data.answer', 'Recovered response.');

        Http::assertSentCount(2);
        Sleep::assertSlept(fn ($duration) => abs($duration->totalMilliseconds - 250) < 1);
    }

    public function test_daily_quota_is_not_retried_and_returns_rate_limit_message(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            $this->quotaResponse('GenerateRequestsPerDayPerProjectPerModel-FreeTier'),
            429,
        )]);

        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Hello'])
            ->assertStatus(429)
            ->assertExactJson(['status' => false, 'message' => 'AI is receiving too many requests right now. Please wait a moment and try again.']);

        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_temporary_500_uses_exponential_backoff_and_can_recover(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['status' => 'INTERNAL']], 500)
            ->push($this->geminiText('Recovered.'))]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Hello'])->assertOk();
        Http::assertSentCount(2);
        Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds >= 1000 && $duration->totalMilliseconds <= 1250);
    }


    public function test_temporary_failures_stop_after_three_retries(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['status' => 'UNAVAILABLE']], 503)
            ->push(['error' => ['status' => 'UNAVAILABLE']], 503)
            ->push(['error' => ['status' => 'UNAVAILABLE']], 503)
            ->push(['error' => ['status' => 'UNAVAILABLE']], 503)]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Hello'])
            ->assertStatus(503)->assertJsonPath('message', 'AI service is temporarily unavailable. Please try again.');
        Http::assertSentCount(4);
        Sleep::assertSleptTimes(3);
        Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds >= 1000 && $duration->totalMilliseconds <= 1250);
        Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds >= 2000 && $duration->totalMilliseconds <= 2250);
        Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds >= 4000 && $duration->totalMilliseconds <= 4250);
    }

    public function test_permanent_400_is_not_retried(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['status' => 'INVALID_ARGUMENT']], 400)]);

        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'Hello'])
            ->assertStatus(503)->assertJsonPath('message', 'AI service is temporarily unavailable. Please try again.');

        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }


    public function test_successful_message_logs_exactly_one_generation_call_without_secrets(): void
    {
        $handler = new TestHandler;
        Log::swap(new IlluminateLogger(new MonologLogger('test', [$handler]), app('events')));
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('Hello!'))]);

        app(GeminiCampusAssistant::class)->answer('Hi', [], $this->student());

        $records = $handler->getRecords();
        $messages = collect($records)->pluck('message')->all();
        $this->assertContains('AI message processing started', $messages);
        $dispatch = collect($records)->first(fn ($record) => $record->message === 'Gemini generation call dispatched');
        $this->assertNotNull($dispatch);
        $this->assertSame(1, $dispatch->context['gemini_generation_calls']);
        $this->assertStringNotContainsString('test-key', json_encode($records));
    }
    public function test_failure_logs_are_structured_and_never_contain_the_api_key(): void
    {
        $handler = new TestHandler;
        Log::swap(new IlluminateLogger(new MonologLogger('test', [$handler]), app('events')));
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['status' => 'INVALID_ARGUMENT']], 400)]);

        try {
            app(GeminiCampusAssistant::class)->answer('Hello', [], $this->student());
            $this->fail('Expected GeminiServiceException was not thrown.');
        } catch (GeminiServiceException) {
            $this->assertTrue(true);
        }

        $logged = json_encode($handler->getRecords());
        $this->assertStringContainsString('Gemini request failed', $logged);
        $this->assertStringContainsString('gemini-3.5-flash-lite', $logged);
        $this->assertStringContainsString('INVALID_ARGUMENT', $logged);
        $this->assertStringNotContainsString('test-key', $logged);
        $this->assertStringNotContainsString('x-goog-api-key', strtolower($logged));
    }

    public function test_bangla_and_mixed_language_responses_pass_through_unchanged(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('অবশ্যই—কোন topic-এ help দরকার?'))]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'আমার একটু help দরকার'])
            ->assertOk()->assertJsonPath('data.answer', 'অবশ্যই—কোন topic-এ help দরকার?');
        Http::assertSentCount(1);
    }

    public function test_unavailable_policy_context_is_explicit_in_one_call(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('I do not currently have the official policy.'))]);
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/ai/assistant', ['question' => 'What is the official minimum attendance?'])
            ->assertOk()->assertJsonPath('data.answer', 'I do not currently have the official policy.');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains(
            data_get($request->data(), 'systemInstruction.parts.0.text', ''),
            'Official institutional policy data is not available',
        ));
    }

    public function test_missing_faculty_profile_is_linked_to_the_authenticated_user_without_breaking_chat(): void
    {
        $facultyUser = User::factory()->create([
            'role' => 'faculty',
            'email' => 'missing.faculty@example.edu',
            'department' => 'CSE',
            'faculty_id' => null,
        ]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('Hello, Faculty!'))]);

        $this->actingAs($facultyUser, 'sanctum')
            ->postJson('/api/ai/assistant', ['question' => 'Hi'])
            ->assertOk()
            ->assertJsonPath('data.answer', 'Hello, Faculty!');

        $this->assertDatabaseHas('faculty', [
            'user_id' => $facultyUser->id,
            'department' => 'CSE',
        ]);
        Http::assertSent(function ($request) {
            $system = data_get($request->data(), 'systemInstruction.parts.0.text', '');

            return str_contains($system, 'AUTHENTICATED USER ROLE: FACULTY');
        });
    }

    public function test_faculty_uses_the_shared_assistant_with_only_assigned_course_context(): void
    {
        $facultyUser = User::factory()->create(['role' => 'faculty', 'department' => 'CSE']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id, 'department' => 'CSE', 'designation' => 'Lecturer']);
        $otherUser = User::factory()->create(['role' => 'faculty', 'department' => 'CSE']);
        $otherFaculty = Faculty::create(['user_id' => $otherUser->id, 'department' => 'CSE', 'designation' => 'Lecturer']);
        Course::create(['faculty_id' => $faculty->id, 'course_code' => 'CSE4204', 'title' => 'Artificial Intelligence', 'department' => 'CSE']);
        Course::create(['faculty_id' => $otherFaculty->id, 'course_code' => 'CSE4999', 'title' => 'Other Faculty Private Course', 'department' => 'CSE']);
        $studentConversation = AiConversation::create(['user_id' => $this->student()->id, 'last_message_at' => now()]);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('You teach CSE4204.'))]);

        $this->actingAs($facultyUser->load('facultyProfile'), 'sanctum')
            ->postJson('/api/ai/assistant', ['question' => 'What courses do I teach?'])
            ->assertOk()
            ->assertJsonPath('data.answer', 'You teach CSE4204.');

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $system = data_get($request->data(), 'systemInstruction.parts.0.text', '');

            return str_contains($system, 'AUTHENTICATED USER ROLE: FACULTY')
                && str_contains($system, 'CSE4204')
                && str_contains($system, 'Artificial Intelligence')
                && ! str_contains($system, 'CSE4999')
                && ! str_contains($system, 'Other Faculty Private Course');
        });

        $this->actingAs($facultyUser, 'sanctum')
            ->postJson('/api/ai/assistant', [
                'question' => 'Continue',
                'conversation_id' => $studentConversation->id,
            ])->assertNotFound();
    }

    public function test_starting_a_new_conversation_creates_an_empty_session_without_old_gemini_context(): void
    {
        $user = $this->student();
        $oldConversation = AiConversation::create(['user_id' => $user->id, 'last_message_at' => now()->subMinute()]);
        $oldConversation->messages()->createMany([
            ['role' => 'user', 'content' => 'Old private topic'],
            ['role' => 'assistant', 'content' => 'Old answer', 'model' => 'gemini-3.5-flash-lite'],
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/ai/assistant/conversation')
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonCount(0, 'data.messages');
        $newConversationId = $response->json('data.conversation_id');

        $this->assertNotSame($oldConversation->id, $newConversationId);
        $this->assertDatabaseHas('ai_conversations', ['id' => $newConversationId, 'user_id' => $user->id]);
        $this->assertDatabaseCount('ai_messages', 2);
        Http::assertNothingSent();

        $this->actingAs($user, 'sanctum')->getJson('/api/ai/assistant/conversation')
            ->assertOk()->assertJsonPath('data.conversation_id', $newConversationId)->assertJsonCount(0, 'data.messages');

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('Fresh answer.'))]);
        $this->actingAs($user, 'sanctum')->postJson('/api/ai/assistant', [
            'question' => 'Fresh topic',
            'conversation_id' => $newConversationId,
        ])->assertOk()->assertJsonPath('data.answer', 'Fresh answer.');

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $contents = $request->data()['contents'];
            return count($contents) === 1 && data_get($contents, '0.parts.0.text') === 'Fresh topic';
        });
    }

    public function test_student_can_reload_only_their_latest_conversation_and_access_controls_remain(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $conversation = AiConversation::create(['user_id' => $owner->id, 'last_message_at' => now()]);
        $conversation->messages()->createMany([
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there', 'model' => 'gemini-3.5-flash-lite'],
        ]);

        $this->actingAs($owner, 'sanctum')->getJson('/api/ai/assistant/conversation')
            ->assertOk()->assertJsonPath('data.conversation_id', $conversation->id)->assertJsonCount(2, 'data.messages');
        $this->actingAs($other, 'sanctum')->getJson('/api/ai/assistant/conversation')
            ->assertOk()->assertJsonPath('data.conversation_id', null)->assertJsonCount(0, 'data.messages');
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->postJson('/api/ai/assistant', ['question' => 'Hello'])->assertForbidden();
    }

    private function quotaResponse(string $quotaId, ?string $retryDelay = null): array
    {
        $details = [[
            '@type' => 'type.googleapis.com/google.rpc.QuotaFailure',
            'violations' => [[
                'quotaMetric' => self::QUOTA_METRIC,
                'quotaId' => $quotaId,
                'quotaValue' => '20',
            ]],
        ]];
        if ($retryDelay !== null) $details[] = ['@type' => 'type.googleapis.com/google.rpc.RetryInfo', 'retryDelay' => $retryDelay];

        return ['error' => [
            'status' => 'RESOURCE_EXHAUSTED',
            'message' => 'Quota exceeded for metric: '.self::QUOTA_METRIC.', limit: 20',
            'details' => $details,
        ]];
    }

    private function geminiText(string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }

    private function student(): User
    {
        $user = User::factory()->create(['role' => 'student', 'department' => 'CSE']);
        Student::create(['user_id' => $user->id, 'student_number' => 'CSE-'.fake()->unique()->numerify('###'), 'department' => 'CSE', 'program' => 'BSc in CSE', 'current_semester' => 6]);
        return $user->load('studentProfile');
    }
}
