<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiRiskAnalyzer
{
    public function analyze(array $student): array
    {
        $apiKey = (string) config('services.openai.api_key');
        $model = (string) config('services.openai.model', 'gpt-4.1-mini');

        if ($apiKey === '') {
            throw new RuntimeException('OpenAI is not configured. Add OPENAI_API_KEY to backend/.env.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('services.openai.timeout', 30))
            ->retry(2, 500)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'instructions' => implode("\n", [
                    'You are an academic early-warning assistant for a university.',
                    'Analyze only the supplied academic indicators.',
                    'Do not infer protected or personal characteristics.',
                    'Return concise, supportive, actionable academic guidance.',
                    'A risk score is 0 to 100, where higher means greater academic risk.',
                ]),
                'input' => 'Analyze this student academic record: '.json_encode($student, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'temperature' => 0.2,
                'max_output_tokens' => 350,
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'student_risk_analysis',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'risk_score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                                'risk_level' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                                'prediction' => ['type' => 'string'],
                                'reasons' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'minItems' => 1,
                                    'maxItems' => 4,
                                ],
                                'advice' => ['type' => 'string'],
                            ],
                            'required' => ['risk_score', 'risk_level', 'prediction', 'reasons', 'advice'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            $message = $response->json('error.message') ?: 'OpenAI could not analyze this student right now.';
            throw new RuntimeException($message, $response->status(), $exception);
        }

        $json = $response->json('output_text');

        if (! is_string($json) || $json === '') {
            $json = collect($response->json('output', []))
                ->flatMap(fn (array $output) => $output['content'] ?? [])
                ->firstWhere('type', 'output_text')['text'] ?? null;
        }

        $analysis = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($analysis)) {
            throw new RuntimeException('OpenAI returned an invalid risk analysis.');
        }

        $analysis['model'] = $model;

        return $analysis;
    }
}
