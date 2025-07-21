<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Adicionar campos faltantes (se não existirem)
            if (!Schema::hasColumn('pedidos', 'bling_id')) {
                $table->string('bling_id')->unique()->after('id');
            }
            
            if (!Schema::hasColumn('pedidos', 'numero')) {
                $table->integer('numero')->unique()->after('bling_id');
            }
            
            if (!Schema::hasColumn('pedidos', 'status')) {
                $table->enum('status', ['aberto', 'em_producao', 'finalizado'])->default('aberto')->after('numero');
            }
            
            if (!Schema::hasColumn('pedidos', 'cliente_nome')) {
                $table->string('cliente_nome')->after('status');
            }
            
            if (!Schema::hasColumn('pedidos', 'cliente_telefone')) {
                $table->string('cliente_telefone')->nullable()->after('cliente_nome');
            }
            
            if (!Schema::hasColumn('pedidos', 'observacoes_internas')) {
                $table->text('observacoes_internas')->nullable()->after('cliente_telefone');
            }
            
            if (!Schema::hasColumn('pedidos', 'data_pedido')) {
                $table->date('data_pedido')->after('observacoes_internas');
            }
            
            if (!Schema::hasColumn('pedidos', 'importado')) {
                $table->boolean('importado')->default(true)->after('data_pedido');
            }
            
            if (!Schema::hasColumn('pedidos', 'data_importacao')) {
                $table->timestamp('data_importacao')->nullable()->after('importado');
            }
            
            if (!Schema::hasColumn('pedidos', 'data_producao')) {
                $table->timestamp('data_producao')->nullable()->after('data_importacao');
            }
            
            if (!Schema::hasColumn('pedidos', 'data_finalizacao')) {
                $table->timestamp('data_finalizacao')->nullable()->after('data_producao');
            }
            
            // Adicionar índices se não existirem
            if (!Schema::hasIndex('pedidos', 'pedidos_status_index')) {
                $table->index('status');
            }
            
            if (!Schema::hasIndex('pedidos', 'pedidos_numero_index')) {
                $table->index('numero');
            }
            
            if (!Schema::hasIndex('pedidos', 'pedidos_data_pedido_index')) {
                $table->index('data_pedido');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['numero']);
            $table->dropIndex(['data_pedido']);
            
            $table->dropColumn([
                'bling_id',
                'numero',
                'status',
                'cliente_nome',
                'cliente_telefone',
                'observacoes_internas',
                'data_pedido',
                'importado',
                'data_importacao',
                'data_producao',
                'data_finalizacao'
            ]);
        });
    }
};