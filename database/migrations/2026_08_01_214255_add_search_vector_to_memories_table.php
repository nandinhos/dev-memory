<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->index('embedding_model');
        });

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE memories
            ADD COLUMN IF NOT EXISTS search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector(
                    'simple',
                    coalesce(title, '') || ' ' ||
                    coalesce(description, '') || ' ' ||
                    coalesce(stack, '')
                )
            ) STORED
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS memories_search_vector_gin ON memories USING GIN (search_vector)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS memories_search_vector_gin');
            DB::statement('ALTER TABLE memories DROP COLUMN IF EXISTS search_vector');
        }

        Schema::table('memories', function (Blueprint $table) {
            $table->dropIndex(['embedding_model']);
        });
    }
};
