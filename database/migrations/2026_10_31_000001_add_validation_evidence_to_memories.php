<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Curadoria v2: trilha de auditoria da validação (fast-track probe/reference).
     * Coluna única JSON: {track, probe_exit_code, probe_platform,
     * probe_output_hash, reference_version, validated_at}.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('memories', 'validation_evidence')) {
            Schema::table('memories', function (Blueprint $table) {
                $table->json('validation_evidence')->nullable()->after('validated_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('memories', 'validation_evidence')) {
            Schema::table('memories', function (Blueprint $table) {
                $table->dropColumn('validation_evidence');
            });
        }
    }
};
