<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('purpose');
            $table->text('rationale');
            $table->decimal('cohesion', 3, 2);
            $table->string('status')->default('proposed');
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('memory_skill_group', function (Blueprint $table) {
            $table->foreignUuid('skill_group_id')->constrained('skill_groups')->cascadeOnDelete();
            $table->foreignUuid('memory_id')->constrained('memories')->cascadeOnDelete();
            $table->primary(['skill_group_id', 'memory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_skill_group');
        Schema::dropIfExists('skill_groups');
    }
};
