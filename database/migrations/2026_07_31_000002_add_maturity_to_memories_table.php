<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->string('maturity', 30)->default('provisional')->after('scope');
        });

        // Popula o estado inicial das memórias existentes
        DB::table('memories')->where('type', 'workaround')->update(['maturity' => 'workaround']);
        DB::table('memories')->where('type', '!=', 'workaround')->update(['maturity' => 'provisional']);
    }

    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->dropColumn('maturity');
        });
    }
};
