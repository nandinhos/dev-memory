<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harness_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('harness');
            $table->string('name')->default('default');
            $table->string('version')->default('1.0.0');
            $table->text('description')->nullable();
            $table->json('files');
            $table->timestamps();

            $table->unique(['harness', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harness_profiles');
    }
};
