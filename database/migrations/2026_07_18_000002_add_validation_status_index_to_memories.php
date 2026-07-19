<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            // Filtro do MemoryList + counts do Dashboard consultam por status.
            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->dropIndex(['validation_status']);
        });
    }
};
