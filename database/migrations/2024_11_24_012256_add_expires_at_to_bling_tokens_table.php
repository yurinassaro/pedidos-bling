<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bling_tokens', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable(); // Adiciona o campo expires_at
        });
    }

    public function down(): void
    {
        Schema::table('bling_tokens', function (Blueprint $table) {
            $table->dropColumn('expires_at'); // Remove o campo expires_at
        });
    }
};
