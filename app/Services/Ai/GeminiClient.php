<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Single shared AI backbone (8.5): one Gemini REST client used by both
 * output types. LIVE API ONLY (D12) — a terminal failure raises
 * AiUnavailableException, which the jobs persist as a first-class failed state.
 * Provenance (model, prompt version, timestamps) always recorded.
 *
 * TRANSIENT-FAILURE RETRY (added 2026-10-02)
 * -----------------------------------------
 * Gemini returns 503 UNAVAILABLE under peak load ("temporarily restricting
 * capacity for preview or flash models") and 429 RESOURCE_EXHAUSTED when a
 * quota is hit. Both are transient, and Google's documented remedy is
 * exponential backoff with jitter on 408/429/5xx — explicitly NOT on 4xx,
 * because a bad key, a depleted prepay balance (402) or a retired model id
 * cannot be fixed by asking again.
 *
 * This matters more here than in a typical app: the whole pipeline runs inside
 * the web request via dispatchSync, so the jobs' own `tries`/`backoff` never
 * apply and a single 503 used to become an immediate "Analysis unavailable".
 *
 * The budget is deliberately small (3 attempts, ~1s + ~2s of waiting, plus a
 * wall-clock ceiling) because a Director is waiting on the response. A failure
 * that survives the retries still lands in the same first-class failed state
 * with the same manual Retry button — this only absorbs the blips.
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

        $maxAttempts = max(1, (int) config('smartcemes.ai.retry.max_attempts', 3));
        $baseDelayMs = max(0, (int) config('smartcemes.ai.retry.base_delay_ms', 1000));
        $maxDelayMs = max($baseDelayMs, (int) config('smartcemes.ai.retry.max_delay_ms', 8000));
        $deadline = microtime(true)
            + (max(0, (int) config('smartcemes.ai.retry.total_budget_ms', 120000)) / 1000);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
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
                $detail = 'AI request failed: '.$e->getMessage();

                if (! $this->shouldRetryAgain($attempt, $maxAttempts, $deadline)) {
                    throw new AiUnavailableException($detail, previous: $e);
                }

                $this->waitBeforeRetry($attempt, $baseDelayMs, $maxDelayMs, null, 'connection error');

                continue;
            }

            if ($response->successful()) {
                return $this->parse($response, $attempt);
            }

            $status = $response->status();
            $detail = 'AI request failed (HTTP '.$status.'). '.substr($response->body(), 0, 300);

            if (! $this->isRetryable($status) || ! $this->shouldRetryAgain($attempt, $maxAttempts, $deadline)) {
                throw new AiUnavailableException($detail);
            }

            $this->waitBeforeRetry(
                $attempt,
                $baseDelayMs,
                $maxDelayMs,
                $this->retryAfterSeconds($response),
                "HTTP {$status}"
            );
        }

        // Every loop path returns or throws; this keeps the return type honest.
        throw new AiUnavailableException('AI request failed.');
    }

    /**
     * 408 (timeout) and 429 (quota) are transient, and so is every 5xx —
     * 503 capacity shedding is the one this exists for. Other 4xx are the
     * caller's problem and retrying them only delays the error the Director
     * needs to see.
     */
    protected function isRetryable(int $status): bool
    {
        return $status === 408 || $status === 429 || ($status >= 500 && $status <= 599);
    }

    /** Retry only while attempts remain AND the overall time budget allows. */
    protected function shouldRetryAgain(int $attempt, int $maxAttempts, float $deadline): bool
    {
        return $attempt < $maxAttempts && microtime(true) < $deadline;
    }

    /**
     * Exponential backoff with jitter. A Retry-After from the API wins over our
     * guess, because waiting less than the server asked just burns an attempt.
     */
    protected function waitBeforeRetry(
        int $attempt,
        int $baseDelayMs,
        int $maxDelayMs,
        ?int $retryAfterSeconds,
        string $reason
    ): void {
        $delayMs = $retryAfterSeconds !== null
            ? $retryAfterSeconds * 1000
            : $baseDelayMs * (2 ** ($attempt - 1));

        $delayMs = (int) min($delayMs, $maxDelayMs);

        if ($delayMs > 0) {
            // Jitter (0.85x–1.15x) so parallel clients do not resynchronise.
            $delayMs = (int) ($delayMs * (0.85 + mt_rand(0, 300) / 1000));
        }

        Log::warning('Gemini request failed — retrying', [
            'attempt' => $attempt,
            'reason' => $reason,
            'delay_ms' => $delayMs,
        ]);

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }

    /** Honour a Retry-After header when the API sends one (429/503 often do). */
    protected function retryAfterSeconds(Response $response): ?int
    {
        $header = trim((string) $response->header('Retry-After'));

        if ($header === '') {
            return null;
        }

        if (is_numeric($header)) {
            return max(0, (int) $header);
        }

        $timestamp = strtotime($header);

        return $timestamp === false ? null : max(0, $timestamp - time());
    }

    protected function parse(Response $response, int $attempt): array
    {
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
                // 1 = answered first time. >1 records that a transient 429/503
                // was absorbed instead of being shown to the Director.
                'attempts' => $attempt,
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
