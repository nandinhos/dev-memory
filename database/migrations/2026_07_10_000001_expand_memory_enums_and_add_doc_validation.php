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
            if (! Schema::hasColumn('memories', 'doc_validation_status')) {
                $table->string('doc_validation_status')->nullable()->after('validation_status');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE memories DROP CONSTRAINT IF EXISTS memories_type_check');
            DB::statement("ALTER TABLE memories ADD CONSTRAINT memories_type_check
                CHECK (type IN ('error', 'lesson', 'best_practice', 'workaround', 'architecture_decision', 'anti_pattern'))");

            DB::statement('ALTER TABLE memories DROP CONSTRAINT IF EXISTS memories_status_check');
            DB::statement("ALTER TABLE memories ADD CONSTRAINT memories_status_check
                CHECK (validation_status IN ('pending', 'validated', 'rejected', 'superseded'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE memories DROP CONSTRAINT IF EXISTS memories_type_check');
            DB::statement("ALTER TABLE memories ADD CONSTRAINT memories_type_check
                CHECK (type IN ('error', 'lesson', 'best_practice'))");

            DB::statement('ALTER TABLE memories DROP CONSTRAINT IF EXISTS memories_status_check');
            DB::statement("ALTER TABLE memories ADD CONSTRAINT memories_status_check
                CHECK (validation_status IN ('pending', 'validated', 'rejected'))");
        }

        Schema::table('memories', function (Blueprint $table) {
            if (Schema::hasColumn('memories', 'doc_validation_status')) {
                $table->dropColumn('doc_validation_status');
            }
        });
    }
};
