<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatC20CodigoMotivoTraslado extends Model
{
    protected $table = 'sunat_c20_codigo_motivo_traslado';

    protected $primaryKey = 'idsunat_c20_codigo_motivo_traslado';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'codigo',
        'nombre',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];
}
