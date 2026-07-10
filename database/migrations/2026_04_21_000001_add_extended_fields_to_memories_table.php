<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guardas hasColumn: bancos criados antes desta migration podem já
        // conter parte das colunas (drift), então adiciona só as faltantes
        Schema::table('memories', function (Blueprint $table) {
            // Source tracking
            if (! Schema::hasColumn('memories', 'source_system')) {
                $table->string('source_system')->nullable()->after('validation_status');
            }
            if (! Schema::hasColumn('memories', 'source_project')) {
                $table->string('source_project')->nullable()->after('source_system');
            }
            if (! Schema::hasColumn('memories', 'source_file')) {
                $table->string('source_file')->nullable()->after('source_project');
            }
            if (! Schema::hasColumn('memories', 'original_id')) {
                $table->string('original_id')->nullable()->after('source_file');
            }

            // Severity and recurrence
            if (! Schema::hasColumn('memories', 'severity')) {
                $table->string('severity')->nullable()->after('original_id');
            }
            if (! Schema::hasColumn('memories', 'recurrence_count')) {
                $table->unsignedInteger('recurrence_count')->default(1)->after('severity');
            }

            // References
            if (! Schema::hasColumn('memories', 'official_reference')) {
                $table->text('official_reference')->nullable()->after('recurrence_count');
            }
            if (! Schema::hasColumn('memories', 'external_reference')) {
                $table->text('external_reference')->nullable()->after('official_reference');
            }

            // Validation metadata
            if (! Schema::hasColumn('memories', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('external_reference');
            }
            if (! Schema::hasColumn('memories', 'validated_by')) {
                $table->string('validated_by')->nullable()->after('validated_at');
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
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
        ], fn (string $column) => Schema::hasColumn('memories', $column)));

        if ($columns !== []) {
            Schema::table('memories', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
