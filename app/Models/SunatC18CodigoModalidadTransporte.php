<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatC18CodigoModalidadTransporte extends Model
{
    protected $table = 'sunat_c18_codigo_modalidad_transporte';

    protected $primaryKey = 'idsunat_c18_codigo_modalidad_transporte';

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
