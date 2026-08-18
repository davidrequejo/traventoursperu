<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatC10CodigoNotaDebito extends Model
{
    protected $table = 'sunat_c10_codigo_nota_debito';

    protected $primaryKey = 'idsunat_c10_codigo_nota_debito';

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
