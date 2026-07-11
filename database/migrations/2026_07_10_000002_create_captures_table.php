<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source_system');
            $table->string('trigger_event')->nullable();
            $table->string('source_project')->nullable();
            $table->text('raw_content');
            $table->text('sanitized_content')->nullable();
            $table->json('metadata')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->string('status')->default('pending');
            $table->foreignUuid('memory_id')->nullable()->constrained('memories')->nullOnDelete();
            $table->timestamps();

            $table->index('source_system');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captures');
    }
};
