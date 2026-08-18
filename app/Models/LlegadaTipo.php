<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LlegadaTipo extends Model
{
    protected $table = 'llegada_tipo';
    protected $primaryKey = 'idllegada_tipo';
    protected $fillable = ['descripcion', 'estado_trash', 'user_trash', 'user_created', 'user_updated'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(LlegadaPorEmpresa::class, 'idllegada_tipo', 'idllegada_tipo');
    }
}