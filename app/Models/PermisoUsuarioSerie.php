<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermisoUsuarioSerie extends Model
{
    protected $table = 'permiso_usuario_serie';

    protected $primaryKey = 'idpermiso_usuario_serie';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'idusers',
        'idserie_comprobante',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idusers', 'id');
    }

    public function serieComprobante(): BelongsTo
    {
        return $this->belongsTo(SerieComprobante::class, 'idserie_comprobante', 'idserie_comprobante');
    }
}
