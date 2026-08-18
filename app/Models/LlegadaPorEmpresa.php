<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlegadaPorEmpresa extends Model
{
    protected $table = 'llegada_por_empresa';
    protected $primaryKey = 'idllegada_por_empresa';
    protected $fillable = ['idllegada_tipo', 'descripcion', 'estado_trash', 'user_trash', 'user_created', 'user_updated'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(LlegadaTipo::class, 'idllegada_tipo', 'idllegada_tipo');
    }
}