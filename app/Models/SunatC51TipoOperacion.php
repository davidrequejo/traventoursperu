<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SunatC51TipoOperacion extends Model
{
    protected $table = 'sunat_c51_tipo_operacion';

    protected $primaryKey = 'idsunat_c51_tipo_operacion';

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

    public function documentos(): HasMany
    {
        return $this->hasMany(RDocumento::class, 'idsunat_c51', 'idsunat_c51_tipo_operacion');
    }
}

