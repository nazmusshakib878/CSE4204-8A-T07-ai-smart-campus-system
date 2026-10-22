<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GeminiCourseRecommender
{
    public function recommend(array $studentContext, array $candidates): array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-3.6-flash');
        $timeoutSeconds = max(1, min(10, (int) config('services.gemini.recommendation_timeout', 8)));

        if ($apiKey === '') {
            throw new RuntimeException('Gemini is not configured.');
        }

        try {
            $startedAt = microtime(true);
            $response = Http::acceptJson()
                ->asJson()
                ->timeout($timeoutSeconds)
                ->connectTimeout(min(3, $timeoutSeconds))
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post(sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model), [
                    'systemInstruction' => [
                        'parts' => [[
                            'text' => 'Rank only the supplied university course candidates. Return valid JSON only. Do not invent course IDs, prerequisites, grades, or university policies.',
                        ]],
                    ],
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => json_encode([
                                'task' => 'Return up to 6 recommendations as {"recommendations":[{"course_id":1,"score":90,"reason":"one concise reason"}]}.',
                                'student' => $studentContext,
                                'candidates' => $candidates,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 700,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('Gemini course recommendation request failed', [
                    'model' => $model,
                    'http_status' => $response->status(),
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ]);
                throw new RuntimeException('Gemini recommendation request failed.');
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            $payload = is_string($text) ? json_decode($text, true) : null;

            if (! is_array($payload) || ! is_array($payload['recommendations'] ?? null)) {
                throw new RuntimeException('Gemini returned an invalid recommendation payload.');
            }

            return [
                'model' => $model,
                'recommendations' => $payload['recommendations'],
            ];
        } catch (Throwable $exception) {
            Log::warning('Gemini course recommendation request unavailable', [
                'model' => $model,
                'timeout_seconds' => $timeoutSeconds,
                'exception' => $exception::class,
            ]);
            throw $exception instanceof RuntimeException
                ? $exception
                : new RuntimeException('Gemini course recommendations are temporarily unavailable.', 0, $exception);
        }
    }
}
