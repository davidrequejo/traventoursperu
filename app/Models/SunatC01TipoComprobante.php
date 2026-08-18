<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SunatC01TipoComprobante extends Model
{
    protected $table = 'sunat_c01_tipo_comprobante';

    protected $primaryKey = 'idsunat_c01_tipo_comprobante';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'codigo',
        'nombre',
        'abreviatura',
        'serie',
        'numero',
        'un1001',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_trash' => 'integer',
            'user_created' => 'integer',
            'user_updated' => 'integer',
        ];
    }

    public function series(): HasMany
    {
        return $this->hasMany(SerieComprobante::class, 'idsunat_c01_tipo_comprobante', 'idsunat_c01_tipo_comprobante');
    }

    public function seriesActivas(): HasMany
    {
        return $this->series()->where('estado_trash', '1');
    }
}
