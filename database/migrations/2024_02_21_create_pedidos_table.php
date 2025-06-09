<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('bling_id')->unique();
            $table->string('numero');
            $table->string('cliente_nome');
            $table->text('observacoes_internas')->nullable();
            $table->dateTime('data_pedido');
            $table->integer('situacao')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};