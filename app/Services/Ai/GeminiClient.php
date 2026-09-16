<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Single shared AI backbone (8.5): one Gemini REST client used by both
 * output types. LIVE API ONLY (D12) — any failure raises
 * AiUnavailableException, which jobs persist as a terminal failed state.
 * Provenance (model, prompt version, timestamps) always recorded.
 */
class GeminiClient
{
    public function generate(string $prompt): array
    {
        $apiKey = config('smartcemes.ai.key') ?: env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new AiUnavailableException('No API key configured (GEMINI_API_KEY).');
        }

        $endpoint = sprintf(
            '%s/%s:generateContent',
            rtrim(config('smartcemes.ai.endpoint'), '/'),
            config('smartcemes.ai.model')
        );

        try {
            $response = Http::asJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout(60)
                ->post($endpoint, [
                    'contents' => [[
                        'parts' => [['text' => $prompt]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiUnavailableException('AI request failed: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new AiUnavailableException('AI request failed (HTTP '.$response->status().'). '.substr($response->body(), 0, 300));
        }

        $payload = $response->json();

        $text = $payload['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            throw new AiUnavailableException('AI response contained no content.');
        }

        $decoded = $this->decodeJson($text);
        if ($decoded === null) {
            throw new AiUnavailableException('AI response was not valid JSON.');
        }

        return [
            'data' => $decoded,
            'metadata' => [
                'model' => config('smartcemes.ai.model'),
                'prompt_version' => config('smartcemes.ai.prompt_version'),
                'api' => 'gemini',
                'tokens' => $payload['usageMetadata']['totalTokenCount'] ?? null,
            ],
        ];
    }

    /** Strip optional markdown fences and decode. */
    protected function decodeJson(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $decoded = json_decode(trim($text), true);

        return is_array($decoded) ? $decoded : null;
    }
}
