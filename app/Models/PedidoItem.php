<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    protected $table = 'pedido_itens';

    protected $fillable = [
        'pedido_id',
        'bling_produto_id',
        'descricao',
        'quantidade',
        'imagem_original',
        'imagem_local',
        'imagem_personalizada',
        'observacoes',
        'ordem'
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'ordem' => 'integer',
    ];

    /**
     * Função: pedido
     * Descrição: Relacionamento com o pedido pai.
     * Parâmetros: Nenhum
     * Retorno:
     *   - BelongsTo: Relação com Pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Função: getImagemAttribute
     * Descrição: Retorna a imagem personalizada se existir, senão retorna a original.
     * Parâmetros: Nenhum
     * Retorno:
     *   - string|null: URL da imagem
     */
    public function getImagemAttribute(): ?string
    {
        return $this->imagem_personalizada ?? $this->imagem_original;
    }

    /**
     * Função: hasCustomImage
     * Descrição: Verifica se o item possui imagem personalizada.
     * Parâmetros: Nenhum
     * Retorno:
     *   - bool: true se tem imagem personalizada, false caso contrário
     */
    public function hasCustomImage(): bool
    {
        return !empty($this->imagem_personalizada);
    }
}