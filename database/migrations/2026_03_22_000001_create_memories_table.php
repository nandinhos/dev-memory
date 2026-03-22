<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->nullable();
            $table->text('title');
            $table->text('description');
            $table->string('type', 20);
            $table->string('stack')->nullable();
            $table->string('scope', 20)->default('project');
            $table->string('validation_status', 20)->default('pending');
            $table->text('official_reference')->nullable();
            $table->integer('recurrence_count')->default(1);
            $table->timestamps();

            $table->index('type');
            $table->index('stack');
            $table->index('scope');
            $table->index('project_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};