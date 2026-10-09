<?php

namespace Tests\Feature;

use App\Services\Ai\AiUnavailableException;
use App\Services\Ai\GeminiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gemini transient-failure retry (added 2026-10-02).
 *
 * WHY THIS EXISTS
 * ---------------
 * Gemini sheds capacity under peak load — "temporarily restricting capacity for
 * preview or flash models" — returning 503 UNAVAILABLE, and returns 429
 * RESOURCE_EXHAUSTED when a quota is hit. Both are transient.
 *
 * The pipeline runs inside the web request via `dispatchSync`, so the jobs' own
 * `tries`/`backoff` never apply: before this, a single 503 became an immediate
 * "Analysis unavailable". Google's documented remedy is exponential backoff with
 * jitter on 408/429/5xx, and explicitly NOT on 4xx — these tests pin both halves,
 * because "retry everything" would turn a bad key or a retired model id into a
 * three-attempt delay before showing the same error.
 *
 * The backoff is zeroed in phpunit.xml (GEMINI_RETRY_BASE_MS=0) so no test sleeps.
 */
class GeminiRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'smartcemes.ai.key' => 'test-key',
            'smartcemes.ai.model' => 'gemini-3.6-flash',
        ]);
    }

    /** A well-formed Gemini success body. */
    private function ok(string $summary = 'Fine.'): array
    {
        return [
            'candidates' => [[
                'content' => ['parts' => [['text' => json_encode(['summary' => $summary])]]],
            ]],
        ];
    }

    /** The real 503 the API returns when it is shedding load. */
    private function overloaded(): array
    {
        return ['error' => [
            'code' => 503,
            'status' => 'UNAVAILABLE',
            'message' => 'The model is currently experiencing high demand. Spikes in demand are usually temporary. Please try again later.',
        ]];
    }

    public function test_a_503_capacity_error_is_retried_and_then_succeeds(): void
    {
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::sequence()
                ->push($this->overloaded(), 503)
                ->push($this->ok('Recovered after a peak-traffic blip.'), 200),
        ]);

        $result = app(GeminiClient::class)->generate('prompt');

        $this->assertSame('Recovered after a peak-traffic blip.', $result['data']['summary']);
        // The retry is recorded, so a stored analysis can show it absorbed a 503.
        $this->assertSame(2, $result['metadata']['attempts']);
        Http::assertSentCount(2);
    }

    public function test_a_429_quota_error_is_retried(): void
    {
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::sequence()
                ->push(['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED']], 429)
                ->push($this->ok('Second time lucky.'), 200),
        ]);

        $result = app(GeminiClient::class)->generate('prompt');

        $this->assertSame('Second time lucky.', $result['data']['summary']);
        $this->assertSame(2, $result['metadata']['attempts']);
        Http::assertSentCount(2);
    }

    public function test_a_500_is_retried(): void
    {
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::sequence()
                ->push('internal error', 500)
                ->push($this->ok(), 200),
        ]);

        $result = app(GeminiClient::class)->generate('prompt');

        $this->assertSame(2, $result['metadata']['attempts']);
    }

    public function test_a_successful_first_attempt_is_not_retried(): void
    {
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response($this->ok(), 200)]);

        $result = app(GeminiClient::class)->generate('prompt');

        $this->assertSame(1, $result['metadata']['attempts']);
        Http::assertSentCount(1);
    }

    public function test_a_400_bad_request_is_not_retried(): void
    {
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response('bad request', 400)]);

        try {
            app(GeminiClient::class)->generate('prompt');
            $this->fail('A 400 must fail immediately rather than be retried into a success.');
        } catch (AiUnavailableException $e) {
            $this->assertStringContainsString('HTTP 400', $e->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_a_404_retired_model_id_is_not_retried(): void
    {
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response('model not found', 404)]);

        try {
            app(GeminiClient::class)->generate('prompt');
            $this->fail('A retired model id must fail immediately — retrying cannot fix it.');
        } catch (AiUnavailableException $e) {
            $this->assertStringContainsString('HTTP 404', $e->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_a_402_depleted_prepay_balance_is_not_retried(): void
    {
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response('prepay credits depleted', 402)]);

        try {
            app(GeminiClient::class)->generate('prompt');
            $this->fail('A depleted prepay balance must fail immediately, not burn retries.');
        } catch (AiUnavailableException $e) {
            $this->assertStringContainsString('HTTP 402', $e->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_retries_stop_after_the_configured_max_attempts(): void
    {
        config(['smartcemes.ai.retry.max_attempts' => 3]);
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response($this->overloaded(), 503)]);

        try {
            app(GeminiClient::class)->generate('prompt');
            $this->fail('A sustained 503 must surface as a failure after the retry budget.');
        } catch (AiUnavailableException $e) {
            $this->assertStringContainsString('HTTP 503', $e->getMessage());
        }

        // Exactly the configured number of attempts — no runaway retry loop.
        Http::assertSentCount(3);
    }

    public function test_retrying_can_be_disabled(): void
    {
        config(['smartcemes.ai.retry.max_attempts' => 1]);
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response($this->overloaded(), 503)]);

        try {
            app(GeminiClient::class)->generate('prompt');
            $this->fail('With retries disabled a 503 must fail on the first attempt.');
        } catch (AiUnavailableException $e) {
            $this->assertStringContainsString('HTTP 503', $e->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_a_connection_failure_is_retried(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            if ($attempts === 1) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response($this->ok('Recovered from a timeout.'), 200);
        });

        $result = app(GeminiClient::class)->generate('prompt');

        $this->assertSame('Recovered from a timeout.', $result['data']['summary']);
        $this->assertSame(2, $result['metadata']['attempts']);
    }

    /**
     * THE KEY MUST COME FROM CONFIG — a runtime `env()` fallback hides a
     * production-only failure (added 2026-10-07).
     *
     * `GeminiClient` used to read `config('smartcemes.ai.key') ?: env('GEMINI_API_KEY')`
     * and the config key did not exist, so the real read was a runtime `env()` call.
     * That works in development and breaks on the deployed server: `php artisan
     * config:cache` (deploy checklist item 10) stops Laravel loading `.env`, so
     * `env()` returns null at request time and every AI surface falls into its
     * "unavailable" state — while the model id and endpoint, which ARE read through
     * config, keep working.
     *
     * This test puts a key in the ENVIRONMENT and none in config, which is exactly
     * the shape of a cached production config. An `env()` fallback would rescue the
     * call and this test would fail; with config as the single source of truth the
     * client refuses to start, which is the honest outcome.
     */
    public function test_the_api_key_is_read_from_config_not_from_a_runtime_env_call(): void
    {
        $originalEnv = getenv('GEMINI_API_KEY');
        putenv('GEMINI_API_KEY=an-env-only-key');
        $_ENV['GEMINI_API_KEY'] = 'an-env-only-key';

        config(['smartcemes.ai.key' => null]);

        // If the client reached the network at all, the fake below would answer it
        // and no exception would be raised — which is the bug being pinned.
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response($this->ok('Should never be reached.'), 200)]);

        try {
            app(GeminiClient::class)->generate('prompt');

            $this->fail('The client reached the network: a runtime env() fallback rescued a null config key.');
        } catch (AiUnavailableException $e) {
            $this->assertStringContainsString('No API key configured', $e->getMessage());
        } finally {
            if ($originalEnv === false) {
                putenv('GEMINI_API_KEY');
                unset($_ENV['GEMINI_API_KEY']);
            } else {
                putenv('GEMINI_API_KEY='.$originalEnv);
                $_ENV['GEMINI_API_KEY'] = $originalEnv;
            }
        }

        Http::assertNothingSent();
    }
}
