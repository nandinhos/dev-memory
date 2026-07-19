<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A migration 2026_07_10 adicionou 'architecture_decision' (21 chars) ao
     * CHECK de memories.type, mas a coluna seguiu varchar(20) — o insert estoura
     * em Postgres (SQLSTATE 22001). SQLite não enforça varchar(N), por isso os
     * testes nunca pegaram. Alarga para varchar(50) (margem para valores futuros).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return; // SQLite ignora o limite de varchar(N); nada a alargar
        }

        DB::statement('ALTER TABLE memories ALTER COLUMN type TYPE varchar(50)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Só é seguro reduzir se não houver valores com mais de 20 chars.
        DB::statement('ALTER TABLE memories ALTER COLUMN type TYPE varchar(20)');
    }
};
