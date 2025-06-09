<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlingToken extends Model
{
    protected $fillable = ['access_token', 'expires_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}