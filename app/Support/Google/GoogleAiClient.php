<?php

declare(strict_types=1);

namespace App\Support\Google;

use App\Support\Logging\LoggingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

/**
 * Thin wrapper around Google's Generative Language API (Gemini), the engine behind
 * the vectorized DB + the AI-mapped search box (CLAUDE.md §1.2, §3). Two jobs:
 *
 *   1. embed() / embedBatch() — turn Bulgarian text into a vector with the SAME
 *      embedding model for documents (at `search:embed`) and for the live query,
 *      so pgvector cosine distance is meaningful. Output dimensionality is pinned
 *      to config('vector.dimensions') so it matches the stored vector() columns.
 *   2. mapQuery() — let Gemini "guess" what a citizen meant, turning a raw search
 *      box string into a cleaned retrieval phrase. Degrades to the raw query.
 *
 * The key lives in env (GOOGLE_API_KEY) and is sent as a header, never in the URL
 * or logs (security.md §7/§9). Every failure is logged with context and degrades
 * gracefully — the search box must keep working (keyword fallback) without a key.
 */
final class GoogleAiClient
{
    private const BASE = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(private readonly LoggingService $log) {}

    /** True when a GOOGLE_API_KEY is configured (otherwise callers fall back). */
    public function configured(): bool
    {
        return $this->key() !== '';
    }

    /**
     * Embed a single text (the live search path). Cached in Redis by model/dim/
     * task/text so a repeated query skips the network round-trip and feels instant,
     * and it uses the shorter query timeout so a slow API degrades to keyword search
     * fast rather than hanging the box. Returns null when unavailable.
     *
     * @return list<float>|null
     */
    public function embed(string $text, string $taskType = 'RETRIEVAL_QUERY'): ?array
    {
        if ($text === '' || ! $this->configured()) {
            return null;
        }

        $model = (string) config('services.google.embedding_model');
        $dim = (int) config('vector.dimensions', 384);
        $ttl = (int) config('services.google.embed_cache_ttl', 86400);
        $cacheKey = "embed:{$taskType}:{$model}:{$dim}:".md5($text);

        if ($ttl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $batch = $this->embedBatch([$text], $taskType, (int) config('services.google.query_timeout', 8));
        $vector = $batch === null ? null : ($batch[0] ?? null);

        if ($vector !== null && $ttl > 0) {
            Cache::put($cacheKey, $vector, $ttl);
        }

        return $vector;
    }

    /**
     * Batch-embed many texts in one round-trip — used by `search:embed` to
     * vectorize the DB. Returns null on any failure (the caller logs + skips the
     * batch honestly rather than storing a half-batch).
     *
     * @param  list<string>  $texts
     * @return list<list<float>>|null
     */
    public function embedBatch(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', int $timeout = 30): ?array
    {
        if ($texts === [] || ! $this->configured()) {
            return null;
        }

        $model = (string) config('services.google.embedding_model');
        $dim = (int) config('vector.dimensions', 384);

        $requests = array_map(static fn (string $t): array => [
            'model' => "models/{$model}",
            'content' => ['parts' => [['text' => $t]]],
            'taskType' => $taskType,
            'outputDimensionality' => $dim,
        ], array_values($texts));

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $this->key()])
                ->timeout($timeout)
                ->post(self::BASE."/models/{$model}:batchEmbedContents", ['requests' => $requests]);
        } catch (Throwable $e) {
            $this->log->error('google: embed request threw', ['error' => $e->getMessage(), 'count' => count($texts)]);

            return null;
        }

        if ($response->failed()) {
            $this->log->error('google: embed returned error', ['status' => $response->status(), 'count' => count($texts)]);

            return null;
        }

        $out = [];
        foreach ((array) $response->json('embeddings', []) as $embedding) {
            $values = is_array($embedding) ? ($embedding['values'] ?? null) : null;
            if (! is_array($values)) {
                $this->log->error('google: unexpected embed response shape', ['count' => count($texts)]);

                return null;
            }
            $out[] = array_map(static fn ($v): float => (float) $v, array_values($values));
        }

        return count($out) === count($texts) ? $out : null;
    }

    /**
     * Ask Gemini to map a raw search-box query to a cleaned Bulgarian retrieval
     * phrase ("guess" the intent). Returns the original query on any failure or
     * when refinement is disabled, so the search path never depends on this call.
     */
    public function mapQuery(string $query): string
    {
        if (! $this->configured() || ! (bool) config('services.google.refine_search', false)) {
            return $query;
        }

        $model = (string) config('services.google.chat_model');
        $ttl = (int) config('services.google.embed_cache_ttl', 86400);
        $cacheKey = "qmap:{$model}:".md5($query);

        if ($ttl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached)) {
                return $cached;
            }
        }
        $system = 'Ти си помощник за търсене в регистър на българските обществени поръчки. '
            .'Преформулирай заявката на потребителя в кратка ключова фраза на български за семантично търсене. '
            .'Запази имена на институции и фирми. Не добавяй обяснения.';
        $schema = [
            'type' => 'object',
            'properties' => ['query' => ['type' => 'string']],
            'required' => ['query'],
        ];

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $this->key()])
                ->timeout(15)
                ->post(self::BASE."/models/{$model}:generateContent", [
                    'system_instruction' => ['parts' => [['text' => $system]]],
                    'contents' => [['parts' => [['text' => $query]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                        'temperature' => 0.0,
                    ],
                ]);
        } catch (Throwable $e) {
            $this->log->warning('google: query map threw; using raw query', ['error' => $e->getMessage()]);

            return $query;
        }

        if ($response->failed()) {
            $this->log->warning('google: query map error; using raw query', ['status' => $response->status()]);

            return $query;
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($text) || trim($text) === '') {
            return $query;
        }

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $query;
        }

        $refined = is_array($decoded) ? ($decoded['query'] ?? null) : null;
        $result = is_string($refined) && trim($refined) !== '' ? trim($refined) : $query;

        if ($ttl > 0) {
            Cache::put($cacheKey, $result, $ttl);
        }

        return $result;
    }

    private function key(): string
    {
        return trim((string) config('services.google.key', ''));
    }
}
