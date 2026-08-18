<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatC11CodigoValorVenta extends Model
{
    protected $table = 'sunat_c11_codigo_valor_venta';

    protected $primaryKey = 'idsunat_c11_codigo_valor_venta';

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
