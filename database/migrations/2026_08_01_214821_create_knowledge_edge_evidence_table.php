<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_edge_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('knowledge_edge_id')->constrained('knowledge_edges')->cascadeOnDelete();
            $table->foreignUuid('capture_id')->nullable()->constrained('captures')->nullOnDelete();
            $table->foreignUuid('memory_id')->nullable()->constrained('memories')->nullOnDelete();
            $table->string('source_type', 40);
            $table->text('source_uri')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('evidence_hash', 64);
            $table->decimal('confidence', 5, 4)->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['knowledge_edge_id', 'evidence_hash'], 'knowledge_edge_evidence_edge_hash_unique');
            $table->index(['source_type', 'evidence_hash'], 'knowledge_edge_evidence_source_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_edge_evidence');
    }
};
