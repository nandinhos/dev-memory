<?php

namespace App\Services\Curation;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Context7 REST API (official library docs).
 * Free tier works without a key; CONTEXT7_API_KEY raises rate limits.
 */
class Context7Client
{
    private string $baseUrl;

    private ?string $apiKey;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->baseUrl = $baseUrl ?? config('services.context7.base_url');
        $this->apiKey = $apiKey ?? config('services.context7.api_key');
    }

    /**
     * Resolve a technology name ("Laravel") to a Context7 library id
     * ("/laravel/docs"). Results are ranked; the first is the best match.
     */
    public function resolveLibrary(string $technology): ?string
    {
        $cacheKey = 'context7:resolve:'.mb_strtolower(trim($technology));

        return Cache::remember($cacheKey, now()->addDay(), function () use ($technology) {
            $response = $this->request()->get('/search', ['query' => $technology]);

            return $response->successful()
                ? $response->json('results.0.id')
                : null;
        });
    }

    public function fetchDocs(string $libraryId, string $topic, int $tokens = 4000): ?string
    {
        $response = $this->request()->get($libraryId, [
            'type' => 'txt',
            'topic' => $topic,
            'tokens' => $tokens,
        ]);

        if ($response->failed() || trim($response->body()) === '') {
            return null;
        }

        return $response->body();
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->retry(2, 500, throw: false);

        return $this->apiKey ? $request->withToken($this->apiKey) : $request;
    }
}
