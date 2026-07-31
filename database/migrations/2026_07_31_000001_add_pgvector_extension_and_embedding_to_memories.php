<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::table('memories', function (Blueprint $table) use ($driver) {
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE memories ADD COLUMN IF NOT EXISTS embedding vector(1536)');
            } else {
                $table->json('embedding')->nullable();
            }

            $table->string('embedding_model', 50)->nullable()->after('doc_validation_summary');
            $table->string('embedding_hash', 64)->nullable()->after('embedding_model');
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::table('memories', function (Blueprint $table) use ($driver) {
            $table->dropColumn(['embedding_model', 'embedding_hash']);
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE memories DROP COLUMN IF EXISTS embedding');
            } else {
                $table->dropColumn('embedding');
            }
        });
    }
};
