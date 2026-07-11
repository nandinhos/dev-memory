<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            if (! Schema::hasColumn('memories', 'doc_validation_report')) {
                $table->json('doc_validation_report')->nullable()->after('doc_validation_status');
            }

            if (! Schema::hasColumn('memories', 'doc_validated_at')) {
                $table->timestamp('doc_validated_at')->nullable()->after('doc_validation_report');
            }
        });
    }

    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            foreach (['doc_validation_report', 'doc_validated_at'] as $column) {
                if (Schema::hasColumn('memories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
