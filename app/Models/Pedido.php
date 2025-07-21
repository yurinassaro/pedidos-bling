<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $fillable = [
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
    ];

    protected $casts = [
        'data_pedido' => 'date',
        'data_importacao' => 'datetime',
        'data_producao' => 'datetime',
        'data_finalizacao' => 'datetime',
        'importado' => 'boolean',
    ];

    /**
     * Função: itens
     * Descrição: Relacionamento com os itens do pedido.
     * Parâmetros: Nenhum
     * Retorno:
     *   - HasMany: Relação com PedidoItem
     */
    public function itens(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    /**
     * Função: scopeAberto
     * Descrição: Scope para filtrar pedidos com status 'aberto'.
     * Parâmetros:
     *   - query (Builder): Query builder
     * Retorno:
     *   - Builder: Query modificada
     */
    public function scopeAberto($query)
    {
        return $query->where('status', 'aberto');
    }

    /**
     * Função: scopeEmProducao
     * Descrição: Scope para filtrar pedidos com status 'em_producao'.
     * Parâmetros:
     *   - query (Builder): Query builder
     * Retorno:
     *   - Builder: Query modificada
     */
    public function scopeEmProducao($query)
    {
        return $query->where('status', 'em_producao');
    }

    /**
     * Função: scopeFinalizado
     * Descrição: Scope para filtrar pedidos com status 'finalizado'.
     * Parâmetros:
     *   - query (Builder): Query builder
     * Retorno:
     *   - Builder: Query modificada
     */
    public function scopeFinalizado($query)
    {
        return $query->where('status', 'finalizado');
    }

    /**
     * Função: setEmProducao
     * Descrição: Altera o status do pedido para 'em_producao' e registra a data.
     * Parâmetros: Nenhum
     * Retorno:
     *   - bool: Sucesso da operação
     */
    public function setEmProducao(): bool
    {
        $this->status = 'em_producao';
        $this->data_producao = now();
        return $this->save();
    }

    /**
     * Função: setFinalizado
     * Descrição: Altera o status do pedido para 'finalizado' e registra a data.
     * Parâmetros: Nenhum
     * Retorno:
     *   - bool: Sucesso da operação
     */
    public function setFinalizado(): bool
    {
        $this->status = 'finalizado';
        $this->data_finalizacao = now();
        return $this->save();
    }
}