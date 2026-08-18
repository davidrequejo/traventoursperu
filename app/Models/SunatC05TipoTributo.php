<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatC05TipoTributo extends Model
{
    protected $table = 'sunat_c05_tipo_tributo';

    protected $primaryKey = 'idsunat_c05_tipo_tributo';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'code_sunat',
        'nombre',
        'abreviatura',
        'unece5153',
        'estado_trash',
        'user_trash',
        'user_created',
        'user_updated',
    ];
}
