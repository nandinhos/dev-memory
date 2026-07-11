<?php

namespace Tests\Unit;

use App\Services\Curation\Context7Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Context7ClientTest extends TestCase
{
    private function client(): Context7Client
    {
        return new Context7Client(baseUrl: 'https://context7.test/api/v1', apiKey: null);
    }

    public function test_resolves_library_to_first_ranked_result(): void
    {
        Http::fake([
            'context7.test/api/v1/search*' => Http::response([
                'results' => [
                    ['id' => '/laravel/docs', 'title' => 'Laravel'],
                    ['id' => '/websites/laravel', 'title' => 'Laravel Website'],
                ],
            ]),
        ]);

        $this->assertSame('/laravel/docs', $this->client()->resolveLibrary('Laravel'));
    }

    public function test_resolution_is_cached(): void
    {
        Http::fake([
            'context7.test/api/v1/search*' => Http::response([
                'results' => [['id' => '/laravel/docs']],
            ]),
        ]);

        $client = $this->client();
        $client->resolveLibrary('Laravel');
        $client->resolveLibrary('Laravel');

        Http::assertSentCount(1);
        $this->assertTrue(Cache::has('context7:resolve:laravel'));
    }

    public function test_returns_null_when_search_fails(): void
    {
        Http::fake(['context7.test/*' => Http::response([], 500)]);

        $this->assertNull($this->client()->resolveLibrary('Inexistente'));
    }

    public function test_fetches_docs_as_plain_text(): void
    {
        Http::fake([
            'context7.test/api/v1/laravel/docs*' => Http::response('### Check Migration Status ...'),
        ]);

        $docs = $this->client()->fetchDocs('/laravel/docs', 'migrations');

        $this->assertStringContainsString('Migration Status', $docs);
    }

    public function test_returns_null_on_empty_docs(): void
    {
        Http::fake(['context7.test/*' => Http::response('   ')]);

        $this->assertNull($this->client()->fetchDocs('/laravel/docs', 'tema obscuro'));
    }
}
