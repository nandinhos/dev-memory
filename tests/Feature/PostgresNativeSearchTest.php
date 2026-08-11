<?php

namespace Tests\Feature;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Models\Memory;
use App\Services\Search\MemorySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresNativeSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_pgvector_and_full_text_schema_supports_lexical_search(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este gate cobre recursos nativos do PostgreSQL.');
        }

        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('vector', DB::table('pg_extension')->where('extname', 'vector')->value('extname'));
        $this->assertNotNull(DB::table('pg_indexes')->where('indexname', 'memories_search_vector_gin')->value('indexname'));

        $memory = Memory::create([
            'title' => 'Falha de migração PostgreSQL',
            'description' => 'A busca lexical precisa usar o índice tsvector nativo.',
            'type' => MemoryType::LESSON,
            'scope' => MemoryScope::PROJECT,
        ]);

        $result = app(MemorySearchService::class)->search('migração PostgreSQL');

        $this->assertSame('lexical', $result['search_mode']);
        $this->assertSame($memory->id, $result['results']->first()['memory']->id);
    }
}
