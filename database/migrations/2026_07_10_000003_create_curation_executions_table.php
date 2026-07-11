<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curation_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('capture_id')->constrained('captures')->cascadeOnDelete();
            $table->string('pipeline_stage');
            $table->string('provider');
            $table->string('model');
            $table->string('prompt_version');
            $table->decimal('temperature', 3, 2)->nullable();
            $table->string('input_hash', 64);
            $table->string('output_hash', 64)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->json('usage')->nullable();
            $table->string('status');
            $table->string('outcome')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['capture_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curation_executions');
    }
};
