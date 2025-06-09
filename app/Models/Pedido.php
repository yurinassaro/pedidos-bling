<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $fillable = [
        'bling_id',
        'numero',
        'cliente_nome',
        'observacoes_internas',
        'data_pedido',
        'situacao'
    ];

    protected $casts = [
        'data_pedido' => 'datetime',
    ];

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }
}