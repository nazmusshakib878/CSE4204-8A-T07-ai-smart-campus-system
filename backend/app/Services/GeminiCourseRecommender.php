<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GeminiCourseRecommender
{
    public function recommend(array $studentContext, array $candidates): array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-3.6-flash');

        if ($apiKey === '') {
            throw new RuntimeException('Gemini is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('services.gemini.timeout', 30))
                ->connectTimeout(5)
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
            throw $exception instanceof RuntimeException
                ? $exception
                : new RuntimeException('Gemini course recommendations are temporarily unavailable.', 0, $exception);
        }
    }
}
