<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_nodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind', 40);
            $table->foreignUuid('memory_id')->nullable()->unique()->constrained('memories')->cascadeOnDelete();
            $table->string('namespace', 100);
            $table->string('canonical_key', 64);
            $table->text('label');
            $table->json('properties')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['namespace', 'canonical_key']);
            $table->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_nodes');
    }
};
