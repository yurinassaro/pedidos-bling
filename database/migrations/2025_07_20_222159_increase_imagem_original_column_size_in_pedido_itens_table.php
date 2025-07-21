<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table) {
            // Mudar de string (255 caracteres) para text (65,535 caracteres)
            $table->text('imagem_original')->nullable()->change();
            $table->text('imagem_personalizada')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table) {
            // Voltar para string se necessário
            $table->string('imagem_original')->nullable()->change();
            $table->string('imagem_personalizada')->nullable()->change();
        });
    }
};