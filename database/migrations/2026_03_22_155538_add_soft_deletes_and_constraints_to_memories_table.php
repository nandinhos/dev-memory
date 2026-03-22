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
            $table->softDeletes();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE memories ADD CONSTRAINT memories_type_check
                CHECK (type IN ('error', 'lesson', 'best_practice'))");
            DB::statement("ALTER TABLE memories ADD CONSTRAINT memories_scope_check
                CHECK (scope IN ('project', 'global'))");
            DB::statement("ALTER TABLE memories ADD CONSTRAINT memories_status_check
                CHECK (validation_status IN ('pending', 'validated', 'rejected'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE memories DROP CONSTRAINT IF EXISTS memories_type_check');
            DB::statement('ALTER TABLE memories DROP CONSTRAINT IF EXISTS memories_scope_check');
            DB::statement('ALTER TABLE memories DROP CONSTRAINT IF EXISTS memories_status_check');
        }

        Schema::table('memories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
