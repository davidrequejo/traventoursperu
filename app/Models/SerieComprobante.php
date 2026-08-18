<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerieComprobante extends Model
{
    protected $table = 'serie_comprobante';

    protected $primaryKey = 'idserie_comprobante';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'idsunat_c01_tipo_comprobante',
        'serie',
        'numero',
        'tipo_comprobante_adicional',
        'predeterminado',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];

    public function tipoComprobante(): BelongsTo
    {
        return $this->belongsTo(SunatC01TipoComprobante::class, 'idsunat_c01_tipo_comprobante', 'idsunat_c01_tipo_comprobante');
    }

    public function comprobanteAdicional(): BelongsTo
    {
        return $this->belongsTo(SunatC01TipoComprobante::class, 'tipo_comprobante_adicional', 'idsunat_c01_tipo_comprobante');
    }

    public function permisosUsuariosSeries(): HasMany
    {
        return $this->hasMany(PermisoUsuarioSerie::class, 'idserie_comprobante', 'idserie_comprobante');
    }
}
