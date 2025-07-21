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
        Schema::create('pedido_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->string('bling_produto_id')->nullable();
            $table->string('descricao');
            $table->decimal('quantidade', 10, 2);
            $table->string('imagem_original')->nullable(); // Imagem vinda do Bling
            $table->string('imagem_personalizada')->nullable(); // Imagem customizada pelo usuário
            $table->text('observacoes')->nullable(); // Observações específicas do item
            $table->integer('ordem')->default(0); // Para ordenar os itens
            $table->timestamps();
            
            $table->index('pedido_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_itens');
    }
};