<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OpenAiCampusAssistant
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a student academic assistant. Help the authenticated student with study advice, course planning, and campus guidance using only the provided context. Keep answers short, practical, and supportive.
PROMPT;

    public function answer(string $question, array $context): array
    {
        $apiKey = (string) config('services.openai.api_key');
        $model = (string) config('services.openai.model', 'gpt-4.1-mini');

        if ($apiKey === '') {
            throw new RuntimeException('AI Assistant is temporarily unavailable. Please try again.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('services.openai.timeout', 30))
                ->connectTimeout(10)
                ->retry(2, 500)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'instructions' => self::SYSTEM_PROMPT,
                    'input' => "Student context:\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\nStudent question:\n".$question,
                    'temperature' => 0.3,
                    'max_output_tokens' => 500,
                ]);
            $response->throw();
        } catch (Throwable $exception) {
            report($exception);
            throw new RuntimeException('AI Assistant is temporarily unavailable. Please try again.');
        }

        $answer = $response->json('output_text');
        if (! is_string($answer) || trim($answer) === '') {
            $answer = collect($response->json('output', []))
                ->flatMap(fn (array $output) => $output['content'] ?? [])
                ->firstWhere('type', 'output_text')['text'] ?? null;
        }

        if (! is_string($answer) || trim($answer) === '') {
            throw new RuntimeException('AI Assistant returned an invalid response. Please try again.');
        }

        return ['answer' => trim($answer)];
    }
}
