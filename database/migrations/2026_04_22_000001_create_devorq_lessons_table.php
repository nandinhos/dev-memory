<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devorq_lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('problem');
            $table->text('solution');
            $table->json('stack')->default('[]');
            $table->json('tags')->default('[]');
            $table->string('project')->default('devorq_v3');
            $table->string('source_file')->nullable();
            $table->boolean('validated')->default(false);
            $table->boolean('applied')->default(false);
            $table->integer('recurrence_count')->default(0);
            $table->json('metadata')->default('{}');
            $table->timestamps();

            $table->index('project');
            $table->index('validated');
            $table->index(['project', 'validated']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devorq_lessons');
    }
};
