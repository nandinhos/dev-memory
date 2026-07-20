<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca memórias cuja validação documental foi refeita pela IA (que apontou a
     * biblioteca correta no Context7 após um falso-negativo). Dá o badge "Reanalisado
     * por IA" e permite filtrar/auditar.
     */
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            if (! Schema::hasColumn('memories', 'reanalyzed_by_ai')) {
                $table->boolean('reanalyzed_by_ai')->default(false)->after('doc_validation_report');
            }
        });
    }

    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            if (Schema::hasColumn('memories', 'reanalyzed_by_ai')) {
                $table->dropColumn('reanalyzed_by_ai');
            }
        });
    }
};
