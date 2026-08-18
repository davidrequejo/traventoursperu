<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SunatC12DocumentoRelacionado extends Model
{
    protected $table = 'sunat_c12_documento_relacionado';

    protected $primaryKey = 'idsunat_c12_documento_relacionado';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function documentosRelacionados(): HasMany
    {
        return $this->hasMany(RDocumentoRelacionado::class, 'idsunat_c12', 'idsunat_c12_documento_relacionado');
    }
}

