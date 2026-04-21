<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            // Source tracking
            $table->string('source_system')->nullable()->after('validation_status');
            $table->string('source_project')->nullable()->after('source_system');
            $table->string('source_file')->nullable()->after('source_project');
            $table->string('original_id')->nullable()->after('source_file');

            // Severity and recurrence
            $table->string('severity')->nullable()->after('original_id');
            $table->unsignedInteger('recurrence_count')->default(1)->after('severity');

            // References
            $table->text('official_reference')->nullable()->after('recurrence_count');
            $table->text('external_reference')->nullable()->after('official_reference');

            // Validation metadata
            $table->timestamp('validated_at')->nullable()->after('external_reference');
            $table->string('validated_by')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->dropColumn([
                'source_system',
                'source_project',
                'source_file',
                'original_id',
                'severity',
                'recurrence_count',
                'official_reference',
                'external_reference',
                'validated_at',
                'validated_by',
            ]);
        });
    }
};
