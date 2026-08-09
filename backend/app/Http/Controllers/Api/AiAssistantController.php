<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeminiRateLimitException;
use App\Exceptions\GeminiServiceException;
use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\Faculty;
use App\Services\GeminiCampusAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiAssistantController extends Controller
{
    private const HISTORY_LIMIT = 16;
    private const UNAVAILABLE_MESSAGE = 'AI service is temporarily unavailable. Please try again.';

    public function conversation(Request $request): JsonResponse
    {
        if ($response = $this->requireAssistantUser($request)) return $response;

        $conversation = AiConversation::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_message_at')
            ->first();

        if (! $conversation) {
            return response()->json(['status' => true, 'data' => ['conversation_id' => null, 'messages' => []]]);
        }

        return response()->json(['status' => true, 'data' => [
            'conversation_id' => $conversation->id,
            'messages' => $conversation->messages()->oldest()->get(['id', 'role', 'content', 'created_at']),
        ]]);
    }

    public function startConversation(Request $request): JsonResponse
    {
        if ($response = $this->requireAssistantUser($request)) return $response;

        $conversation = AiConversation::create([
            'user_id' => $request->user()->id,
            'last_message_at' => now(),
        ]);

        return response()->json(['status' => true, 'data' => [
            'conversation_id' => $conversation->id,
            'messages' => [],
        ]], 201);
    }

    public function ask(Request $request, GeminiCampusAssistant $assistant): JsonResponse
    {
        if ($response = $this->requireAssistantUser($request)) return $response;

        $data = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);
        $question = trim($data['question']);
        if ($question === '') {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['question' => ['Please enter a question.']]], 422);
        }

        $conversation = null;
        if (! empty($data['conversation_id'])) {
            $conversation = AiConversation::query()
                ->whereKey($data['conversation_id'])
                ->where('user_id', $request->user()->id)
                ->first();

            if (! $conversation) {
                return response()->json(['status' => false, 'message' => 'Conversation not found.'], 404);
            }
        }

        $history = $conversation
            ? $conversation->messages()->latest()->limit(self::HISTORY_LIMIT)->get(['role', 'content'])->reverse()->values()->all()
            : [];

        try {
            $answer = $assistant->answer($question, $history, $request->user());
        } catch (GeminiRateLimitException $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 429);
        } catch (GeminiServiceException $exception) {
            return response()->json(['status' => false, 'message' => self::UNAVAILABLE_MESSAGE], 503);
        }

        $conversation = DB::transaction(function () use ($conversation, $request, $question, $answer) {
            $conversation ??= AiConversation::create([
                'user_id' => $request->user()->id,
                'title' => mb_substr($question, 0, 100),
            ]);
            $conversation->messages()->createMany([
                ['role' => 'user', 'content' => $question],
                ['role' => 'assistant', 'content' => $answer['answer'], 'model' => $answer['model']],
            ]);
            $conversation->update(['last_message_at' => now()]);
            return $conversation;
        });

        return response()->json(['status' => true, 'data' => array_merge($answer, [
            'conversation_id' => $conversation->id,
        ])]);
    }

    private function requireAssistantUser(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! in_array($user?->role, ['student', 'faculty'], true)) {
            return response()->json(['status' => false, 'message' => 'Student or faculty access is required.'], 403);
        }
        if ($user->role === 'student' && ! $user->studentProfile) {
            return response()->json(['status' => false, 'message' => 'Your student profile is not available.'], 422);
        }
        if ($user->role === 'faculty' && ! $user->facultyProfile) {
            try {
                $profile = Faculty::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'department' => $user->department,
                        'designation' => 'Faculty Member',
                    ],
                );
                $user->setRelation('facultyProfile', $profile);
            } catch (Throwable $exception) {
                Log::warning('Faculty AI profile could not be initialized', [
                    'user_id' => $user->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return null;
    }
}
