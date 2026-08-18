<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatC09CodigoNotaCredito extends Model
{
    protected $table = 'sunat_c09_codigo_nota_credito';

    protected $primaryKey = 'idsunat_c09_codigo_nota_credito';

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
