<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_edges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_node_id')->constrained('knowledge_nodes')->cascadeOnDelete();
            $table->foreignUuid('target_node_id')->constrained('knowledge_nodes')->cascadeOnDelete();
            $table->string('relation_type', 40);
            $table->string('status', 20)->default('proposed');
            $table->decimal('confidence', 5, 4)->default(1);
            $table->string('origin', 30);
            $table->string('extractor')->nullable();
            $table->string('prompt_version')->nullable();
            $table->string('input_hash', 64)->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->unique(['source_node_id', 'target_node_id', 'relation_type'], 'knowledge_edges_pair_type_unique');
            $table->index(['source_node_id', 'status', 'relation_type'], 'knowledge_edges_source_status_type_idx');
            $table->index(['target_node_id', 'status', 'relation_type'], 'knowledge_edges_target_status_type_idx');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE knowledge_edges ADD CONSTRAINT knowledge_edges_distinct_nodes CHECK (source_node_id <> target_node_id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_edges');
    }
};
