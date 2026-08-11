<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->uuid('project_id')->nullable()->index();
            $table->boolean('is_global')->default(false)->index();
        });

        Schema::table('captures', function (Blueprint $table) {
            $table->uuid('project_id')->nullable()->index();
        });

        Schema::table('harness_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dropUnique('harness_profiles_harness_name_unique');
            $table->unique(['user_id', 'harness', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('harness_profiles', function (Blueprint $table) {
            $table->dropUnique('harness_profiles_user_id_harness_name_unique');
            $table->dropConstrainedForeignId('user_id');
            $table->unique(['harness', 'name']);
        });

        Schema::table('captures', function (Blueprint $table) {
            $table->dropColumn('project_id');
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropColumn(['project_id', 'is_global']);
        });

        Schema::dropIfExists('projects');
    }
};
