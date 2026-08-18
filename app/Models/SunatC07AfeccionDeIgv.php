<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatC07AfeccionDeIgv extends Model
{
    protected $table = 'sunat_c07_afeccion_de_igv';

    protected $primaryKey = 'idsunat_c07_afeccion_de_igv';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'codigo',
        'nombre',
        'codigo_tributario',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];
}
